<?php

namespace App\Http\Controllers\Api\Landing;

use App\Http\Controllers\Frontend\Landing\BaseLandingsPageController;
use App\Jobs\Landings\DownloadWebsiteJob;
use App\Models\Frontend\Landings\WebsiteDownloadMonitor;
use App\Services\Common\AntiFloodService;
use App\Services\Common\Landings\LandingDownloadService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

use function App\Helpers\sanitize_url;

class LandingsPageApiController extends BaseLandingsPageController
{
    use AuthorizesRequests;

    public function __construct(
        LandingDownloadService $downloadService,
        AntiFloodService $antiFloodService
    ) {
        parent::__construct($downloadService, $antiFloodService);
    }

    /**
     * Handles AJAX request to get a list of landings.
     *
     * @return \Illuminate\Http\Response
     */
    public function ajaxList(Request $request): JsonResponse
    {
        $viewConfig = $this->getViewConfig();
        $data = parent::getData($request);

        $html = $this->renderContentWrapperView($data);

        $responseObject = [
            'success' => true,
            'message' => __('landings.success.successfully_loaded_message_text'),
            'data' => [
                'table_html' => $html,

            ],
        ];

        return $this->jsonResponse($responseObject);
    }

    /**
     * Handles AJAX request to store a new landing.
     */
    public function ajaxStore(Request $request): JsonResponse
    {
        Log::info('ajaxStore attempt initiated', ['url' => $request->input('url'), 'user_id' => Auth::id()]);
        try {
            $validated = $request->validate([
                'url' => [
                    'required',
                    'string',
                    'max:300', // Consistent with WebsiteDownloadController
                    function ($attribute, $value, $fail) {
                        $sanitizedUrl = sanitize_url($value);
                        $processedUrl = preg_replace('/\\{[^}]+\\}/', 'dummy', $sanitizedUrl);
                        if (! filter_var($processedUrl, FILTER_VALIDATE_URL)) {
                            $fail(__('validation.url', ['attribute' => $attribute]));
                        }
                    },
                ],
            ]);

            $sanitizedUrl = sanitize_url($validated['url']);

            $existingDownloads = WebsiteDownloadMonitor::where('user_id', Auth::id())
                ->where(function ($query) use ($sanitizedUrl) {
                    $query->where('url', $sanitizedUrl)
                        ->orWhere('url', 'like', '%' . parse_url($sanitizedUrl, PHP_URL_HOST) . '%');
                })
                ->whereIn('status', ['pending', 'in_progress', 'completed'])
                ->first();

            if ($existingDownloads) {
                $descriptionKey = match ($existingDownloads->status) {
                    'pending', 'in_progress' => 'landings.alreadyInProgress.description',
                    'completed' => 'landings.alreadyDownloaded.description',
                    default => 'landings.duplicateDownload.description', // Should not happen with current whereIn
                };
                Log::warning('Duplicate download attempt', [
                    'url' => $sanitizedUrl,
                    'user_id' => Auth::id(),
                    'existing_status' => $existingDownloads->status,
                ]);

                return response()->json([
                    'success' => false,
                    'message' => __($descriptionKey),
                ], 409); // 409 Conflict
            }

            try {
                $this->checkWebsiteAvailability($sanitizedUrl);
                Log::info('Website availability check passed', ['url' => $sanitizedUrl, 'user_id' => Auth::id()]);
            } catch (\Exception $e) {
                Log::error('Failed to check website availability for ajaxStore: ' . $e->getMessage(), ['url' => $sanitizedUrl, 'user_id' => Auth::id()]);

                return response()->json([
                    'success' => false,
                    'message' => __('landings.downloadFailedUrlDisabled.description'),
                ], 400); // 400 Bad Request (URL not accessible)
            }

            // Using AntiFloodService logic from WebsiteDownloadController
            if (! $this->antiFloodService->check($request->user()->id, 'website-download', 2, 3600)) {

                return response()->json([
                    'success' => false,
                    'message' => __('landings.antiFlood.description'),
                ], 429); // 429 Too Many Requests
            }

            $uuid = Str::uuid();
            $monitor = WebsiteDownloadMonitor::create([
                'url' => $sanitizedUrl,
                'output_path' => 'website-downloads/' . $uuid,
                'user_id' => Auth::id(),
                'status' => 'pending',
                'progress' => 0,
            ]);
            Log::info('WebsiteDownloadMonitor record created', ['uuid' => $uuid, 'user_id' => Auth::id()]);

            DownloadWebsiteJob::dispatch(
                $sanitizedUrl,
                $uuid,
                Auth::id(),
                []
            )->onQueue('website-downloads');
            Log::info('DownloadWebsiteJob dispatched', ['uuid' => $uuid, 'user_id' => Auth::id()]);

            $userLandingsCount = WebsiteDownloadMonitor::where('user_id', Auth::id())->count();
            $responseData = ['landing_id' => $monitor->id];

            // Get per_page from request to properly handle pagination
            $perPage = $request->input('per_page', 12);
            $responseData['per_page'] = $perPage;

            // For pagination calculations, get the total count of items
            $totalCount = WebsiteDownloadMonitor::where('user_id', Auth::id())->count();
            $responseData['total_items'] = $totalCount;

            // Calculate the last page number
            $lastPage = ceil($totalCount / $perPage);
            $responseData['last_page'] = $lastPage;

            if ($userLandingsCount === 1) {
                // This is the first landing for the user, send the whole table structure
                $tableData = parent::getData($request); // $request is available in the method
                $fullTableHtml = $this->renderContentWrapperView($tableData);
                $responseData['table_html'] = $fullTableHtml;
            } else {
                // Not the first landing, send only the new row

                // For items that exceed the per_page limit, we need to return complete table HTML
                // to properly set up pagination
                $currentPage = $request->input('page', 1);
                $itemsOnCurrentPage = WebsiteDownloadMonitor::where('user_id', Auth::id())
                    ->orderBy($request->input('sort', 'created_at'), $request->input('direction', 'desc'))
                    ->skip(($currentPage - 1) * $perPage)
                    ->take($perPage + 1) // Check if we have more than perPage items
                    ->count();

                if ($itemsOnCurrentPage > $perPage) {
                    $tableData = parent::getData($request);
                    $fullTableHtml = $this->renderContentWrapperView($tableData);
                    $responseData['table_html'] = $fullTableHtml;
                } else {
                    // Just add the new row if we haven't exceeded per_page
                    $landingHtml = view('components.landings.table.row', ['landing' => $monitor, 'viewConfig' => $this->getViewConfig()])->render();
                    $responseData['landing_html'] = $landingHtml;
                }
            }

            return response()->json([
                'success' => true,
                'message' => __('landings.downloadStarted.description'), // Message from WebsiteDownloadController
                'data' => $responseData,
            ], 201); // 201 Created
        } catch (ValidationException $e) {

            return response()->json([
                'success' => false,
                'message' => $e->getMessage(), // Or a generic validation error message
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {

            return response()->json([
                'success' => false,
                'message' => __('landings.generalError.description'), // Message from WebsiteDownloadController
            ], 500);
        }
    }

    /**
     * Check if a website is available and responding
     * Copied from WebsiteDownloadController
     *
     * @param  string  $url  The URL to check
     *
     * @throws \Exception if the website is not available
     */
    protected function checkWebsiteAvailability(string $url): void
    {
        try {
            // Try with HEAD request first
            $response = Http::timeout(10)
                ->withoutVerifying() // Important for self-signed certs or misconfigurations
                ->head($url);

            if (! $response->successful()) {
                // If HEAD fails, try GET (some servers might not support HEAD properly)
                $response = Http::timeout(10)->withoutVerifying()->get($url);
                if (! $response->successful()) {
                    throw new \Exception('Website returned status code: ' . $response->status());
                }
            }
        } catch (\Illuminate\Http\Client\ConnectionException $e) {

            throw new \Exception('Failed to connect to website: ' . $e->getMessage());
        } catch (\Exception $e) {
            // Catching the re-thrown exception from the block above or other general exceptions
            Log::error('Website availability check failed (General Exception)', [
                'url' => $url,
                'error' => $e->getMessage(),
            ]);
            throw $e; // Re-throw the caught exception
        }
    }

    /**
     * Handles AJAX request to delete a landing.
     */
    public function ajaxDestroy(WebsiteDownloadMonitor $landing): JsonResponse
    {
        $this->authorize('delete', $landing); // Использует WebsiteDownloadMonitorPolicy

        try {
            // Сохраняем путь до удаления записи из базы
            $outputPath = $landing->output_path;
            $userId = Auth::id();

            // Сначала удаляем запись из базы данных
            $landing->delete();

            // Затем удаляем файлы, если они существуют
            if ($outputPath) {

                // Проверяем существование директории или файла
                if (Storage::exists($outputPath)) {
                    // Определяем, является ли путь директорией
                    $fullPath = Storage::path($outputPath);
                    if (is_dir($fullPath)) {
                        // Удаляем всю директорию лендинга
                        $result = Storage::deleteDirectory($outputPath);
                    } else {
                        // Удаляем только файл
                        $result = Storage::delete($outputPath);
                    }
                } else {
                    Log::warning('Landing files not found for deletion', [
                        'output_path' => $outputPath,
                    ]);
                }
            }

            // Get current pagination stats after deletion
            $perPage = request()->input('per_page', 12);
            $totalItems = WebsiteDownloadMonitor::where('user_id', $userId)->count();
            $lastPage = max(1, ceil($totalItems / $perPage));

            // Calculate remaining items on current page
            $currentPage = request()->input('page', 1);
            $offset = ($currentPage - 1) * $perPage;
            $itemsOnCurrentPage = WebsiteDownloadMonitor::where('user_id', $userId)
                ->orderBy(request()->input('sort', 'created_at'), request()->input('direction', 'desc'))
                ->skip($offset)
                ->limit($perPage)
                ->count();

            return response()->json([
                'success' => true,
                'message' => __('landings.success.successfully_deleted_message_text'),
                'pagination' => [
                    'total_items' => $totalItems,
                    'per_page' => $perPage,
                    'current_page' => $currentPage,
                    'last_page' => $lastPage,
                    'items_on_current_page' => $itemsOnCurrentPage,
                ],
            ]);
        } catch (\Exception $e) {

            return response()->json([
                'success' => false,
                'message' => __('landings.errors.error_occurred'),
            ], 500);
        }
    }

    /**
     * Handles AJAX request to download a landing.
     */
    public function ajaxDownload(WebsiteDownloadMonitor $landing, Request $request): JsonResponse
    {

        // Authorize the action using the policy (checks ownership and status)
        $this->authorize('download', $landing);

        try {

            // Проверяем, что лендинг существует и имеет статус 'completed'
            if ($landing->status !== 'completed') {
                return response()->json([
                    'success' => false,
                    'message' => __('landings.errors.not_completed'),
                ], 400);
            }

            // Проверяем, что файл архива существует
            if (! $landing->output_path || ! file_exists(Storage::path($landing->output_path))) {
                // Обновляем статус лендинга на "failed"
                $landing->update([
                    'status' => 'failed',
                    'error' => 'File not found on server',
                    'completed_at' => now(),
                ]);

                return response()->json([
                    'success' => false,
                    'message' => __('landings.errors.file_not_found'),
                ], 404);
            }

            Log::debug('Archive file exists, preparing download URL', [
                'landing_id' => $landing->getKey(),
            ]);

            // Формируем URL для скачивания
            $downloadUrl = route('landings.download', $landing->getKey());

            Log::debug('Returning successful download response', [
                'landing_id' => $landing->getKey(),
                'download_url' => $downloadUrl,
                'filename' => basename($landing->output_path),
            ]);

            return response()->json([
                'success' => true,
                'message' => __('landings.success.download_ready'),
                'data' => [
                    'download_url' => $downloadUrl,
                    'landing_id' => $landing->getKey(),
                    'filename' => basename($landing->output_path),
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('Error preparing landing download via AJAX: ' . $e->getMessage(), [
                'exception' => $e,
                'landing_id' => $landing->getKey(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => __('landings.errors.error_occurred'),
            ], 500);
        }
    }
}

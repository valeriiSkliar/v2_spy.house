<?php

namespace App\Http\Controllers\Api\Landing;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Frontend\Landing\BaseLandingsPageController;
use App\Models\Frontend\Landings\WebsiteDownloadMonitor;
use App\Services\App\AntiFloodService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;
use App\Jobs\Landings\DownloadWebsiteJob;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use function App\Helpers\sanitize_url;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class LandingsPageApiController extends BaseLandingsPageController
{
    use AuthorizesRequests;
    protected AntiFloodService $antiFloodService;

    public function __construct(AntiFloodService $antiFloodService)
    {
        $this->antiFloodService = $antiFloodService;
    }

    // private function getViewConfig(): array
    // {
    //     return [
    //         'show_status' => true,
    //         'show_progress' => true,
    //         'show_actions' => true,
    //         'show_domain' => true,
    //         'show_download_date' => true,
    //         'date_format' => 'd.m.Y H:i',
    //         'column_classes' => [
    //             'name' => 'col-lg-3 col-md-4 col-sm-6',
    //             'domain' => 'col-lg-2 col-md-3 d-none d-md-table-cell',
    //             'status' => 'col-lg-2 col-md-2 col-sm-3',
    //             'progress' => 'col-lg-2 col-md-3 d-none d-lg-table-cell',
    //             'download_date' => 'col-lg-2 col-md-3 d-none d-md-table-cell text-muted small',
    //             'actions' => 'col-lg-1 col-md-2 col-sm-3 text-end',
    //         ],
    //         'action_icons' => [
    //             'ACTION_DOWNLOAD' => 'fas fa-download',
    //             'ACTION_RETRY' => 'fas fa-sync-alt',
    //             'ACTION_VIEW' => 'fas fa-eye',
    //             'ACTION_DELETE' => 'fas fa-trash-alt text-danger',
    //             'ACTION_CANCEL' => 'fas fa-ban text-warning',
    //         ],
    //         'badge_config' => [
    //             'completed' => ['class' => 'bg-success-soft', 'icon' => 'fas fa-check-circle'],
    //             'in_progress' => ['class' => 'bg-primary-soft', 'icon' => 'fas fa-spinner fa-pulse'],
    //             'pending' => ['class' => 'bg-secondary-soft', 'icon' => 'fas fa-clock'],
    //             'failed' => ['class' => 'bg-danger-soft', 'icon' => 'fas fa-exclamation-triangle'],
    //             'cancelled' => ['class' => 'bg-warning-soft', 'icon' => 'fas fa-ban'],
    //             'moderation' => ['class' => 'bg-info-soft', 'icon' => 'fas fa-gavel'],
    //         ]
    //     ];
    // }

    /**
     * Handles AJAX request to get a list of landings.
     *
     * @param Request $request
     * @return \Illuminate\Http\Response
     */
    public function ajaxList(Request $request): JsonResponse
    {
        $viewConfig = $this->getViewConfig();
        $data = parent::getData($request);

        $html = $this->renderContentWrapperView($data);

        $responseObject = [
            'success' => true,
            'message' => __('landings.successfully_loaded_message_text'),
            'data' => [
                'table_html' => $html,

            ],
        ];

        return $this->jsonResponse($responseObject);
    }

    /**
     * Handles AJAX request to store a new landing.
     *
     * @param Request $request
     * @return JsonResponse
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
                        if (!filter_var($processedUrl, FILTER_VALIDATE_URL)) {
                            $fail(__('validation.url', ['attribute' => $attribute]));
                        }
                    }
                ],
            ]);

            $sanitizedUrl = sanitize_url($validated['url']);
            Log::info('URL validated and sanitized', ['sanitized_url' => $sanitizedUrl, 'user_id' => Auth::id()]);

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
                    'existing_status' => $existingDownloads->status
                ]);
                return response()->json([
                    'success' => false,
                    'message' => __($descriptionKey),
                ], 409); // 409 Conflict
            }

            Log::info('No existing downloads found, proceeding.', ['url' => $sanitizedUrl, 'user_id' => Auth::id()]);

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
            if (!$this->antiFloodService->check($request->user()->id, 'website-download', 2, 3600)) {
                Log::warning('Anti-flood limit reached for website-download', [
                    'user_id' => $request->user()->id,
                    'action' => 'website-download'
                ]);
                return response()->json([
                    'success' => false,
                    'message' => __('landings.antiFlood.description'),
                ], 429); // 429 Too Many Requests
            }
            Log::info('Anti-flood check passed', ['user_id' => Auth::id()]);


            $uuid = Str::uuid();
            $monitor = WebsiteDownloadMonitor::create([
                'url' => $sanitizedUrl,
                'output_path' => 'private/website-downloads/' . $uuid,
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

            // Generate HTML for the new row
            // Assuming 'components.landings.table.row' can handle a WebsiteDownloadMonitor instance
            // or that 'landing' is a generic enough key for the view.
            $landingHtml = view('components.landings.table.row', ['landing' => $monitor, 'viewConfig' => $this->getViewConfig()])->render();
            Log::info('Landing HTML row rendered for response', ['uuid' => $uuid, 'user_id' => Auth::id()]);

            return response()->json([
                'success' => true,
                'message' => __('landings.downloadStarted.description'), // Message from WebsiteDownloadController
                'data' => [ // Added 'data' key to encapsulate response details
                    'landing_id' => $monitor->id, // Use monitor's ID
                    'landing_html' => $landingHtml,
                ]
            ], 201); // 201 Created
        } catch (ValidationException $e) {
            Log::error('URL validation failed for ajaxStore', [
                'url' => $request->input('url'),
                'user_id' => Auth::id(),
                'errors' => $e->errors(),
            ]);
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(), // Or a generic validation error message
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            Log::error('Failed to start download in ajaxStore', [
                'url' => $request->input('url'),
                'user_id' => Auth::id(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString() // More details for debugging
            ]);
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
     * @param string $url The URL to check
     * @throws \Exception if the website is not available
     */
    protected function checkWebsiteAvailability(string $url): void
    {
        try {
            // Try with HEAD request first
            $response = Http::timeout(10)
                ->withoutVerifying() // Important for self-signed certs or misconfigurations
                ->head($url);

            if (!$response->successful()) {
                // If HEAD fails, try GET (some servers might not support HEAD properly)
                $response = Http::timeout(10)->withoutVerifying()->get($url);
                if (!$response->successful()) {
                    Log::warning('Website check failed with GET', ['url' => $url, 'status' => $response->status()]);
                    throw new \Exception("Website returned status code: " . $response->status());
                }
            }
            Log::info('Website availability check successful', ['url' => $url, 'status' => $response->status()]);
        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            Log::error('Website availability check failed (Connection Exception)', [
                'url' => $url,
                'error' => $e->getMessage()
            ]);
            throw new \Exception("Failed to connect to website: " . $e->getMessage());
        } catch (\Exception $e) {
            // Catching the re-thrown exception from the block above or other general exceptions
            Log::error('Website availability check failed (General Exception)', [
                'url' => $url,
                'error' => $e->getMessage()
            ]);
            throw $e; // Re-throw the caught exception
        }
    }

    /**
     * Handles AJAX request to delete a landing.
     *
     * @param WebsiteDownloadMonitor $landing
     * @return JsonResponse
     */
    public function ajaxDestroy(WebsiteDownloadMonitor $landing): JsonResponse
    {
        $this->authorize('delete', $landing); // Использует WebsiteDownloadMonitorPolicy

        try {
            if ($landing->path_to_archive && Storage::disk('landings')->exists($landing->path_to_archive)) {
                // Удаляем всю директорию лендинга, так как HTTrack создает поддиректорию с именем сайта
                $directoryPath = dirname($landing->path_to_archive);
                if ($directoryPath !== '.') { // Предосторожность, чтобы не удалить корень диска 'landings'
                    Storage::disk('landings')->deleteDirectory($directoryPath);
                } else {
                    // Если путь к архиву не содержит поддиректорий, удаляем только сам файл
                    Storage::disk('landings')->delete($landing->path_to_archive);
                }
            }
            $landing->delete();

            return response()->json([
                'success' => true,
                'message' => __('landings.successfully_deleted_message_text'),
            ]);
        } catch (\Exception $e) {
            Log::error('Error deleting landing via AJAX: ' . $e->getMessage(), ['exception' => $e]);
            return response()->json([
                'success' => false,
                'message' => __('common.error_occurred_common_message'),
            ], 500);
        }
    }
}

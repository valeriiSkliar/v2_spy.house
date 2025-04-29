<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\FrontendController;
use App\Models\Frondend\Landings\WebsiteDownloadMonitor;
use App\Services\App\AntiFloodService;
use App\Services\App\Landings\LandingDownloadService;
use Illuminate\Container\Attributes\Auth;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Illuminate\Http\JsonResponse;

class LandingsPageController extends FrontendController
{
    protected LandingDownloadService $downloadService;
    protected AntiFloodService $antiFloodService;

    public function __construct(
        LandingDownloadService $downloadService,
        AntiFloodService $antiFloodService
    ) {
        $this->downloadService = $downloadService;
        $this->antiFloodService = $antiFloodService;
    }

    public function index(Request $request): View
    {
        $sortField = $request->input('sort', 'created_at');
        $sortDirection = $request->input('direction', 'desc');
        $perPage = $request->input('per_page', 12);

        // Validate sort parameters
        $allowedSortFields = ['created_at', 'status', 'url'];
        $sortField = in_array($sortField, $allowedSortFields) ? $sortField : 'created_at';
        $sortDirection = in_array($sortDirection, ['asc', 'desc']) ? $sortDirection : 'desc';

        $userId = $request->user()->id ?? 1;

        $this->antiFloodService->check($userId);

        $landings = WebsiteDownloadMonitor::where('user_id', $userId)
            ->orderBy($sortField, $sortDirection)
            ->paginate($perPage)
            ->withQueryString();


        return view('landings.index',  [
            'landings' => $landings,
            'pendingRequests' => WebsiteDownloadMonitor::where('user_id', $userId)
                ->where('status', 'pending')
                ->count(),
            'completedRequests' => WebsiteDownloadMonitor::where('user_id', $userId)
                ->where('status', 'completed')
                ->count(),
            'filters' => [
                'sort' => $sortField,
                'direction' => $sortDirection,
                'per_page' => $perPage,
            ],
        ]);
    }

    public function destroy(WebsiteDownloadMonitor $landing, Request $request): RedirectResponse
    {
        $userId = $request->user()->id ?? 1;

        if ($landing->user_id !== $userId) {
            abort(403);
        }

        // Get current page and count before deletion
        $currentPage = $request->input('page', 1);
        $perPage = $request->input('per_page', 12);
        $totalBeforeDelete = WebsiteDownloadMonitor::where('user_id', $userId)->count();

        // Delete the downloaded files
        if ($landing->output_path) {
            Storage::disk('local')->deleteDirectory($landing->output_path);
        }

        $landing->delete();

        // Calculate the appropriate page to redirect to
        $remainingItems = $totalBeforeDelete - 1;
        $lastPage = ceil($remainingItems / $perPage);

        // If we're not on the first page and this was the last item on the current page,
        // redirect to the previous page

        $itemsOnCurrentPage = WebsiteDownloadMonitor::where('user_id', $userId)
            ->orderBy('created_at', 'desc')
            ->offset(($currentPage - 1) * $perPage)
            ->limit($perPage)
            ->count();

        $targetPage = $currentPage;
        if ($currentPage > 1 && $itemsOnCurrentPage === 0) {
            $targetPage = $currentPage - 1;
        }

        // Ensure we don't exceed the last page
        $targetPage = min($targetPage, $lastPage);

        return redirect()->route('landings.index', [
            'page' => $targetPage,
            'per_page' => $perPage
        ])->with('message', [
            'title' => 'landingsPage.deleted.title',
            'type' => 'success',
            'description' => 'landingsPage.deleted.description',
            'duration' => 3000
        ]);
    }

    /**
     * Download the landing page archive.
     *
     * @param WebsiteDownloadMonitor $landing
     * @param Request $request
     * @return StreamedResponse|JsonResponse|RedirectResponse
     */
    public function download(WebsiteDownloadMonitor $landing, Request $request): StreamedResponse|JsonResponse|RedirectResponse
    {
        $userId = $request->user()->id ?? 1;

        // Проверка прав доступа
        if ($landing->user_id !== $userId) {
            // Не используем abort(403), чтобы гарантировать редирект
            return redirect()->back()->with('message', [
                'type' => 'error',
                'title' => 'landingsPage.downloadFailedAuthorization.title', // Ключ для локализации
                'description' => 'landingsPage.downloadFailedAuthorization.description', // Ключ для локализации
                'duration' => 3000
            ]);
        }

        try {
            $response = $this->downloadService->download($landing);

            // Если сервис вернул JsonResponse с ошибкой
            if ($response instanceof JsonResponse && $response->getStatusCode() !== 200) {
                $errorData = $response->getData(true);
                // Используем ключ 'message' или 'error' из ответа сервиса, или дефолтное сообщение
                $errorMessageKey = $errorData['message_key'] ?? 'landingsPage.downloadServiceFailed.description'; // Предполагаем ключ локализации
                $errorMessageParams = $errorData['message_params'] ?? []; // Параметры для перевода, если есть

                // Всегда редирект с flash сообщением
                return redirect()->back()->with('message', [
                    'title' => 'landingsPage.downloadFailed.title', // Общий заголовок ошибки
                    'description' => $errorMessageKey, // Ключ для локализации
                    'description_params' => $errorMessageParams, // Передаем параметры
                    'type' => 'error',
                    'duration' => 5000 // Увеличим время показа для ошибок сервиса
                ]);
            }

            // Успешный ответ (StreamedResponse)
            return $response;
        } catch (\Exception $e) {
            // Обновляем статус при исключении
            $landing->update([
                'status' => 'failed',
                'error' => $e->getMessage() ?? 'Download failed',
                'completed_at' => now()
            ]);

            // Редирект с сообщением об ошибке (используем ключ для локализации)
            return redirect()->back()->with('message', [
                'title' => 'landingsPage.downloadFailed.title', // Общий заголовок
                'description' => 'landingsPage.downloadException.description', // Ключ для описания
                // Можно передать текст исключения как параметр, если нужно его показать (но осторожно с XSS)
                // 'description_params' => ['error' => $e->getMessage()],
                'type' => 'error',
                'duration' => 5000
            ]);
        }
    }
}

<?php

namespace App\Http\Controllers\Frontend\Landing;

use App\Http\Controllers\FrontendController;
use App\Models\Frontend\Landings\WebsiteDownloadMonitor;
use App\Services\App\AntiFloodService;
use App\Services\App\Landings\LandingDownloadService;
use App\Services\Frontend\Toast;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class LandingsPageController extends BaseLandingsPageController
{


    public function index(Request $request): View
    {
        $sortField = $request->input('sort', 'created_at');
        $sortDirection = $request->input('direction', 'desc');
        $perPage = $request->input('per_page', 12);

        // Validate sort parameters
        $allowedSortFields = ['created_at', 'status', 'url'];
        $sortField = in_array($sortField, $allowedSortFields) ? $sortField : 'created_at';
        $sortDirection = in_array($sortDirection, ['asc', 'desc']) ? $sortDirection : 'desc';

        $userId = $request->user()->id;

        if ($userId) {
            $this->antiFloodService->check($userId);
        }

        $landings = WebsiteDownloadMonitor::where('user_id', $userId)
            ->orderBy($sortField, $sortDirection)
            ->with('user')
            ->whereNotIn('status', ['cancelled', 'in_progress'])
            ->paginate($perPage)
            ->withQueryString();

        // Fetch options using the renamed method
        $filterOptions = $this->getFilterOptions();
        $sortOptions = $filterOptions['sortOptions'];
        $perPageOptions = $filterOptions['perPageOptions'];
        $sortOptionsPlaceholder = $filterOptions['sortOptionsPlaceholder'];
        $perPageOptionsPlaceholder = $filterOptions['perPageOptionsPlaceholder'];

        // Find the selected sort option based on current filters
        $selectedSort = collect($sortOptions)->first(function ($option) use ($sortField, $sortDirection) {
            return $option['value'] === $sortField && $option['order'] === $sortDirection;
        }) ?? $sortOptions[0]; // Default to the first option if not found

        // Find the selected per-page option
        $selectedPerPage = collect($perPageOptions)->firstWhere('value', $perPage) ?? $perPageOptions[0];

        return view($this->indexView,  [
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
            'sortOptions' => $sortOptions,
            'perPageOptions' => $perPageOptions,
            // Pass selected options to the view
            'selectedSort' => $selectedSort,
            'selectedPerPage' => $selectedPerPage,
            'sortOptionsPlaceholder' => $sortOptionsPlaceholder,
            'perPageOptionsPlaceholder' => $perPageOptionsPlaceholder,
        ]);
    }

    public function destroy(WebsiteDownloadMonitor $landing, Request $request): RedirectResponse
    {
        // Authorize the action using the policy
        $this->authorize('delete', $landing);

        // Get current page and count before deletion
        $currentPage = $request->input('page', 1);
        $perPage = $request->input('per_page', 12);
        $userId = $request->user()->id; // Get user ID safely
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
        // Authorize the action using the policy (checks ownership and status)
        $this->authorize('download', $landing);

        try {
            $response = $this->downloadService->download($landing);

            // Если сервис вернул JsonResponse с ошибкой
            if ($response instanceof JsonResponse && $response->getStatusCode() !== 200) {
                $errorData = $response->getData(true);
                Toast::error($errorData['message']);
                return redirect()->route('landings.index');
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
            Toast::error('landings.downloadException.description');
            return redirect()->route('landings.index');
        }
    }

    // Renamed method and updated options
    private function getFilterOptions()
    {


        $sortOptionsPlaceholder = 'Sort by — ';
        $perPageOptionsPlaceholder = 'On page — ';

        return [
            'sortOptions' => $this->sortOptions,
            'sortOptionsPlaceholder' => $sortOptionsPlaceholder,
            'perPageOptions' => $this->perPageOptions,
            'perPageOptionsPlaceholder' => $perPageOptionsPlaceholder,
        ];
    }
}

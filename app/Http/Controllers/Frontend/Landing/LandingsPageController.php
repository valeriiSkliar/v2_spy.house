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

class LandingsPageController extends FrontendController
{
    use AuthorizesRequests;

    protected $indexView = 'pages.landings.index';
    private $statusIcons = [
        'pending' => 'pending',
        'completed' => 'completed',
        'failed' => 'failed',
    ];
    private $statusLabels = [
        'pending' => 'landings.table.status.pending',
        'completed' => 'landings.table.status.completed',
        'failed' => 'landings.table.status.failed',
    ];
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

        // dd($userId, $landings->first()->user_id);
        // Fetch options using the renamed method
        $filterOptions = $this->getFilterOptions();
        $sortOptions = $filterOptions['sortOptions'];
        $perPageOptions = $filterOptions['perPageOptions'];

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
        // Correct sort options for landings
        $sortOptions = [
            ['value' => 'created_at', 'label' => 'Sort by Date (Newest First)', 'order' => 'desc'],
            ['value' => 'created_at', 'label' => 'Sort by Date (Oldest First)', 'order' => 'asc'],
            ['value' => 'status', 'label' => 'Sort by Status (Asc)', 'order' => 'asc'],
            ['value' => 'status', 'label' => 'Sort by Status (Desc)', 'order' => 'desc'],
            ['value' => 'url', 'label' => 'Sort by URL (Asc)', 'order' => 'asc'],
            ['value' => 'url', 'label' => 'Sort by URL (Desc)', 'order' => 'desc'],
        ];

        $perPageOptions = [
            ['value' => 12, 'label' => '12', 'order' => ''],
            ['value' => 24, 'label' => '24', 'order' => ''],
            ['value' => 48, 'label' => '48', 'order' => ''],
            ['value' => 96, 'label' => '96', 'order' => ''],
        ];

        return [
            'sortOptions' => $sortOptions,
            'perPageOptions' => $perPageOptions
        ];
    }
}

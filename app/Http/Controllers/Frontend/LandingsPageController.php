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
}

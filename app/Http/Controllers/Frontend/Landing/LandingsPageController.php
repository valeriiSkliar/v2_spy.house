<?php

namespace App\Http\Controllers\Frontend\Landing;

use App\Models\Frontend\Landings\WebsiteDownloadMonitor;
use App\Services\Frontend\Toast;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class LandingsPageController extends BaseLandingsPageController
{
    public function index(Request $request): View
    {
        $data = $this->getData($request);
        $userId = $request->user()->id;

        return view($this->indexView, array_merge($data, [
            'pendingRequests' => WebsiteDownloadMonitor::where('user_id', $userId)
                ->where('status', 'pending')
                ->count(),
            'completedRequests' => WebsiteDownloadMonitor::where('user_id', $userId)
                ->where('status', 'completed')
                ->count(),
            'viewConfig' => $this->getViewConfig(),
        ]));
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
            'per_page' => $perPage,
        ])->with('message', [
            'title' => 'landingsPage.deleted.title',
            'type' => 'success',
            'description' => 'landingsPage.deleted.description',
            'duration' => 3000,
        ]);
    }

    /**
     * Download the landing page archive.
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
                // Toast::error($errorData['message']);

                return redirect()->route('landings.index');
            }

            // Успешный ответ (StreamedResponse)
            return $response;
        } catch (\Exception $e) {
            // Обновляем статус при исключении
            $landing->update([
                'status' => 'failed',
                'error' => $e->getMessage() ?? __('landings.errors.download_failed'),
                'completed_at' => now(),
            ]);

            return redirect()->route('landings.index');
        }
    }
}

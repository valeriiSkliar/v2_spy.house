<?php

namespace App\Http\Controllers\Frontend\Landing;

use App\Http\Controllers\FrontendController;
use App\Services\Common\AntiFloodService;
use App\Services\Common\Landings\LandingDownloadService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use App\Models\Frontend\Landings\WebsiteDownloadMonitor;
use Illuminate\Support\Facades\Log;

class BaseLandingsPageController extends FrontendController
{
    protected $indexView = 'pages.landings.index';
    protected $statusIcons = [
        'pending' => 'pending',
        'completed' => 'completed',
        'failed' => 'failed',
    ];
    protected $statusLabels = [
        'pending' => 'landings.table.status.pending',
        'completed' => 'landings.table.status.completed',
        'failed' => 'landings.table.status.failed',
    ];

    // Correct sort options for landings
    protected $sortOptions = [
        ['value' => 'created_at', 'label' => 'Date (Newest First)', 'order' => 'desc'],
        ['value' => 'created_at', 'label' => 'Date (Oldest First)', 'order' => 'asc'],
        ['value' => 'status', 'label' => 'Status (Asc)', 'order' => 'asc'],
        ['value' => 'status', 'label' => 'Status (Desc)', 'order' => 'desc'],
        ['value' => 'url', 'label' => 'URL (Asc)', 'order' => 'asc'],
        ['value' => 'url', 'label' => 'URL (Desc)', 'order' => 'desc'],
    ];

    protected $perPageOptions = [
        ['value' => 12, 'label' => '12', 'order' => ''],
        ['value' => 24, 'label' => '24', 'order' => ''],
        ['value' => 48, 'label' => '48', 'order' => ''],
        ['value' => 96, 'label' => '96', 'order' => ''],
    ];

    use AuthorizesRequests;


    protected LandingDownloadService $downloadService;
    protected AntiFloodService $antiFloodService;

    public function __construct(
        LandingDownloadService $downloadService,
        AntiFloodService $antiFloodService
    ) {
        $this->downloadService = $downloadService;
        $this->antiFloodService = $antiFloodService;
    }

    public function renderIndexPage()
    {
        return view($this->indexView);
    }

    protected function getViewConfig(): array
    {
        return [
            'statusIcons' => $this->statusIcons,
            'statusLabels' => $this->statusLabels,
        ];
    }

    protected function getFilterOptions(): array
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

    protected function getData(Request $request): array
    {
        $sortField = $request->input('sort', 'created_at');
        $sortDirection = $request->input('direction', 'desc');
        $perPage = $request->input('per_page', 12);

        // Validate sort parameters
        $allowedSortFields = ['created_at', 'status', 'url'];
        $sortField = in_array($sortField, $allowedSortFields) ? $sortField : 'created_at';
        $sortDirection = in_array($sortDirection, ['asc', 'desc']) ? $sortDirection : 'desc';

        Log::info('Landings page request parameters', [
            'user_id' => $request->user()->id,
            'sort_field' => $sortField,
            'sort_direction' => $sortDirection,
            'per_page' => $perPage,
            'request_params' => $request->all()
        ]);


        $userId = $request->user()->id;

        if ($userId) {
            $this->antiFloodService->check($userId);
        }

        $landings = WebsiteDownloadMonitor::where('user_id', $userId)
            ->orderBy($sortField, $sortDirection)
            ->with('user')
            ->whereNotIn('status', ['cancelled'])
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
        }) ?? $sortOptions[0];

        // Find the selected per-page option
        $selectedPerPage = collect($perPageOptions)->firstWhere('value', $perPage) ?? $perPageOptions[0];

        return [
            'landings' => $landings,
            'sortOptions' => $sortOptions,
            'perPageOptions' => $perPageOptions,
            'paginationOptions' => $perPageOptions,
            'currentSort' => $selectedSort,
            'currentPerPage' => $selectedPerPage,
            'selectedSort' => $selectedSort,
            'selectedPerPage' => $selectedPerPage,
            'sortOptionsPlaceholder' => $sortOptionsPlaceholder,
            'perPageOptionsPlaceholder' => $perPageOptionsPlaceholder,
            'filters' => [
                'sort' => $sortField,
                'direction' => $sortDirection,
                'per_page' => $perPage,
            ],
        ];
    }

    protected function renderContentWrapperView(array $data): string
    {
        $viewConfig = $this->getViewConfig();
        return view('pages.landings._content_wrapper', [
            'landings' => $data['landings'],
            'sortOptions' => $data['sortOptions'],
            'paginationOptions' => $data['paginationOptions'],
            'currentSort' => $data['currentSort'],
            'currentPerPage' => $data['currentPerPage'],
            'viewConfig' => $viewConfig,
        ])->render();
    }
}

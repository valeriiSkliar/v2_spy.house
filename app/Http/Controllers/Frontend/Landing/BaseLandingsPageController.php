<?php

namespace App\Http\Controllers\Frontend\Landing;

use App\Http\Controllers\FrontendController;
use App\Services\App\AntiFloodService;
use App\Services\App\Landings\LandingDownloadService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

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
}

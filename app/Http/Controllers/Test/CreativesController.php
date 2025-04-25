<?php

namespace App\Http\Controllers\Test;

use App\Http\Controllers\Controller;

use Illuminate\Http\Request;

class CreativesController extends Controller
{
    /**
     * Display the creatives page with different layouts based on the type
     *
     * @param Request $request
     * @return \Illuminate\View\View
     */
    public function index(Request $request)
    {
        $activeTab = $request->get('type', 'push');

        // Validate that the tab is one of the allowed values
        if (!in_array($activeTab, ['push', 'facebook', 'tiktok', 'inpage'])) {
            $activeTab = 'push';
        }

        // Get counts for each creative type (in a real app, these would come from the database)
        $counts = [
            'push' => '170k',
            'inpage' => '3.1k',
            'facebook' => '65.1k',
            'tiktok' => '45.2m',
        ];

        return view('creatives.index', [
            'activeTab' => $activeTab,
            'counts' => $counts
        ]);
    }
}

<?php

namespace App\Http\Controllers\Frontend\Creatives;

use App\Http\Controllers\FrontendController;
use Illuminate\Http\Request;

class CreativesController extends FrontendController
{
    public function index(Request $request)
    {
        $filters = [
            'searchKeyword' => request()->get('search', ''),
            'country' => request()->get('country', 'All Categories'),
            'onlyAdult' => request()->get('adult', 'false'),
        ];

        return view('pages.creatives.index', [
            'activeTab' => 'push',
            'filters' => $filters,
        ]);
    }
}

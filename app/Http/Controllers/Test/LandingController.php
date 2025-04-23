<?php

namespace App\Http\Controllers\Test;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class LandingController extends Controller
{
    /**
     * Display a listing of landings.
     */
    public function index()
    {
        // Здесь будет логика получения лендингов из базы данных
        $hasLandings = true; // Это можно менять в зависимости от наличия лендингов

        if ($hasLandings) {
            return view('landings.index');
        } else {
            return view('landings.empty');
        }
    }
}

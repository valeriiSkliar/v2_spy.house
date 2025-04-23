<?php

namespace App\Http\Controllers\Test;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class ApiController extends Controller
{
    /**
     * Display API documentation.
     */
    public function index()
    {
        return view('api.index');
    }
}

<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Test\API\BaseTokenController;

Route::get('/test-base-token', [BaseTokenController::class, 'testBaseToken'])
    ->name('test-base-token')
    ->middleware('auth:sanctum');

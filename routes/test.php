<?php

use App\Http\Controllers\Test\API\BaseTokenController;
use Illuminate\Support\Facades\Route;

Route::get('/test-base-token', [BaseTokenController::class, 'testBaseToken'])
    ->name('test-base-token')
    ->middleware('auth:sanctum');

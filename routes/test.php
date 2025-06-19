<?php

use App\Http\Controllers\Auth\EmailVerificationController;
use App\Http\Controllers\Test\API\BaseTokenController;
use Illuminate\Support\Facades\Route;

Route::get('/test-base-token', [BaseTokenController::class, 'testBaseToken'])
    ->name('test-base-token')
    ->middleware('auth:sanctum');

// Simple endpoint for tests
Route::post('email/verify', [EmailVerificationController::class, 'verifySimple'])
    ->middleware(['throttle:6,1'])
    ->name('verification.verify.simple');

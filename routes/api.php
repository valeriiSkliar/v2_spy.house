<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\TokenController;
use App\Http\Controllers\Test\API\BaseTokenController;
use Illuminate\Support\Facades\Route;

// API routes - prefix 'api' is automatically added by RouteServiceProvider
// Auth routes
Route::post('login', [AuthController::class, 'login'])->name('api.login');
Route::post('auth/refresh', [AuthController::class, 'refreshToken'])->name('api.auth.refresh');

// Token refresh endpoint - works with cookies and without authentication
Route::post('auth/refresh-token', [TokenController::class, 'refreshToken'])->name('api.auth.refresh-token');

// Protected routes - works with both web session auth and API tokens
Route::middleware(['auth:sanctum'])->group(function () {
    // Auth API endpoint
    Route::post('auth/logout', [AuthController::class, 'logout'])->name('api.logout');
    Route::get('user', [AuthController::class, 'user'])->name('api.user');

    // Token management
    Route::post('tokens/create', [TokenController::class, 'createToken'])->name('api.tokens.create');
    Route::get('tokens', [TokenController::class, 'listTokens'])->name('api.tokens.list');
    Route::post('tokens/revoke', [TokenController::class, 'revokeToken'])->name('api.tokens.revoke');

    // Test
    Route::get('test-api-token2', [BaseTokenController::class, 'testBaseToken2'])->name('test-base-token2');
});

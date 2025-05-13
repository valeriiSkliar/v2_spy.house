<?php

use App\Http\Controllers\Frontend\Service\ServiceRedirectController;
use App\Http\Controllers\Frontend\Service\ServicesController;
use Illuminate\Support\Facades\Route;

Route::middleware('web')
    ->prefix('services')
    ->group(function () {
        Route::get('/', [ServicesController::class, 'index'])->name('services.index');
        Route::get('/{id}', [ServicesController::class, 'show'])->name('services.show');
        // Route::post('/{id}/rate', [ServicesController::class, 'rate'])->name('services.rate');
        Route::get('/redirect/{service}', [ServiceRedirectController::class, 'redirect'])->name('services.redirect');
        // Route::get('/reset', [ServicesController::class, 'reset'])->name('services.reset');
    });

// API routes
Route::prefix('api')
    ->middleware('auth:sanctum', 'check.abilities:read:public') // Using read:public which is available in both basic and advanced tokens
    ->group(function () {
        Route::get('/services/{id}/rate/{rating}', [ServicesController::class, 'rate'])->name('services.rate');
    });

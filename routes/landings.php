<?php

use App\Http\Controllers\App\WebsiteDownloadController;
use App\Http\Controllers\Frontend\Landing\LandingsPageController;
use Illuminate\Support\Facades\Route;

// Web Routes
Route::middleware(['web', 'auth'])
    ->prefix('landings')
    ->name('landings.')
    ->group(function () {
        Route::get('/', [LandingsPageController::class, 'index'])->name('index');
        Route::delete('/{landing}', [LandingsPageController::class, 'destroy'])->name('destroy');
        Route::get('/{landing}/download', [LandingsPageController::class, 'download'])->name('download');
    });


// API Routes
Route::middleware(['auth:sanctum', 'check.abilities:read:base-token'])
    ->prefix('api/landings')
    ->name('landings.')
    ->group(function () {
        Route::get('/{monitor}/status', [WebsiteDownloadController::class, 'getStatus'])->name('status');
    });



Route::get('/website-downloads', [WebsiteDownloadController::class, 'index'])->name('website-downloads.index');
Route::post('/website-downloads', [WebsiteDownloadController::class, 'store'])->name('website-downloads.store');
Route::get('/website-downloads/{monitor}', [WebsiteDownloadController::class, 'show'])->name('website-downloads.show');
Route::delete('/website-downloads/{monitor}', [WebsiteDownloadController::class, 'destroy'])->name('website-downloads.destroy');

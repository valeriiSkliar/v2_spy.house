<?php

use App\Http\Controllers\Test\CreativesController;
use Illuminate\Support\Facades\Route;

Route::prefix('creatives')
    ->name('creatives.')
    ->group(function () {
        Route::get('/', [CreativesController::class, 'index'])->name('index');
    });

Route::prefix('api/creatives')
    ->name('api.creatives.')
    ->group(function () {
        Route::get('/tab-counts', [CreativesController::class, 'tabCounts'])->name('tabCounts');
    });

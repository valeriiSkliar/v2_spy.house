<?php

use App\Http\Controllers\Frontend\Creatives\CreativesController;
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
        Route::get('/filters/validate', [CreativesController::class, 'validateFilters'])->name('validateFilters');
        // Route::get('/filter-options', [CreativesController::class, 'getFilterOptionsApi'])->name('filterOptions');
        Route::get('/search-count', [CreativesController::class, 'getSearchCountApi'])->name('searchCount');
        Route::get('/', [CreativesController::class, 'apiIndex'])->name('index');

        // API для избранного
        Route::get('/favorites/count', [CreativesController::class, 'getFavoritesCount'])->name('favorites.count');
        Route::post('/{id}/favorite', [CreativesController::class, 'addToFavorites'])->name('favorites.add');
        Route::delete('/{id}/favorite', [CreativesController::class, 'removeFromFavorites'])->name('favorites.remove');
    });

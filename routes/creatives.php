<?php

use App\Http\Controllers\Frontend\Creatives\CreativesController;
use App\Http\Controllers\Frontend\FavoriteController;
use Illuminate\Support\Facades\Route;

Route::prefix('creatives')
    ->name('creatives.')
    ->middleware(['throttle:10,1', 'auth'])
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

        // API для деталей креативов
        Route::get('/{id}/details', [CreativesController::class, 'getCreativeDetails'])->name('details');

        // API для избранного
        Route::middleware('auth')->group(function () {
            Route::get('/favorites/count', [CreativesController::class, 'getFavoritesCount'])->name('favorites.count');
            Route::get('/favorites/ids', [FavoriteController::class, 'ids'])->name('favorites.ids');
            Route::get('/{id}/favorite/status', [CreativesController::class, 'getFavoriteStatus'])->name('favorites.status');
            Route::post('/{id}/favorite', [CreativesController::class, 'addToFavorites'])->name('favorites.add');
            Route::delete('/{id}/favorite', [CreativesController::class, 'removeFromFavorites'])->name('favorites.remove');
        });
    });

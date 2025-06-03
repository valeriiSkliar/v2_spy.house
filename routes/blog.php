<?php

use App\Http\Controllers\Frontend\Blog\BlogController;
use Illuminate\Support\Facades\Route;

Route::prefix('blog')->name('blog.')->group(function () {
    Route::get('/', [BlogController::class, 'index'])->name('index');
    Route::get('/search', [BlogController::class, 'search'])->name('search');
    Route::get('/category/{slug}', [BlogController::class, 'byCategory'])->name('category');
    Route::get('/{slug}', [BlogController::class, 'show'])->name('show');
});

Route::prefix('blog')->name('blog.')->middleware(['auth', 'throttle:10,1'])->group(function () {
    Route::post('/{slug}/comment', [BlogController::class, 'storeComment'])->name('comment.store');
    Route::post('/{slug}/rate', [BlogController::class, 'rateArticle'])->name('rate');
});

Route::prefix('blog')->name('blog.')->middleware(['throttle:20,1'])->group(function () {
    Route::get('/{slug}/comments', [BlogController::class, 'paginateComments'])->name('comments.paginate');
});

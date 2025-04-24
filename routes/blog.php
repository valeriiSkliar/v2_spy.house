<?php

use App\Http\Controllers\Test\BlogController;
use Illuminate\Support\Facades\Route;

Route::prefix('blog')->name('blog.')->group(function () {
    Route::get('/', [BlogController::class, 'index'])->name('index');
    Route::get('/category/{slug}', [BlogController::class, 'category'])->name('category');
    Route::get('/search', [BlogController::class, 'search'])->name('search');
    Route::get('/{slug}', [BlogController::class, 'show'])->name('show');
    Route::post('/{slug}/comment', [BlogController::class, 'storeComment'])->name('comment.store');
    Route::get('/{slug}/reply/{comment_id}', [BlogController::class, 'reply'])->name('reply');
    Route::post('/{slug}/reply', [BlogController::class, 'storeReply'])->name('reply.store');
    Route::post('/{slug}/rate', [BlogController::class, 'rateArticle'])->name('rate');
});

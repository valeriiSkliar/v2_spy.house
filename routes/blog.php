<?php

use App\Http\Controllers\Api\Blog\ApiBlogController;
use App\Http\Controllers\Frontend\Blog\BlogController;
use Illuminate\Support\Facades\Route;

Route::prefix('blog')->name('blog.')->middleware('blog.validate.params')->group(function () {
    Route::get('/', [BlogController::class, 'index'])->name('index');
    Route::get('/category/{slug}', [BlogController::class, 'byCategory'])->name('category');
    Route::get('/{slug}', [BlogController::class, 'show'])->name('show');
});

Route::prefix('blog')->name('blog.')->middleware(['auth', 'throttle:10,1'])->group(function () {
    Route::post('/{slug}/rate', [BlogController::class, 'rateArticle'])->name('rate');
});

// API routes for blog
Route::prefix('api/blog')
    ->name('api.blog.')
    ->middleware(['web', 'blog.validate.params'])
    ->group(function () {
        Route::get('list', [ApiBlogController::class, 'ajaxList'])->name('list');
        Route::get('search', [ApiBlogController::class, 'search'])->name('search');
        Route::middleware(['auth:sanctum'])
            ->group(function () {
                Route::post('{slug}/comment', [ApiBlogController::class, 'storeComment'])->name('comment.store');
                Route::post('{slug}/reply', [ApiBlogController::class, 'storeReply'])->name('reply.store');
                Route::get('{slug}/reply/{comment_id}', [ApiBlogController::class, 'getReplyForm'])->name('get-reply-form');
                Route::get('{slug}/comments', [ApiBlogController::class, 'getComments'])->name('comments.get');
            });
    });

// Backward compatibility routes - redirect to API
Route::prefix('blog')->name('blog.')->group(function () {
    Route::post('/{slug}/comment', [ApiBlogController::class, 'storeComment'])->name('comment.store')->middleware(['auth', 'throttle:10,1']);
    Route::get('/{slug}/comments', [ApiBlogController::class, 'paginateComments'])->name('comments.paginate')->middleware(['throttle:20,1']);
});

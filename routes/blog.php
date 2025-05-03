<?php

use App\Http\Controllers\Frontend\Blog\BlogController;
use App\Http\Controllers\Test\BlogController as TestBlogController;
use Illuminate\Support\Facades\Route;

Route::prefix('blog')->name('blog.')->group(function () {
    Route::get('/', [BlogController::class, 'index'])->name('index');
    Route::get('/{slug}', [BlogController::class, 'show'])->name('show');
    Route::get('/category/{slug}', [BlogController::class, 'byCategory'])->name('category');
    Route::get('/search', [BlogController::class, 'search'])->name('search');
    Route::post('/{slug}/comment', [TestBlogController::class, 'storeComment'])->name('comment.store');
    Route::get('/{slug}/reply/{comment_id}', [TestBlogController::class, 'reply'])->name('reply');
    Route::post('/{slug}/reply', [TestBlogController::class, 'storeReply'])->name('reply.store');
    Route::post('/{slug}/rate', [TestBlogController::class, 'rateArticle'])->name('rate');
    // Route::get('/{slug}/comments', [BlogController::class, 'getComments'])->name('comments.get');
});

Route::prefix('blog')->name('blog.')->middleware(['auth'])->group(function () {
    Route::post('/{slug}/comment', [BlogController::class, 'storeComment'])->name('comment.store');
    Route::post('/{slug}/rate', [BlogController::class, 'rateArticle'])->name('rate');
});
Route::get('/blog/{slug}/comments', [BlogController::class, 'paginateComments'])->name('blog.comments.paginate');

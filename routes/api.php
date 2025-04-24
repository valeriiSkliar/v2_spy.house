<?php

use App\Http\Controllers\Api\BlogController;
use Illuminate\Support\Facades\Route;

Route::prefix('api')->name('api.')->group(function () {
    Route::get('/blog/search', [BlogController::class, 'search'])->name('blog.search');
    Route::post('/blog/{slug}/comment', [BlogController::class, 'storeComment'])->name('blog.comment.store');
    Route::post('/blog/{slug}/reply', [BlogController::class, 'storeReply'])->name('blog.reply.store');
    Route::get('/blog/{slug}/reply/{comment_id}', [BlogController::class, 'getReplyForm'])->name('blog.get-reply-form');
});

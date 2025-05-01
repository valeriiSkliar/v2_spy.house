<?php

use App\Http\Controllers\Api\Blog\ApiBlogController;
use Illuminate\Support\Facades\Route;

Route::prefix('api')->name('api.')->group(function () {
    Route::get('/blog/search', [ApiBlogController::class, 'search'])->name('blog.search');
    Route::post('/blog/{slug}/comment', [ApiBlogController::class, 'storeComment'])->name('blog.comment.store');
    Route::post('/blog/{slug}/reply', [ApiBlogController::class, 'storeReply'])->name('blog.reply.store');
    Route::get('/blog/{slug}/reply/{comment_id}', [ApiBlogController::class, 'getReplyForm'])->name('blog.get-reply-form');
});

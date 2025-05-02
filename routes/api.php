<?php

use App\Http\Controllers\Api\Blog\ApiBlogController;
use Illuminate\Support\Facades\Route;

Route::get('/blog/search', [ApiBlogController::class, 'search'])->name('api.blog.search');
Route::post('/blog/{slug}/comment', [ApiBlogController::class, 'storeComment'])->name('api.blog.comment.store');
Route::post('/blog/{slug}/reply', [ApiBlogController::class, 'storeReply'])->name('api.blog.reply.store');
Route::get('/blog/{slug}/reply/{comment_id}', [ApiBlogController::class, 'getReplyForm'])->name('api.blog.get-reply-form');
Route::get('/blog/{slug}/comments', [ApiBlogController::class, 'paginateComments'])->name('api.blog.comments.get');

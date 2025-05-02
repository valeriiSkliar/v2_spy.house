<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\Blog\ApiBlogController;
use Illuminate\Support\Facades\Route;


// Auth routes
// Route::post('/login', [AuthController::class, 'login'])->name('api.login');

// Public routes
Route::get('/blog/search', [ApiBlogController::class, 'search'])->name('api.blog.search');

// Protected routes - works with both web session auth and API tokens
Route::middleware(['auth:sanctum'])->group(function () {
    // Auth
    // Route::post('/logout', [AuthController::class, 'logout'])->name('api.logout');
    Route::get('/user', [AuthController::class, 'user'])->name('api.user');

    // Blog
    Route::post('/blog/{slug}/comment', [ApiBlogController::class, 'storeComment'])->name('api.blog.comment.store');
    Route::post('/blog/{slug}/reply', [ApiBlogController::class, 'storeReply'])->name('api.blog.reply.store');
    Route::get('/blog/{slug}/reply/{comment_id}', [ApiBlogController::class, 'getReplyForm'])->name('api.blog.get-reply-form');
    Route::get('/blog/{slug}/comments', [ApiBlogController::class, 'paginateComments'])->name('api.blog.comments.get');
});

<?php

use App\Http\Controllers\Api\Blog\ApiBlogController;
use App\Http\Controllers\Frontend\Blog\BlogController;
use Illuminate\Support\Facades\Route;

// ========== WEB ROUTES - ТОЛЬКО ДЛЯ ОТОБРАЖЕНИЯ ==========

Route::prefix('blog')->name('blog.')->middleware('blog.validate.params')->group(function () {
    Route::get('/', [BlogController::class, 'index'])->name('index');
    Route::get('/category/{slug}', [BlogController::class, 'byCategory'])->name('category');
    Route::get('/{slug}', [BlogController::class, 'show'])->name('show');
});

// Web роуты требующие авторизации
Route::prefix('blog')->name('blog.')->middleware(['auth', 'throttle:10,1'])->group(function () {
    Route::post('/{slug}/rate', [BlogController::class, 'rateArticle'])->name('rate');
});

// ========== API ROUTES - ТОЛЬКО ДЛЯ AJAX ==========

Route::prefix('api/blog')
    ->name('api.blog.')
    ->middleware(['web', 'blog.validate.params'])
    ->group(function () {

        // Публичные API endpoints
        Route::get('list', [ApiBlogController::class, 'ajaxList'])->name('list');
        Route::get('search', [ApiBlogController::class, 'search'])->name('search');
        Route::get('{slug}/comments', [ApiBlogController::class, 'getComments'])->name('comments.get');

        // API endpoints требующие авторизации
        Route::middleware(['auth:sanctum', 'throttle:10,1'])->group(function () {
            Route::post('{slug}/comment', [ApiBlogController::class, 'storeComment'])->name('comment.store');
            Route::post('{slug}/reply', [ApiBlogController::class, 'storeReply'])->name('reply.store');
            Route::get('{slug}/reply/{comment_id}', [ApiBlogController::class, 'getReplyForm'])->name('get-reply-form');
        });

        // Совместимость со старым форматом (deprecated - будет удален в будущих версиях)
        Route::middleware(['throttle:20,1'])->group(function () {
            Route::get('{slug}/comments/paginate', [ApiBlogController::class, 'paginateComments'])->name('comments.paginate');
        });
    });

// ========== BACKWARD COMPATIBILITY ROUTES ==========
// Эти роуты обеспечивают совместимость со старыми view файлами  (have errors durring commit submit)

Route::prefix('blog')->name('blog.')->group(function () {
    // Роут для формы комментариев (используется в reply-form.blade.php)
    Route::post('/{slug}/comment', [ApiBlogController::class, 'storeComment'])
        ->name('comment.store')
        ->middleware(['auth', 'throttle:10,1']);

    // Роут для пагинации комментариев (используется в старых AJAX запросах)
    Route::get('/{slug}/comments', [ApiBlogController::class, 'paginateComments'])
        ->name('comments.paginate')
        ->middleware(['throttle:20,1']);
});

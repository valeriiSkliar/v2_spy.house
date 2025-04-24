<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Test\LandingController;
use App\Http\Controllers\Test\NotificationController;
use App\Http\Controllers\Test\ApiController;
use App\Http\Controllers\Test\FinanceController;
use App\Http\Controllers\BlogController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('index');
});

Route::get('/blog', [BlogController::class, 'index'])->name('blog.index');
Route::get('/blog/{slug}', [BlogController::class, 'show'])->name('blog.show');
Route::get('/blog/category/{slug}', [BlogController::class, 'category'])->name('blog.category');
Route::post('/blog/{articleId}/comments', [BlogController::class, 'storeComment'])->name('blog.comments.store');
Route::post('/blog/{slug}/rate', [BlogController::class, 'rateArticle'])->name('blog.rate');


Route::get('/landings', [LandingController::class, 'index'])->name('landings.index');
Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');
Route::post('/notifications/mark-all-read', [NotificationController::class, 'markAllAsRead'])->name('notifications.markAllAsRead');
Route::post('/notifications/{id}/mark-read', [NotificationController::class, 'markAsRead'])->name('notifications.markAsRead');
Route::get('/api', [ApiController::class, 'index'])->name('api.index');
Route::get('/finances', [FinanceController::class, 'index'])->name('finances.index');
Route::post('/finances/deposit', [FinanceController::class, 'deposit'])->name('finances.deposit');

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__ . '/auth.php';

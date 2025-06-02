<?php

use App\Http\Controllers\Frontend\ModalController;
use App\Http\Controllers\LanguageController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Test\ApiController;
use App\Http\Controllers\Test\CreativesController;
use App\Http\Controllers\Test\FinanceController;
use App\Http\Controllers\UnsubscribeController;
// use App\Http\Controllers\UnsubscribeController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('index', [
        'user' => auth(),
    ]);
})->name('home');

Route::get('/terms', function () {
    return view('pages.terms');
})->name('terms');

Route::get('/modal/{type}', [ModalController::class, 'loadModal'])->name('modal.load');

Route::get('/api', [ApiController::class, 'index'])->name('api.index');
Route::get('/finances', [FinanceController::class, 'index'])->name('finances.index');
Route::post('/finances/deposit', [FinanceController::class, 'deposit'])->name('finances.deposit');

// Route::get('/dashboard', function () {
//     return view('dashboard');
// })->middleware(['auth', 'verified'])->name('dashboard');

// Route::middleware('auth')->group(function () {
//     Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
//     Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
//     Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
// });

// Creatives
Route::get('/creatives', [CreativesController::class, 'index'])->name('creatives.index');

Route::get('/language/{locale}', [LanguageController::class, 'switch'])->name('language.switch');

// Маршруты для отписки от рассылки
Route::get('/unsubscribe/{hash}', [UnsubscribeController::class, 'show'])
    ->name('unsubscribe.show');

Route::post('/unsubscribe/{hash}', [UnsubscribeController::class, 'unsubscribe'])
    ->name('unsubscribe.process');

Route::get('/unsubscribe-success', [UnsubscribeController::class, 'success'])
    ->name('unsubscribe.success');

require __DIR__ . '/auth.php';
// API routes are included directly in api.php with proper prefixing
include __DIR__ . '/api.php';
require __DIR__ . '/blog.php';
require __DIR__ . '/profile.php';
require __DIR__ . '/tariffs.php';
require __DIR__ . '/landings.php';
require __DIR__ . '/notifications.php';
require __DIR__ . '/services.php';
require __DIR__ . '/test.php';

<?php

use App\Http\Controllers\Frontend\LandingsPageController;
use App\Http\Controllers\Frontend\ModalController;
use App\Http\Controllers\LanguageController;
use App\Http\Controllers\Test\CreativesController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Test\NotificationController;
use App\Http\Controllers\Test\ApiController;
use App\Http\Controllers\Test\FinanceController;
use App\Http\Controllers\Test\BlogController;
use App\Http\Controllers\Test\TariffController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Test\ServicesController;
use Illuminate\Support\Facades\Session;

Route::get('/', function () {
    return view('index', [
        'user' => auth(),
    ]);
});

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

require __DIR__ . '/auth.php';
require __DIR__ . '/api.php';
require __DIR__ . '/blog.php';
require __DIR__ . '/profile.php';
require __DIR__ . '/tariffs.php';
require __DIR__ . '/landings.php';
require __DIR__ . '/notifications.php';
require __DIR__ . '/services.php';
require __DIR__ . '/test.php';

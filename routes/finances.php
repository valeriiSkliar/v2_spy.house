<?php

use App\Finance\Http\Controllers\FinanceController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth')
    ->prefix('finances')
    ->name('finances.')
    ->group(function () {
        Route::get('/', [FinanceController::class, 'index'])->name('index');
        Route::post('/deposit', [FinanceController::class, 'deposit'])->name('deposit');
    });


// Public API routes with web middleware and auth
Route::middleware(['web', 'auth'])->group(function () {
    Route::get('finances/list', [FinanceController::class, 'ajaxList'])->name('api.finances.list');
});

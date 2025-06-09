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

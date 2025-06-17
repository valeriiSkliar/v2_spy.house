<?php

use App\Finance\Http\Controllers\FinanceController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth')
    ->prefix('finances')
    ->name('finances.')
    ->group(function () {
        Route::get('/', [FinanceController::class, 'index'])->name('index');
        Route::get('/deposit', [FinanceController::class, 'depositForm'])->name('deposit.form');
        Route::post('/deposit', [FinanceController::class, 'deposit'])->name('deposit');
        Route::post('/validate-deposit', [FinanceController::class, 'validateDeposit'])->name('validate-deposit');
        Route::get('/deposit/success', [FinanceController::class, 'depositSuccess'])->name('deposit.success');
        Route::get('/deposit/cancel', [FinanceController::class, 'depositCancel'])->name('deposit.cancel');
    });

// Продолжение депозита (без auth middleware, используем invoice_number)
Route::get('/deposit/continue/{invoice_number}', [FinanceController::class, 'continueDeposit'])->name('deposit.continue');

// Public API routes with web middleware and auth
Route::middleware(['web', 'auth'])->group(function () {
    Route::get('finances/list', [FinanceController::class, 'ajaxList'])->name('api.finances.list');
});

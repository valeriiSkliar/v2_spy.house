<?php

use App\Finance\Http\Controllers\TariffController;
use Illuminate\Support\Facades\Route;

// Возврат с платежной системы (без auth middleware)
Route::get('/tariffs/payment/success', [TariffController::class, 'paymentSuccess'])->name('tariffs.payment.success');
Route::get('/tariffs/payment/cancel', [TariffController::class, 'paymentCancel'])->name('tariffs.payment.cancel');

// Тарифы
Route::middleware('auth')->group(function () {
    Route::get('/tariffs', [TariffController::class, 'index'])->name('tariffs.index');
    Route::get('/tariffs/payment/{slug}', [TariffController::class, 'payment'])->name('tariffs.payment');
    Route::post('/tariffs/process-payment', [TariffController::class, 'processPayment'])->name('tariffs.process-payment');
});

// API роуты для AJAX
Route::prefix('api')->middleware('auth')->group(function () {
    Route::get('/tariffs/payments', [TariffController::class, 'ajaxPayments'])->name('api.tariffs.payments');
});

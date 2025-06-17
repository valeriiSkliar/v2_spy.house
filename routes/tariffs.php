<?php

use App\Finance\Http\Controllers\TariffController;
use Illuminate\Support\Facades\Route;

// Возврат с платежной системы (без auth middleware)
Route::get('/tariffs/payment/success', [TariffController::class, 'paymentSuccess'])->name('tariffs.payment.success');
Route::get('/tariffs/payment/cancel', [TariffController::class, 'paymentCancel'])->name('tariffs.payment.cancel');

// Продолжение платежа (без auth middleware, используем invoice_number)
Route::get('/payment/continue/{invoice_number}', [TariffController::class, 'continuePayment'])->name('payment.continue');

// Тарифы
Route::middleware('auth')->group(function () {
    Route::get('/tariffs', [TariffController::class, 'index'])->name('tariffs.index');

    // Красивый URL для страницы оплаты тарифа
    Route::get('/tariffs/payment/{slug}/{billingType}', [TariffController::class, 'payment'])
        ->name('tariffs.payment')
        ->where('billingType', 'month|year');

    Route::post('/tariffs/validate-payment', [TariffController::class, 'validatePayment'])->name('tariffs.validate-payment');
    Route::post('/tariffs/process-payment', [TariffController::class, 'processPayment'])->name('tariffs.process-payment');
});

// API роуты для AJAX
Route::prefix('api')->middleware('auth')->group(function () {
    Route::get('/tariffs/payments', [TariffController::class, 'ajaxPayments'])->name('api.tariffs.payments');
    Route::get('/tariffs/pending-payments', [TariffController::class, 'getPendingPayments'])->name('api.tariffs.pending-payments');
});

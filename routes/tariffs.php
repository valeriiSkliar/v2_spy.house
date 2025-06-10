<?php

use App\Finance\Http\Controllers\TariffController;
use Illuminate\Support\Facades\Route;

// Тарифы
Route::middleware('auth')->group(function () {
    Route::get('/tariffs', [TariffController::class, 'index'])->name('tariffs.index');
    Route::get('/tariffs/payment/{slug}', [TariffController::class, 'payment'])->name('tariffs.payment');
    Route::post('/tariffs/process-payment', [TariffController::class, 'processPayment'])->name('tariffs.process-payment');
});

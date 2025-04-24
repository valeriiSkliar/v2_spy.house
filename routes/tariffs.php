<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Test\TariffController;
// Тарифы
Route::middleware('auth')->group(function () {
    Route::get('/tariffs', [TariffController::class, 'index'])->name('tariffs.index');
    Route::get('/tariffs/payment/{slug}', [TariffController::class, 'payment'])->name('tariffs.payment');
    Route::post('/tariffs/process-payment', [TariffController::class, 'processPayment'])->name('tariffs.process-payment');
});

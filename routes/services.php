<?php

use App\Http\Controllers\Frontend\Service\ServicesController;
use Illuminate\Support\Facades\Route;

Route::middleware('web')->group(function () {
    Route::get('/services', [ServicesController::class, 'index'])->name('services.index');
    Route::get('/services/{id}', [ServicesController::class, 'show'])->name('services.show');
    Route::post('/services/{id}/rate', [ServicesController::class, 'rate'])->name('services.rate');
});

<?php

use App\Finance\Http\Controllers\Pay2WebhookController;
use Illuminate\Support\Facades\Route;

// Pay2.House webhook (без middleware auth, так как это внешний вызов)
Route::post('/api/pay2/webhook', [Pay2WebhookController::class, 'handle'])->name('api.pay2.webhook');

// Route::post('/webhooks/pay2house', [WebhookController::class, 'pay2house'])->name('webhooks.pay2house');

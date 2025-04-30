<?php

use App\Http\Controllers\Test\NotificationController;
use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'auth'])
    ->prefix('notifications')
    ->group(function () {
        Route::get('/', [NotificationController::class, 'index'])->name('notifications.index');
        Route::post('/mark-all-read', [NotificationController::class, 'markAllAsRead'])->name('notifications.markAllAsRead');
        Route::post('/{id}/mark-read', [NotificationController::class, 'markAsRead'])->name('notifications.markAsRead');
    });

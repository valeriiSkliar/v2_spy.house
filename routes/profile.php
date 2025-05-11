<?php

use App\Http\Controllers\Frontend\Profile\ProfileController;
use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'auth', 'auth:sanctum'])->group(function () {
    Route::get('/profile/settings', [ProfileController::class, 'settings'])->name('profile.settings');
    Route::put('/profile/settings', [ProfileController::class, 'updateSettings'])->name('profile.update-settings');
    Route::get('/profile/change-password', [ProfileController::class, 'changePassword'])->name('profile.change-password');
    Route::post('/profile/initiate-password-update', [ProfileController::class, 'initiatePasswordUpdate'])->name('profile.initiate-password-update');
    Route::post('/profile/confirm-password-update', [ProfileController::class, 'confirmPasswordUpdate'])->name('profile.confirm-password-update');
    Route::get('/profile/cancel-password-update', [ProfileController::class, 'cancelPasswordUpdate'])->name('profile.cancel-password-update');
    Route::get('/profile/change-email', [ProfileController::class, 'changeEmail'])->name('profile.change-email');
    Route::post('/profile/initiate-email-update', [ProfileController::class, 'initiateEmailUpdate'])->name('profile.initiate-email-update');
    Route::post('/profile/confirm-email-update', [ProfileController::class, 'confirmEmailUpdate'])->name('profile.confirm-email-update');
    Route::get('/profile/cancel-email-update', [ProfileController::class, 'cancelEmailUpdate'])->name('profile.cancel-email-update');
    Route::get('/profile/ip-restriction', [ProfileController::class, 'ipRestriction'])->name('profile.ip-restriction');
    Route::put('/profile/ip-restriction', [ProfileController::class, 'updateIpRestriction'])->name('profile.update-ip-restriction');
    Route::get('/profile/connect-2fa', [ProfileController::class, 'connect2fa'])->name('profile.connect-2fa');
    Route::post('/profile/connect-2fa', [ProfileController::class, 'store2fa'])->name('profile.store-2fa');
    Route::get('/profile/connect-pin', [ProfileController::class, 'connectPin'])->name('profile.connect-pin');
    Route::post('/profile/connect-pin', [ProfileController::class, 'storePin'])->name('profile.store-pin');
    Route::put('/profile/update-notifications', [ProfileController::class, 'updateNotifications'])->name('profile.update-notifications');
    Route::post('/profile/2fa/disable', [ProfileController::class, 'disable2fa'])->name('profile.disable-2fa');

    Route::get('/profile/personal-greeting', [ProfileController::class, 'personalGreeting'])->name('profile.personal-greeting');
    Route::post('/profile/initiate-personal-greeting-update', [ProfileController::class, 'initiatePersonalGreetingUpdate'])->name('profile.initiate-personal-greeting-update');
    Route::post('/profile/confirm-personal-greeting-update', [ProfileController::class, 'confirmPersonalGreetingUpdate'])->name('profile.confirm-personal-greeting-update');
    Route::get('/profile/cancel-personal-greeting-update', [ProfileController::class, 'cancelPersonalGreetingUpdate'])->name('profile.cancel-personal-greeting-update');
});

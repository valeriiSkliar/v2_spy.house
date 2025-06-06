<?php

use App\Http\Controllers\Api\Profile\ProfileAvatarController;
use App\Http\Controllers\Api\Profile\ProfileSettingsController;
use App\Http\Controllers\Frontend\Profile\ProfileController;
use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'auth', 'auth:sanctum', 'verified:verification.notice'])->group(function () {
    Route::get('/profile/settings', [ProfileController::class, 'settings'])->name('profile.settings');
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
    Route::get('/profile/connect-2fa-step2', [ProfileController::class, 'connect2faStep2'])->name('profile.connect-2fa-step2');
    Route::post('/profile/connect-2fa', [ProfileController::class, 'store2fa'])->name('profile.store-2fa');
    Route::post('/profile/regenerate-2fa-secret', [ProfileController::class, 'regenerate2faSecret'])->name('profile.regenerate-2fa-secret');
    Route::get('/profile/connect-2fa-step2-content', [ProfileController::class, 'getConnect2faStep2Content'])->name('profile.connect-2fa-step2-content');
    Route::post('/profile/store-2fa-ajax', [ProfileController::class, 'store2faAjax'])->name('profile.store-2fa-ajax');
    Route::get('/profile/connect-pin', [ProfileController::class, 'connectPin'])->name('profile.connect-pin');
    Route::post('/profile/connect-pin', [ProfileController::class, 'storePin'])->name('profile.store-pin');
    Route::put('/profile/update-notifications', [ProfileController::class, 'updateNotifications'])->name('profile.update-notifications');
    Route::get('/profile/2fa/disable', [ProfileController::class, 'disable2fa'])->name('profile.disable-2fa');
    Route::get('/profile/2fa/load-disable-form', [ProfileController::class, 'load2faDisableForm'])->name('profile.load-2fa-disable-form');
    Route::post('/profile/2fa/confirm-disable', [ProfileController::class, 'confirmDisable2fa'])->name('profile.confirm-disable-2fa');

    Route::get('/profile/personal-greeting', [ProfileController::class, 'personalGreeting'])->name('profile.personal-greeting');
    Route::post('/profile/initiate-personal-greeting-update', [ProfileController::class, 'initiatePersonalGreetingUpdate'])->name('profile.initiate-personal-greeting-update');
    Route::post('/profile/confirm-personal-greeting-update', [ProfileController::class, 'confirmPersonalGreetingUpdate'])->name('profile.confirm-personal-greeting-update');
    Route::get('/profile/cancel-personal-greeting-update', [ProfileController::class, 'cancelPersonalGreetingUpdate'])->name('profile.cancel-personal-greeting-update');
});

Route::middleware(['web', 'auth', 'auth:sanctum'])
    ->prefix('api')
    ->group(function () {
        Route::post('/profile/avatar', [ProfileAvatarController::class, 'upload'])->name('api.profile.avatar.upload');

        Route::post('/profile/initiate-password-update', [ProfileSettingsController::class, 'initiatePasswordUpdateApi'])
            ->name('api.profile.initiate-password-update');
        Route::post('/profile/confirm-password-update', [ProfileSettingsController::class, 'confirmPasswordUpdateApi'])
            ->name('api.profile.confirm-password-update');
        Route::get('/profile/cancel-password-update', [ProfileSettingsController::class, 'cancelPasswordUpdateApi'])
            ->name('api.profile.cancel-password-update');

        Route::put('/profile/settings', [ProfileSettingsController::class, 'updateSettingsApi'])
            ->name('api.profile.settings');

        Route::post('/profile/update-notifications', [ProfileSettingsController::class, 'updateNotificationsApi'])
            ->name('api.profile.update-notifications');

        Route::post('/profile/initiate-email-update', [ProfileSettingsController::class, 'initiateEmailUpdateApi'])
            ->name('api.profile.initiate-email-update');
        Route::post('/profile/confirm-email-update', [ProfileSettingsController::class, 'confirmEmailUpdateApi'])
            ->name('api.profile.confirm-email-update');
        Route::get('/profile/cancel-email-update', [ProfileSettingsController::class, 'cancelEmailUpdateApi'])
            ->name('api.profile.cancel-email-update');

        Route::post('/profile/initiate-personal-greeting-update', [ProfileSettingsController::class, 'initiatePersonalGreetingUpdateApi'])
            ->name('api.profile.initiate-personal-greeting-update');
        Route::post('/profile/confirm-personal-greeting-update', [ProfileSettingsController::class, 'confirmPersonalGreetingUpdateApi'])
            ->name('api.profile.confirm-personal-greeting-update');
        Route::get('/profile/cancel-personal-greeting-update', [ProfileSettingsController::class, 'cancelPersonalGreetingUpdateApi'])
            ->name('api.profile.cancel-personal-greeting-update');

        Route::post('/profile/update-ip-restriction', [ProfileSettingsController::class, 'updateIpRestrictionApi'])
            ->name('api.profile.update-ip-restriction');

        Route::post('/validate-login-unique', [ProfileSettingsController::class, 'validateLoginUnique'])
            ->name('api.validate-login-unique');
    });

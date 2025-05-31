<?php

use App\Http\Controllers\Profile\UnifiedProfileController;
use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'auth', 'auth:sanctum', 'verified:verification.notice'])
    ->prefix('profile')
    ->name('profile.')
    ->group(function () {

        // ==================== MAIN PAGES ====================
        Route::get('/', [UnifiedProfileController::class, 'index'])->name('index');
        Route::get('/settings', [UnifiedProfileController::class, 'index'])->name('settings'); // Backward compatibility

        // ==================== PERSONAL INFORMATION ====================
        Route::put('/', [UnifiedProfileController::class, 'updatePersonalInfo'])->name('update');

        // ==================== SECURITY PAGES ====================
        Route::prefix('security')->name('security.')->group(function () {
            // Password Management
            Route::get('/change-password', [UnifiedProfileController::class, 'changePasswordView'])->name('change-password');
            Route::post('/password/initiate', [UnifiedProfileController::class, 'initiatePasswordChange'])->name('password.initiate');
            Route::post('/password/confirm', [UnifiedProfileController::class, 'confirmPasswordChange'])->name('password.confirm');
            Route::get('/password/cancel', [UnifiedProfileController::class, 'cancelPasswordChange'])->name('password.cancel');

            // Email Management
            Route::get('/change-email', [UnifiedProfileController::class, 'changeEmailView'])->name('change-email');
            Route::post('/email/initiate', [UnifiedProfileController::class, 'initiateEmailChange'])->name('email.initiate');
            Route::post('/email/confirm', [UnifiedProfileController::class, 'confirmEmailChange'])->name('email.confirm');
            Route::get('/email/cancel', [UnifiedProfileController::class, 'cancelEmailChange'])->name('email.cancel');

            // IP Restrictions
            Route::get('/ip-restriction', [UnifiedProfileController::class, 'ipRestrictionView'])->name('ip-restriction');
            Route::put('/ip-restriction', [UnifiedProfileController::class, 'updateIpRestriction'])->name('ip-restriction.update');

            // Two-Factor Authentication
            Route::prefix('2fa')->name('2fa.')->group(function () {
                Route::get('/setup', [UnifiedProfileController::class, 'connect2faView'])->name('setup');
                Route::post('/enable', [UnifiedProfileController::class, 'enable2FA'])->name('enable');
                Route::post('/confirm', [UnifiedProfileController::class, 'confirm2FA'])->name('confirm');
                Route::delete('/disable', [UnifiedProfileController::class, 'disable2FA'])->name('disable');
            });
        });

        // ==================== NOTIFICATIONS ====================
        Route::put('/notifications', [UnifiedProfileController::class, 'updateNotifications'])->name('notifications.update');

        // ==================== PERSONAL GREETING ====================
        Route::prefix('greeting')->name('greeting.')->group(function () {
            Route::get('/', [UnifiedProfileController::class, 'personalGreetingView'])->name('index');
            Route::post('/initiate', [UnifiedProfileController::class, 'initiatePersonalGreetingUpdate'])->name('initiate');
            Route::post('/confirm', [UnifiedProfileController::class, 'confirmPersonalGreetingUpdate'])->name('confirm');
            Route::get('/cancel', [UnifiedProfileController::class, 'cancelPersonalGreetingUpdate'])->name('cancel');
        });

        // ==================== ACCOUNT DELETION ====================
        Route::delete('/', [UnifiedProfileController::class, 'destroy'])->name('destroy');

        // ==================== BACKWARD COMPATIBILITY ROUTES ====================
        // These routes maintain compatibility with existing URLs
        Route::get('/change-password', [UnifiedProfileController::class, 'changePasswordView'])->name('change-password');
        Route::post('/initiate-password-update', [UnifiedProfileController::class, 'initiatePasswordChange'])->name('initiate-password-update');
        Route::post('/confirm-password-update', [UnifiedProfileController::class, 'confirmPasswordChange'])->name('confirm-password-update');
        Route::get('/cancel-password-update', [UnifiedProfileController::class, 'cancelPasswordChange'])->name('cancel-password-update');

        Route::get('/change-email', [UnifiedProfileController::class, 'changeEmailView'])->name('change-email');
        Route::post('/initiate-email-update', [UnifiedProfileController::class, 'initiateEmailChange'])->name('initiate-email-update');
        Route::post('/confirm-email-update', [UnifiedProfileController::class, 'confirmEmailChange'])->name('confirm-email-update');
        Route::get('/cancel-email-update', [UnifiedProfileController::class, 'cancelEmailChange'])->name('cancel-email-update');

        Route::get('/ip-restriction', [UnifiedProfileController::class, 'ipRestrictionView'])->name('ip-restriction');
        Route::put('/ip-restriction', [UnifiedProfileController::class, 'updateIpRestriction'])->name('update-ip-restriction');

        Route::get('/connect-2fa', [UnifiedProfileController::class, 'connect2faView'])->name('connect-2fa');
        Route::post('/connect-2fa', [UnifiedProfileController::class, 'confirm2FA'])->name('store-2fa');
        Route::get('/2fa/disable', [UnifiedProfileController::class, 'disable2FA'])->name('disable-2fa');

        Route::get('/personal-greeting', [UnifiedProfileController::class, 'personalGreetingView'])->name('personal-greeting');
        Route::post('/initiate-personal-greeting-update', [UnifiedProfileController::class, 'initiatePersonalGreetingUpdate'])->name('initiate-personal-greeting-update');
        Route::post('/confirm-personal-greeting-update', [UnifiedProfileController::class, 'confirmPersonalGreetingUpdate'])->name('confirm-personal-greeting-update');
        Route::get('/cancel-personal-greeting-update', [UnifiedProfileController::class, 'cancelPersonalGreetingUpdate'])->name('cancel-personal-greeting-update');

        Route::put('/update-notifications', [UnifiedProfileController::class, 'updateNotifications'])->name('update-notifications');
    });

// ==================== API ROUTES ====================
Route::middleware(['web', 'auth', 'auth:sanctum'])
    ->prefix('api/profile')
    ->name('api.profile.')
    ->group(function () {

        // Personal Information
        Route::put('/settings', [UnifiedProfileController::class, 'updatePersonalInfo'])->name('settings');

        // Password Management
        Route::post('/initiate-password-update', [UnifiedProfileController::class, 'initiatePasswordChange'])->name('initiate-password-update');
        Route::post('/confirm-password-update', [UnifiedProfileController::class, 'confirmPasswordChange'])->name('confirm-password-update');
        Route::get('/cancel-password-update', [UnifiedProfileController::class, 'cancelPasswordChange'])->name('cancel-password-update');

        // Email Management
        Route::post('/initiate-email-update', [UnifiedProfileController::class, 'initiateEmailChange'])->name('initiate-email-update');
        Route::post('/confirm-email-update', [UnifiedProfileController::class, 'confirmEmailChange'])->name('confirm-email-update');
        Route::get('/cancel-email-update', [UnifiedProfileController::class, 'cancelEmailChange'])->name('cancel-email-update');

        // Personal Greeting
        Route::post('/initiate-personal-greeting-update', [UnifiedProfileController::class, 'initiatePersonalGreetingUpdate'])->name('initiate-personal-greeting-update');
        Route::post('/confirm-personal-greeting-update', [UnifiedProfileController::class, 'confirmPersonalGreetingUpdate'])->name('confirm-personal-greeting-update');
        Route::get('/cancel-personal-greeting-update', [UnifiedProfileController::class, 'cancelPersonalGreetingUpdate'])->name('cancel-personal-greeting-update');

        // IP Restrictions
        Route::post('/update-ip-restriction', [UnifiedProfileController::class, 'updateIpRestriction'])->name('update-ip-restriction');

        // Notifications
        Route::post('/update-notifications', [UnifiedProfileController::class, 'updateNotifications'])->name('update-notifications');

        // Utility
        Route::post('/validate-login-unique', [UnifiedProfileController::class, 'validateLoginUnique'])->name('validate-login-unique');

        // Avatar upload (keeping separate controller for file uploads)
        Route::post('/avatar', [\App\Http\Controllers\Api\Profile\ProfileAvatarController::class, 'upload'])->name('avatar.upload');
    });

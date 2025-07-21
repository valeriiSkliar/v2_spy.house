<?php

namespace App\Providers;

use App\View\Composers\ApiTokenComposer;
use App\View\Composers\BlogComposer;
use App\View\Composers\MainPageCommentsComposer;
use App\View\Composers\SubscriptionComposer;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\ServiceProvider;
use App\Services\CustomQRCodeService;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Регистрируем кастомный QR-код сервис для Google2FA
        $this->app->bind('app.qrcode.service', function ($app) {
            return new CustomQRCodeService();
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Force HTTPS scheme for URL generation
        // Always force HTTPS in production or when any HTTPS indicator is present
        if (
            config('app.env') === 'production' ||
            request()->isSecure() ||
            request()->header('X-Forwarded-Proto') === 'https' ||
            request()->header('HTTP_X_FORWARDED_PROTO') === 'https' ||
            isset($_SERVER['HTTPS']) ||
            isset($_SERVER['HTTP_X_FORWARDED_PROTO']) ||
            (config('app.url') && str_starts_with(config('app.url'), 'https://'))
        ) {
            URL::forceScheme('https');
        }

        // Переопределяем путь к языковым файлам
        // $this->loadTranslationsFrom(base_path('lang'), app()->getLocale());

        Paginator::defaultView('common.pagination.spy-pagination-default');

        Paginator::defaultSimpleView('common.pagination.spy-pagination-default');

        // Register view composers
        View::composer('layouts.app', ApiTokenComposer::class);
        View::composer('layouts.authorized', ApiTokenComposer::class);

        // Register subscription composer for home page
        View::composer('index', SubscriptionComposer::class);

        // Register blog composer for home page
        View::composer('index', BlogComposer::class);

        // Register main page comments composer for home page
        View::composer('index', MainPageCommentsComposer::class);

        // Настраиваем Google2FA для использования нашего кастомного QR-кода сервиса
        $this->app->resolving('pragmarx.google2fa', function ($google2fa, $app) {
            try {
                $customQRService = $app->make('app.qrcode.service');
                $google2fa->setQrCodeService($customQRService);
            } catch (\Exception $e) {
                Log::error('Failed to set custom QR service: ' . $e->getMessage());
                // Fallback to default service
            }
        });
    }
}

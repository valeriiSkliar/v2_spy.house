<?php

namespace App\Providers;

use App\View\Composers\ApiTokenComposer;
use App\View\Composers\BlogComposer;
use App\View\Composers\MainPageCommentsComposer;
use App\View\Composers\SubscriptionComposer;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
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
    }
}

<?php

namespace App\Providers;

use App\View\Composers\ApiTokenComposer;
use Illuminate\Pagination\Paginator;
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
        // Переопределяем путь к языковым файлам
        // $this->loadTranslationsFrom(base_path('lang'), app()->getLocale());

        Paginator::defaultView('common.pagination.spy-pagination-default');

        Paginator::defaultSimpleView('common.pagination.spy-pagination-default');

        // Register view composers
        View::composer('layouts.app', ApiTokenComposer::class);
        View::composer('layouts.authorized', ApiTokenComposer::class);
    }
}

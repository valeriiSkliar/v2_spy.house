<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Pagination\Paginator;

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
    }
}

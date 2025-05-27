<?php

namespace App\Providers;

use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    protected $listen = [
        // Убираем автоматическую отправку стандартного письма верификации
        // так как используется кастомная система с кодами
    ];

    public function boot(): void
    {
        //
    }
}

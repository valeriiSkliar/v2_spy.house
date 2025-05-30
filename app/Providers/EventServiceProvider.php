<?php

namespace App\Providers;

use App\Events\User\UserRegistered;
use App\Events\User\AccountConfirmationCodeRequested;
use App\Events\User\EmailVerified;
use App\Events\User\EmailUpdated;
use App\Events\User\PasswordChanged;
use App\Listeners\Notifications\UserNotificationListener;
use App\Listeners\Notifications\NotificationMetricsListener;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    /**
     * Карта событий и их слушателей
     */
    protected $listen = [
        // События пользователя
        UserRegistered::class => [
            UserNotificationListener::class . '@handle',
            NotificationMetricsListener::class . '@handle',
        ],

        AccountConfirmationCodeRequested::class => [
            UserNotificationListener::class . '@handle',
            NotificationMetricsListener::class . '@handle',
        ],

        EmailVerified::class => [
            UserNotificationListener::class . '@handle',
            NotificationMetricsListener::class . '@handle',
        ],

        EmailUpdated::class => [
            UserNotificationListener::class . '@handle',
            NotificationMetricsListener::class . '@handle',
        ],

        PasswordChanged::class => [
            UserNotificationListener::class . '@handle',
            NotificationMetricsListener::class . '@handle',
        ],

        // Системные события Laravel
        \Illuminate\Auth\Events\Registered::class => [
            // Можем добавить дополнительные слушатели для стандартного события
        ],

        \Illuminate\Auth\Events\Verified::class => [
            // Слушатели для стандартного события верификации
        ],
    ];

    /**
     * Регистрация любых событий для вашего приложения
     */
    public function boot(): void
    {
        parent::boot();

        // Дополнительная настройка событий
    }

    /**
     * Определить, должны ли события и слушатели автоматически обнаруживаться
     */
    public function shouldDiscoverEvents(): bool
    {
        return false; // Отключено для предотвращения дублирования
    }
}

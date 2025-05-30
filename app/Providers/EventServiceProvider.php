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
            UserNotificationListener::class . '@handleUserRegistered',
            NotificationMetricsListener::class,
        ],

        AccountConfirmationCodeRequested::class => [
            UserNotificationListener::class . '@handleAccountConfirmationCodeRequested',
            NotificationMetricsListener::class,
        ],

        EmailVerified::class => [
            UserNotificationListener::class . '@handleEmailVerified',
            NotificationMetricsListener::class,
        ],

        EmailUpdated::class => [
            UserNotificationListener::class . '@handleEmailUpdated',
            NotificationMetricsListener::class,
        ],

        PasswordChanged::class => [
            UserNotificationListener::class . '@handlePasswordChanged',
            NotificationMetricsListener::class,
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
        return false; // Используем явное определение для лучшего контроля
    }
}

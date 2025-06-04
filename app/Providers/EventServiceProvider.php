<?php

namespace App\Providers;

use App\Events\User\AccountConfirmationCodeRequested;
use App\Events\User\EmailUpdated;
use App\Events\User\EmailVerified;
use App\Events\User\PasswordChanged;
use App\Events\User\UserRegistered;
use App\Listeners\DebugRegistrationListener;
use App\Listeners\Notifications\AccountConfirmationCodeRequestedListener;
use App\Listeners\Notifications\EmailUpdatedListener;
use App\Listeners\Notifications\EmailVerifiedListener;
use App\Listeners\Notifications\NotificationMetricsListener;
use App\Listeners\Notifications\PasswordChangedListener;
use App\Listeners\Notifications\UserRegisteredListener;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    /**
     * Карта событий и их слушателей
     */
    protected $listen = [
        // События пользователя
        UserRegistered::class => [
            // UserRegisteredListener::class,
            // NotificationMetricsListener::class . '@handle',
        ],

        AccountConfirmationCodeRequested::class => [
            AccountConfirmationCodeRequestedListener::class,
            NotificationMetricsListener::class.'@handle',
        ],

        EmailVerified::class => [
            EmailVerifiedListener::class,
            NotificationMetricsListener::class.'@handle',
        ],

        EmailUpdated::class => [
            EmailUpdatedListener::class,
            NotificationMetricsListener::class.'@handle',
        ],

        PasswordChanged::class => [
            PasswordChangedListener::class,
            NotificationMetricsListener::class.'@handle',
        ],

        // Системные события Laravel
        \Illuminate\Auth\Events\Registered::class => [
            // DebugRegistrationListener::class,
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

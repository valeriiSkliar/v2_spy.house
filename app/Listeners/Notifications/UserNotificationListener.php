<?php

namespace App\Listeners\Notifications;

use App\Enums\Frontend\NotificationType;
use App\Events\User\UserRegistered;
use App\Events\User\AccountConfirmationCodeRequested;
use App\Events\User\EmailVerified;
use App\Events\User\EmailUpdated;
use App\Events\User\PasswordChanged;
use App\Notifications\Auth\WelcomeNotification;
use App\Notifications\Auth\VerifyEmailNotification;
use App\Notifications\Profile\EmailUpdatedNotification;
use App\Services\Notification\NotificationDispatcher;
use Illuminate\Support\Facades\Log;

/**
 * Слушатель для отправки уведомлений при событиях пользователя
 */
class UserNotificationListener
{
    /**
     * Обработка событий пользователя
     */
    public function handle($event): void
    {
        $eventName = class_basename($event);

        switch ($eventName) {
            case 'UserRegistered':
                $this->processUserRegistered($event);
                break;
            case 'AccountConfirmationCodeRequested':
                $this->handleAccountConfirmationCodeRequested($event);
                break;
            case 'EmailVerified':
                $this->handleEmailVerified($event);
                break;
            case 'EmailUpdated':
                $this->handleEmailUpdated($event);
                break;
            case 'PasswordChanged':
                $this->handlePasswordChanged($event);
                break;
        }
    }

    /**
     * Обработка события регистрации пользователя
     */
    public function processUserRegistered(UserRegistered $event): void
    {
        Log::info('Processing UserRegistered event', [
            'user_id' => $event->user->id,
            'email' => $event->user->email
        ]);

        // Отправляем уведомление о необходимости подтверждения email
        if (!$event->user->hasVerifiedEmail()) {
            $event->user->sendEmailVerificationNotification();
        }

        // Отправляем приветственное уведомление
        $event->user->sendWelcomeNotification();
    }

    /**
     * Обработка запроса кода подтверждения
     */
    public function handleAccountConfirmationCodeRequested(AccountConfirmationCodeRequested $event): void
    {
        Log::info('Processing AccountConfirmationCodeRequested event', [
            'user_id' => $event->user->id,
            'code_length' => strlen($event->code)
        ]);

        // Код отправки обрабатывается в VerifyEmailNotification
        // Здесь можем добавить дополнительную логику, например:
        // - Логирование попыток
        // - Отправка SMS как дополнительный канал
        // - Уведомление администраторов о частых запросах
    }

    /**
     * Обработка подтверждения email
     */
    public function handleEmailVerified(EmailVerified $event): void
    {
        Log::info('Processing EmailVerified event', [
            'user_id' => $event->user->id
        ]);

        // Отправляем уведомление об успешном подтверждении
        NotificationDispatcher::quickSend(
            $event->user,
            NotificationType::EMAIL_VERIFIED,
            [],
            __('profile.success.email_verified'),
            __('profile.success.email_verified_message')
        );
    }

    /**
     * Обработка смены email
     */
    public function handleEmailUpdated(EmailUpdated $event): void
    {
        Log::info('Processing EmailUpdated event', [
            'user_id' => $event->user->id,
            'old_email' => $event->oldEmail,
            'new_email' => $event->newEmail
        ]);

        // Уведомление пользователю
        NotificationDispatcher::quickSend(
            $event->user,
            NotificationType::EMAIL_VERIFIED,
            [
                'old_email' => $event->oldEmail,
                'new_email' => $event->newEmail,
            ],
            __('profile.success.email_updated'),
            __('profile.success.email_updated_message', [
                'old_email' => $event->oldEmail,
                'new_email' => $event->newEmail,
            ])
        );

        // Уведомление на старый email
        NotificationDispatcher::sendTo(
            'mail',
            $event->oldEmail,
            new EmailUpdatedNotification($event->oldEmail, $event->newEmail)
        );
    }

    /**
     * Обработка смены пароля
     */
    public function handlePasswordChanged(PasswordChanged $event): void
    {
        Log::info('Processing PasswordChanged event', [
            'user_id' => $event->user->id
        ]);

        // Уведомление об успешной смене пароля
        NotificationDispatcher::quickSend(
            $event->user,
            NotificationType::PASSWORD_CHANGED,
            [],
            __('profile.security_settings.password_updated_success_title'),
            __('profile.security_settings.password_updated_success_message')
        );
    }
}

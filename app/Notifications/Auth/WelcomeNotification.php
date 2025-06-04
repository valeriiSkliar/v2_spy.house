<?php

namespace App\Notifications\Auth;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Log;

class WelcomeNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct()
    {
        //
    }

    /**
     * Get the notification's delivery channels.
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        // Сохраняем текущую локаль
        $currentLocale = App::getLocale();

        // Устанавливаем предпочитаемую локаль пользователя или дефолтную
        $userLocale = $notifiable->preferred_locale ?? config('app.locale', 'en');
        App::setLocale($userLocale);

        Log::debug('Sending welcome email', [
            'notification_class' => get_class($this),
            'user_id' => $notifiable->id ?? null,
            'email' => $notifiable->email,
            'template' => 'welcome',
            'subject' => __('emails.welcome.subject'),
            'user_locale' => $userLocale,
            'current_locale' => $currentLocale,
        ]);

        $mailMessage = (new MailMessage)
            ->subject(__('emails.welcome.subject'))
            ->view('emails.welcome', [
                'username' => $notifiable->login,
                'user' => $notifiable,
                'dashboardUrl' => config('app.url').'/profile/settings',
                'loginUrl' => config('app.url').'/login',
                'telegramUrl' => config('app.telegram_url', 'https://t.me/spyhouse'),
                'supportEmail' => config('mail.support_email', 'support@spy.house'),
                'unsubscribeUrl' => $notifiable->unsubscribe_hash
                    ? route('unsubscribe.show', $notifiable->unsubscribe_hash)
                    : config('app.url').'/unsubscribe',
            ]);

        // Восстанавливаем исходную локаль
        App::setLocale($currentLocale);

        return $mailMessage;
    }
}

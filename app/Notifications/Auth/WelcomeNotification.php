<?php

namespace App\Notifications\Auth;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
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
        Log::info('Sending welcome email', [
            'notification_class' => get_class($this),
            'user_id' => $notifiable->id ?? null,
            'email' => $notifiable->email,
            'template' => 'welcome',
            'subject' => 'Welcome to Partners.House!'
        ]);

        return (new MailMessage)
            ->subject('Welcome to Partners.House!')
            ->view('emails.welcome', [
                'username' => $notifiable->login,
                'user' => $notifiable,
                'dashboardUrl' => config('app.url') . '/profile/settings',
                'loginUrl' => config('app.url') . '/login',
                'telegramUrl' => config('app.telegram_url', 'https://t.me/spyhouse'),
                'supportEmail' => config('mail.support_email', 'support@spy.house'),
                'unsubscribeUrl' => config('app.url') . '/unsubscribe'
            ]);
    }
}

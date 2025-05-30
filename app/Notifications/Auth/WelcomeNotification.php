<?php

namespace App\Notifications\Auth;

use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;

class WelcomeNotification extends Notification
{
    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via($notifiable): array
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Welcome to Partners.House!')
            ->view('emails.welcome', [
                'username' => $notifiable->login,
                'loginUrl' => config('app.url') . '/login',
                'dashboardUrl' => config('app.url') . '/dashboard',
                'telegramUrl' => config('app.telegram_url', 'https://t.me/spyhouse'),
                'supportEmail' => config('mail.support_email', 'support@spy.house'),
                'unsubscribeUrl' => config('app.url') . '/unsubscribe'
            ]);
    }
}

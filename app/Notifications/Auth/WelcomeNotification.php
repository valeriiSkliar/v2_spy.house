<?php

namespace App\Notifications\Auth;

use App\Services\EmailService;
use App\Models\EmailLog;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\Facades\Log;

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
        Log::info('WelcomeNotification toMail() called', [
            'user_id' => $notifiable->id,
            'email' => $notifiable->email,
            'username' => $notifiable->login
        ]);

        // Параметры для шаблона
        $mailData = [
            'username' => $notifiable->login,
            'loginUrl' => config('app.url') . '/login',
            'dashboardUrl' => config('app.url') . '/dashboard',
            'telegramUrl' => config('app.telegram_url', 'https://t.me/spyhouse'),
            'supportEmail' => config('mail.support_email', 'support@spy.house'),
            'unsubscribeUrl' => config('app.url') . '/unsubscribe'
        ];

        try {
            $emailService = app(EmailService::class);
            $result = $emailService->send(
                $notifiable->email,
                'Welcome to Partners.House!',
                'welcome',
                $mailData
            );

            // Логируем результат отправки
            EmailLog::create([
                'email' => $notifiable->email,
                'subject' => 'Welcome to Partners.House!',
                'template' => 'welcome',
                'status' => $result ? 'sent' : 'failed',
                'sent_at' => $result ? now() : null
            ]);

            if (!$result) {
                Log::error('Failed to send welcome email', [
                    'user_id' => $notifiable->id,
                    'email' => $notifiable->email
                ]);
            }
        } catch (\Exception $e) {
            // Логируем ошибку
            EmailLog::create([
                'email' => $notifiable->email,
                'subject' => 'Welcome to Partners.House!',
                'template' => 'welcome',
                'status' => 'failed',
                'sent_at' => null
            ]);

            Log::error('Exception while sending welcome email', [
                'user_id' => $notifiable->id,
                'email' => $notifiable->email,
                'error' => $e->getMessage()
            ]);
        }

        // Возвращаем MailMessage с данными для кастомного шаблона
        return (new MailMessage)
            ->subject('Welcome to Partners.House!')
            ->view('emails.welcome', $mailData);
    }
}

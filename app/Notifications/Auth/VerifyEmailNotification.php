<?php

namespace App\Notifications\Auth;

use App\Mail\VerificationAccountEmail;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\Facades\Cache;
use App\Services\EmailService;
use App\Models\EmailLog;
use Illuminate\Support\Facades\Log;

class VerifyEmailNotification extends VerifyEmail
{
    /**
     * Get the mail representation of the notification.
     */
    public function toMail($notifiable)
    {
        // Генерируем 6-значный числовой код
        $code = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        // Параметры для шаблона
        $loginUrl = config('app.url') . '/login';
        $telegramUrl = config('app.telegram_url', 'https://t.me/spyhouse');
        $supportEmail = config('mail.support_email', 'support@spy.house');
        $unsubscribeUrl = config('app.url') . '/unsubscribe';

        // Сохраняем код в кэш
        Cache::put('email_verification_code:' . $notifiable->id, $code, now()->addMinutes(15));

        try {
            $emailService = new EmailService();
            $result = $emailService->send(
                $notifiable->email,
                'Account Verification - Spy.House',
                'verification-account',
                [
                    'code' => $code,
                    'loginUrl' => $loginUrl,
                    'telegramUrl' => $telegramUrl,
                    'supportEmail' => $supportEmail,
                    'unsubscribeUrl' => $unsubscribeUrl
                ]
            );

            // Логируем результат отправки
            EmailLog::create([
                'email' => $notifiable->email,
                'subject' => 'Account Verification - Spy.House',
                'template' => 'verification-account',
                'status' => $result ? 'sent' : 'failed',
                'sent_at' => $result ? now() : null
            ]);

            if (!$result) {
                Log::error('Failed to send verification email', [
                    'user_id' => $notifiable->id,
                    'email' => $notifiable->email
                ]);
            }
        } catch (\Exception $e) {
            // Логируем ошибку
            EmailLog::create([
                'email' => $notifiable->email,
                'subject' => 'Account Verification - Spy.House',
                'template' => 'verification-account',
                'status' => 'failed',
                'sent_at' => null
            ]);

            Log::error('Exception while sending verification email', [
                'user_id' => $notifiable->id,
                'email' => $notifiable->email,
                'error' => $e->getMessage()
            ]);
        }

        // Возвращаем MailMessage с данными для кастомного шаблона
        return (new MailMessage)
            ->subject('Account Verification - Spy.House')
            ->view('emails.verification-account', [
                'code' => $code,
                'loginUrl' => $loginUrl,
                'telegramUrl' => $telegramUrl,
                'supportEmail' => $supportEmail,
                'unsubscribeUrl' => $unsubscribeUrl,
            ]);
    }
}

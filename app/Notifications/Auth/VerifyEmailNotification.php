<?php

namespace App\Notifications\Auth;

use App\Mail\VerificationAccountEmail;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\Facades\Cache;

class VerifyEmailNotification extends VerifyEmail
{
    /**
     * Get the mail representation of the notification.
     */
    public function toMail($notifiable)
    {
        $verificationUrl = $this->verificationUrl($notifiable);

        // Генерируем 6-значный числовой код
        $code = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        // Параметры для шаблона
        $loginUrl = config('app.url') . '/login';
        $telegramUrl = config('app.telegram_url', 'https://t.me/spyhouse');
        $supportEmail = config('mail.support_email', 'support@spy.house');
        $unsubscribeUrl = config('app.url') . '/unsubscribe';

        // Сохраняем код в кэш
        Cache::put('email_verification_code:' . $notifiable->id, $code, now()->addMinutes(15));

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

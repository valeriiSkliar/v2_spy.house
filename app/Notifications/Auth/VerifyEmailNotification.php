<?php

namespace App\Notifications\Auth;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\App;

class VerifyEmailNotification extends Notification implements ShouldQueue
{
    use Queueable;

    private string $code;

    public function __construct(?string $code = null)
    {
        // Генерируем код если не передан
        $this->code = $code ?: str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        Log::debug('VerifyEmailNotification created', [
            'code_length' => strlen($this->code)
        ]);
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

        // Сохраняем код в кэш при отправке
        Cache::put('email_verification_code:' . $notifiable->id, $this->code, now()->addMinutes(15));

        Log::debug('Verification code saved to cache', [
            'user_id' => $notifiable->id,
            'code_length' => strlen($this->code)
        ]);

        Log::debug('Sending verification email', [
            'notification_class' => get_class($this),
            'user_id' => $notifiable->id ?? null,
            'email' => $notifiable->email,
            'template' => 'verification-account',
            'subject' => __('emails.verification.subject'),
            'user_locale' => $userLocale,
            'current_locale' => $currentLocale
        ]);

        $mailMessage = (new MailMessage)
            ->subject(__('emails.verification.subject'))
            ->view('emails.verification-account', [
                'code' => $this->code,
                'user' => $notifiable,
                'loginUrl' => config('app.url') . '/login',
                'telegramUrl' => config('app.telegram_url', 'https://t.me/spyhouse'),
                'supportEmail' => config('mail.support_email', 'support@spy.house'),
                'unsubscribeUrl' => $notifiable->unsubscribe_hash
                    ? route('unsubscribe.show', $notifiable->unsubscribe_hash)
                    : config('app.url') . '/unsubscribe'
            ]);

        // Восстанавливаем исходную локаль
        App::setLocale($currentLocale);

        return $mailMessage;
    }
}

<?php

namespace App\Notifications\Auth;

use App\Enums\Frontend\NotificationType;
use App\Notifications\BaseNotification;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class VerifyEmailNotification extends BaseNotification
{
    private string $code;

    public function __construct(?string $code = null)
    {
        parent::__construct(NotificationType::EMAIL_VERIFICATION_REQUEST);

        // Генерируем код если не передан
        $this->code = $code ?: str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        Log::info('VerifyEmailNotification created', [
            'code_length' => strlen($this->code)
        ]);
    }

    protected function getEmailTemplate(): string
    {
        return 'verification-account';
    }

    protected function getEmailSubject(object $notifiable): string
    {
        return 'Account Verification - Spy.House';
    }

    protected function getEmailTemplateData(object $notifiable): array
    {
        // Сохраняем код в кэш при отправке
        Cache::put('email_verification_code:' . $notifiable->id, $this->code, now()->addMinutes(15));

        Log::info('Verification code saved to cache', [
            'user_id' => $notifiable->id,
            'code_length' => strlen($this->code)
        ]);

        return array_merge(parent::getEmailTemplateData($notifiable), [
            'code' => $this->code,
        ]);
    }

    protected function getTitle(object $notifiable): string
    {
        return __('auth.verify_email_title', [], 'Account Verification');
    }

    protected function getMessage(object $notifiable): string
    {
        return __('auth.verify_email_message', [], 'Please verify your email address to complete registration.');
    }

    protected function getIcon(): string
    {
        return 'mail';
    }

    protected function getAdditionalData(object $notifiable): array
    {
        return [
            'code' => $this->code,
            'expires_in' => 15, // минут
        ];
    }
}

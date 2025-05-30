<?php

namespace App\Notifications\Profile;

use App\Enums\Frontend\NotificationType;
use App\Models\EmailLog;
use App\Notifications\BaseNotification;
use App\Services\EmailService;
use Illuminate\Support\Facades\Log;

class EmailUpdateConfirmationNotification extends BaseNotification
{
    private string $code;

    public function __construct(string $code)
    {
        parent::__construct(NotificationType::EMAIL_VERIFIED);
        $this->code = $code;
    }

    public function toMail($notifiable)
    {
        Log::info('toMail', ['code' => $this->code]);
        $emailService = app(EmailService::class);

        $result = $emailService->send(
            $notifiable->email,
            $this->getTitle($notifiable),
            'verification-account',
            [
                'code' => $this->code,
                'loginUrl' => config('app.url') . '/login',
                'telegramUrl' => config('app.telegram_url', 'https://t.me/spyhouse'),
                'supportEmail' => config('mail.support_email', 'support@spy.house'),
                'unsubscribeUrl' => config('app.url') . '/unsubscribe'
            ]
        );

        // Логируем результат отправки
        EmailLog::create([
            'email' => $notifiable->email,
            'subject' => $this->getTitle($notifiable),
            'template' => 'verification-account',
            'status' => $result ? 'success' : 'failed',
            'sent_at' => $result ? now() : null
        ]);
    }

    protected function getTitle(object $notifiable): string
    {
        return __('profile.email_update.confirmation_title');
    }

    protected function getMessage(object $notifiable): string
    {
        return __('profile.email_update.confirmation_message');
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

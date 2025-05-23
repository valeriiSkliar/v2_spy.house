<?php

namespace App\Notifications\Profile;

use App\Enums\Frontend\NotificationType;
use App\Notifications\BaseNotification;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\Facades\Log;

class EmailUpdateConfirmationNotification extends BaseNotification
{
    private string $code;

    public function __construct(string $code)
    {
        parent::__construct(NotificationType::EMAIL_VERIFIED);
        $this->code = $code;
    }

    public function toMail($notifiable): MailMessage
    {
        Log::info('toMail', ['code' => $this->code]);

        $mailMessage = (new MailMessage)
            ->subject($this->getTitle($notifiable))
            ->line($this->getMessage($notifiable))
            ->line(__('profile.email_update.verification_code_label').': '.$this->code)
            ->line(__('profile.email_update.verification_expires'));

        Log::info('Mail content', [
            'subject' => $this->getTitle($notifiable),
            'message' => $this->getMessage($notifiable),
            'code' => $this->code,
            'expires' => __('profile.email_update.verification_expires'),
        ]);

        return $mailMessage;
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

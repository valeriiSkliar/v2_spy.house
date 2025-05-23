<?php

namespace App\Notifications\Profile;

use App\Enums\Frontend\NotificationType;
use App\Notifications\BaseNotification;
use Illuminate\Notifications\Messages\MailMessage;

class EmailUpdatedNotification extends BaseNotification
{
    private string $oldEmail;

    private string $newEmail;

    public function __construct(string $oldEmail, string $newEmail)
    {
        parent::__construct(NotificationType::EMAIL_VERIFIED);
        $this->oldEmail = $oldEmail;
        $this->newEmail = $newEmail;
    }

    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject($this->getTitle($notifiable))
            ->line($this->getMessage($notifiable));
    }

    protected function getTitle(object $notifiable): string
    {
        return __('profile.email_updated');
    }

    protected function getMessage(object $notifiable): string
    {
        return __('profile.email_updated_message', [
            'old_email' => $this->oldEmail,
            'new_email' => $this->newEmail,
        ]);
    }

    protected function getIcon(): string
    {
        return 'mail';
    }

    protected function getAdditionalData(object $notifiable): array
    {
        return [
            'old_email' => $this->oldEmail,
            'new_email' => $this->newEmail,
        ];
    }
}

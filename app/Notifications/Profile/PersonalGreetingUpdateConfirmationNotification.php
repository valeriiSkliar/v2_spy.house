<?php

namespace App\Notifications\Profile;

use App\Enums\Frontend\NotificationType;
use App\Notifications\BaseNotification;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\Facades\Lang;

class PersonalGreetingUpdateConfirmationNotification extends BaseNotification
{
    private string $code;

    /**
     * Create a new notification instance.
     */
    public function __construct(string $code)
    {
        parent::__construct(NotificationType::PROFILE_UPDATED);
        $this->code = $code;
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject($this->getTitle($notifiable))
            ->line($this->getMessage($notifiable))
            ->line(Lang::get('profile.personal_greeting_update.verification_code_label').': '.$this->code)
            ->line(Lang::get('profile.personal_greeting_update.verification_expires', ['minutes' => 15]));
    }

    protected function getTitle(object $notifiable): string
    {
        return Lang::get('profile.personal_greeting_update.confirmation_title');
    }

    protected function getMessage(object $notifiable): string
    {
        return Lang::get('profile.personal_greeting_update.confirmation_message');
    }

    protected function getIcon(): string
    {
        return 'user';
    }

    protected function getAdditionalData(object $notifiable): array
    {
        return [
            'verification_code' => $this->code,
            'expires_in' => 15,
        ];
    }
}

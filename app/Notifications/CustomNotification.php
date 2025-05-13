<?php

namespace App\Notifications;

use App\Enums\Frontend\NotificationType;
use Illuminate\Notifications\Messages\MailMessage;

class CustomNotification extends BaseNotification
{
    private array $data;
    private ?string $customTitle;
    private ?string $customMessage;

    public function __construct(NotificationType $type, array $data = [], ?string $title = null, ?string $message = null)
    {
        parent::__construct($type);
        $this->data = $data;
        $this->customTitle = $title;
        $this->customMessage = $message;
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject($this->getTitle($notifiable))
            ->line($this->getMessage($notifiable));
    }

    protected function getTitle(object $notifiable): string
    {
        return $this->customTitle ?? parent::getTitle($notifiable);
    }

    protected function getMessage(object $notifiable): string
    {
        return $this->customMessage ?? parent::getMessage($notifiable);
    }

    protected function getAdditionalData(object $notifiable): array
    {
        return $this->data;
    }
}

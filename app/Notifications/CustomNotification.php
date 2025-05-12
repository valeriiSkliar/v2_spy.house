<?php

namespace App\Notifications;

use App\Enums\Frontend\NotificationType;

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
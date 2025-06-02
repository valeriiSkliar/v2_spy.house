<?php

namespace App\Notifications;

use App\Enums\Frontend\NotificationType;
use App\Traits\HasNotificationType;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

/**
 * Custom notification for in-app notifications (database channel only)
 */
class CustomNotification extends Notification implements ShouldQueue
{
    use HasNotificationType, Queueable;

    private array $data;
    private ?string $customTitle;
    private ?string $customMessage;

    public function __construct(NotificationType $type, array $data = [], ?string $title = null, ?string $message = null)
    {
        $this->notificationType = $type;
        $this->data = $data;
        $this->customTitle = $title;
        $this->customMessage = $message;
    }

    /**
     * Get the notification's delivery channels.
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * Get the array representation of the notification for database storage.
     */
    public function toDatabase(object $notifiable): array
    {
        return [
            'title' => $this->getTitle($notifiable),
            'message' => $this->getMessage($notifiable),
            'type' => $this->getNotificationTypeKey(),
            'icon' => $this->getIcon(),
            'data' => $this->data,
        ];
    }

    protected function getTitle(object $notifiable): string
    {
        if ($this->customTitle) {
            return $this->customTitle;
        }

        $typeModel = $this->getNotificationTypeModel();
        return $typeModel ? $typeModel->name : 'Notification';
    }

    protected function getMessage(object $notifiable): string
    {
        if ($this->customMessage) {
            return $this->customMessage;
        }

        $typeModel = $this->getNotificationTypeModel();
        return $typeModel ? $typeModel->description : 'You have a new notification';
    }

    protected function getIcon(): string
    {
        return $this->getDefaultIcon();
    }
}

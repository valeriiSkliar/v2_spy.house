<?php

namespace App\Notifications;

use App\Enums\Frontend\NotificationType;
use App\Traits\HasNotificationType;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

abstract class BaseNotification extends Notification implements ShouldQueue
{
    use Queueable, HasNotificationType;

    /**
     * Create a new notification instance.
     */
    public function __construct(NotificationType $type)
    {
        $this->notificationType = $type;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return $this->resolveChannels($notifiable);
    }

    /**
     * Get the array representation of the notification.
     * This will be used for the database channel.
     *
     * @return array<string, mixed>
     */
    public function toDatabase(object $notifiable): array
    {
        return [
            'title' => $this->getTitle($notifiable),
            'message' => $this->getMessage($notifiable),
            'type' => $this->getNotificationTypeKey(),
            'icon' => $this->getIcon(),
            'data' => $this->getAdditionalData($notifiable),
        ];
    }

    /**
     * Get the title for this notification.
     * Override this method to customize the title.
     */
    protected function getTitle(object $notifiable): string
    {
        $typeModel = $this->getNotificationTypeModel();
        return $typeModel ? $typeModel->name : 'Notification';
    }

    /**
     * Get the message for this notification.
     * Override this method to customize the message.
     */
    protected function getMessage(object $notifiable): string
    {
        $typeModel = $this->getNotificationTypeModel();
        return $typeModel ? $typeModel->description : 'You have a new notification';
    }

    /**
     * Get the icon for this notification.
     * Override this method to customize the icon.
     */
    protected function getIcon(): string
    {
        return $this->getDefaultIcon();
    }

    /**
     * Get additional data for this notification.
     * Override this method to add custom data.
     */
    protected function getAdditionalData(object $notifiable): array
    {
        return [];
    }
}
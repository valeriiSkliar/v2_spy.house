<?php

namespace App\Notifications\Landings;

use App\Enums\Frontend\NotificationType;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Lang;
use Illuminate\Support\Facades\Log;

class WebsiteDownloadStatus extends Notification implements ShouldQueue
{
    use Queueable;

    private NotificationType $notificationType;

    /**
     * Create a new notification instance.
     */
    public function __construct(
        private readonly string $url,
        private readonly string $status,
        private readonly ?string $error = null
    ) {
        // Выбираем подходящий тип уведомления на основе статуса
        $this->notificationType = match ($status) {
            'started' => NotificationType::WEBSITE_DOWNLOAD_STARTED,
            'completed' => NotificationType::WEBSITE_DOWNLOAD_COMPLETED,
            'failed' => NotificationType::WEBSITE_DOWNLOAD_FAILED,
            default => NotificationType::WEBSITE_DOWNLOAD_STARTED,
        };
    }

    /**
     * Get the notification's delivery channels.
     */
    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        Log::info('Sending website download status email', [
            'notification_class' => get_class($this),
            'user_id' => $notifiable->id ?? null,
            'email' => $notifiable->email,
            'url' => $this->url,
            'status' => $this->status
        ]);

        $mailMessage = (new MailMessage)
            ->subject($this->getTitle($notifiable))
            ->line($this->getMessage($notifiable));

        if ($this->error && $this->status === 'failed') {
            $mailMessage->line(Lang::get('landings.download.error_details').': '.$this->error);
        }

        return $mailMessage;
    }

    /**
     * Get the array representation of the notification for database storage.
     */
    public function toDatabase(object $notifiable): array
    {
        return [
            'title' => $this->getTitle($notifiable),
            'message' => $this->getMessage($notifiable),
            'type' => $this->notificationType->value,
            'icon' => $this->getIcon(),
            'data' => $this->getAdditionalData($notifiable),
        ];
    }

    protected function getTitle(object $notifiable): string
    {
        return Lang::get('landings.download.status.'.$this->status.'.title', ['url' => $this->url]);
    }

    protected function getMessage(object $notifiable): string
    {
        return Lang::get('landings.download.status.'.$this->status.'.message', ['url' => $this->url]);
    }

    protected function getIcon(): string
    {
        return match ($this->status) {
            'started' => 'download',
            'completed' => 'check',
            'failed' => 'alert-triangle',
            default => 'download',
        };
    }

    protected function getAdditionalData(object $notifiable): array
    {
        $data = [
            'url' => $this->url,
            'status' => $this->status,
        ];

        if ($this->error) {
            $data['error'] = $this->error;
        }

        return $data;
    }
}

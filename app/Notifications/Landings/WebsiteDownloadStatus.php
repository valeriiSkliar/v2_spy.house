<?php

namespace App\Notifications\Landings;

use App\Enums\Frontend\NotificationType;
use App\Notifications\BaseNotification;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\Facades\Lang;

class WebsiteDownloadStatus extends BaseNotification
{
    /**
     * Create a new notification instance.
     */
    public function __construct(
        private readonly string $url,
        private readonly string $status,
        private readonly ?string $error = null
    ) {
        // Выбираем подходящий тип уведомления на основе статуса
        $type = match ($status) {
            'started' => NotificationType::WEBSITE_DOWNLOAD_STARTED,
            'completed' => NotificationType::WEBSITE_DOWNLOAD_COMPLETED,
            'failed' => NotificationType::WEBSITE_DOWNLOAD_FAILED,
            default => NotificationType::WEBSITE_DOWNLOAD_STARTED,
        };
        
        parent::__construct($type);
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $mailMessage = (new MailMessage)
            ->subject($this->getTitle($notifiable))
            ->line($this->getMessage($notifiable));
        
        if ($this->error && $this->status === 'failed') {
            $mailMessage->line(Lang::get('landings.download.error_details') . ': ' . $this->error);
        }
        
        return $mailMessage;
    }

    protected function getTitle(object $notifiable): string
    {
        return Lang::get('landings.download.status.' . $this->status . '.title', ['url' => $this->url]);
    }

    protected function getMessage(object $notifiable): string
    {
        return Lang::get('landings.download.status.' . $this->status . '.message', ['url' => $this->url]);
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
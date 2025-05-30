<?php

namespace App\Notifications;

use App\Enums\Frontend\NotificationType;
use App\Models\EmailLog;
use App\Services\EmailService;
use App\Traits\HasNotificationType;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Log;

abstract class BaseNotification extends Notification implements ShouldQueue
{
    use HasNotificationType, Queueable;

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
     * Get the mail representation of the notification.
     * Использует стандартный Laravel подход с возможностью логирования
     */
    public function toMail(object $notifiable): MailMessage
    {
        $templateData = $this->getEmailTemplateData($notifiable);
        $template = $this->getEmailTemplate();
        $subject = $this->getEmailSubject($notifiable);

        Log::info('Sending notification email', [
            'notification_class' => get_class($this),
            'user_id' => $notifiable->id ?? null,
            'email' => $notifiable->email,
            'template' => $template,
            'subject' => $subject
        ]);

        // Возвращаем стандартный MailMessage с view
        return (new MailMessage)
            ->subject($subject)
            ->view('emails.' . $template, $templateData);
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

    /**
     * Get the email template name.
     * Override this method to specify custom template.
     */
    protected function getEmailTemplate(): string
    {
        return 'notification'; // Default template
    }

    /**
     * Get the email subject.
     * Override this method to customize email subject.
     */
    protected function getEmailSubject(object $notifiable): string
    {
        return $this->getTitle($notifiable);
    }

    /**
     * Get the data for email template.
     * Override this method to provide template-specific data.
     */
    protected function getEmailTemplateData(object $notifiable): array
    {
        return [
            'title' => $this->getTitle($notifiable),
            'message' => $this->getMessage($notifiable),
            'user' => $notifiable,
            'loginUrl' => config('app.url') . '/login',
            'telegramUrl' => config('app.telegram_url', 'https://t.me/spyhouse'),
            'supportEmail' => config('mail.support_email', 'support@spy.house'),
            'unsubscribeUrl' => config('app.url') . '/unsubscribe'
        ];
    }
}

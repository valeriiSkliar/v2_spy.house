<?php

namespace App\Traits;

use App\Enums\Frontend\NotificationType;
use App\Models\NotificationType as NotificationTypeModel;
use App\Models\User;
use Illuminate\Support\Facades\Cache;

trait HasNotificationType
{
    /**
     * Тип этого уведомления
     */
    protected NotificationType $notificationType;

    /**
     * Получить тип уведомления для данного класса
     */
    public function getNotificationType(): NotificationType
    {
        return $this->notificationType;
    }

    /**
     * Получить ключ типа уведомления для данного класса
     */
    public function getNotificationTypeKey(): string
    {
        return $this->getNotificationType()->value;
    }

    /**
     * Получить модель типа уведомления из кэша или базы данных
     */
    public function getNotificationTypeModel(): ?NotificationTypeModel
    {
        $key = 'notification_type_'.$this->getNotificationTypeKey();

        return Cache::remember($key, 3600, function () {
            return NotificationTypeModel::where('key', $this->getNotificationTypeKey())->first();
        });
    }

    /**
     * Проверить, может ли пользователь настраивать этот тип уведомлений
     */
    public function isUserConfigurable(): bool
    {
        $model = $this->getNotificationTypeModel();

        return $model ? $model->is_user_configurable : true;
    }

    /**
     * Получить каналы по умолчанию для доставки уведомления
     */
    public function getDefaultChannels(): array
    {
        $model = $this->getNotificationTypeModel();

        return $model ? $model->default_channels : ['mail'];
    }

    /**
     * Определить, через какие каналы следует отправлять уведомление.
     * Учитывает настройки пользователя и каналы по умолчанию.
     */
    public function resolveChannels(object $notifiable): array
    {
        $defaultChannels = $this->getDefaultChannels();

        // Если это не пользователь или уведомление не настраиваемое, просто возвращаем каналы по умолчанию
        if (! ($notifiable instanceof User) || ! $this->isUserConfigurable()) {
            return $defaultChannels;
        }

        // Получаем настройки пользователя
        $userSettings = $notifiable->notification_settings ?? [];
        $typeKey = $this->getNotificationTypeKey();

        // Если настройки для этого типа уведомлений не указаны, используем каналы по умолчанию
        if (! isset($userSettings[$typeKey])) {
            return $defaultChannels;
        }

        // Если пользователь отключил уведомления этого типа, возвращаем пустой массив
        if ($userSettings[$typeKey] === false) {
            return [];
        }

        // Если пользователь указал конкретные каналы, используем их
        if (is_array($userSettings[$typeKey])) {
            return $userSettings[$typeKey];
        }

        // Если настройки есть, но в неизвестном формате, возвращаем каналы по умолчанию
        return $defaultChannels;
    }

    /**
     * Стандартный метод toDatabase, который можно переопределить в конкретных классах
     */
    public function toDatabase(object $notifiable): array
    {
        $typeModel = $this->getNotificationTypeModel();

        return [
            'title' => $typeModel ? $typeModel->name : 'Notification',
            'message' => $typeModel ? $typeModel->description : 'You have a new notification',
            'type' => $this->getNotificationTypeKey(),
            'icon' => $this->getDefaultIcon(),
            'data' => $this->getAdditionalData($notifiable),
        ];
    }

    /**
     * Получить дополнительные данные для уведомления, которые будут сохранены в JSON
     */
    protected function getAdditionalData(object $notifiable): array
    {
        return [];
    }

    /**
     * Получить иконку по умолчанию для этого типа уведомлений
     */
    protected function getDefaultIcon(): string
    {
        $type = $this->getNotificationType();

        // Определить иконку по категории уведомления
        $model = $this->getNotificationTypeModel();
        if ($model) {
            switch ($model->category) {
                case 'account':
                    return 'user';
                case 'content':
                    return 'comment';
                case 'landings':
                    return 'download';
                case 'billing':
                    return 'credit-card';
                case 'security':
                    return 'shield';
                case 'system':
                    return 'bell';
                default:
                    return 'info';
            }
        }

        return 'bell';
    }
}

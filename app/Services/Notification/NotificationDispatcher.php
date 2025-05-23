<?php

namespace App\Services\Notification;

use App\Enums\Frontend\NotificationType;
use App\Models\User;
use App\Notifications\CustomNotification;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Notification as NotificationFacade;

class NotificationDispatcher
{
    /**
     * Отправляет уведомление пользователю через все доступные каналы
     *
     * @param  User  $user  Пользователь, которому отправляется уведомление
     * @param  Notification  $notification  Экземпляр уведомления
     */
    public static function send(User $user, Notification $notification): void
    {
        $user->notify($notification);
    }

    /**
     * Создает и отправляет уведомление указанного класса пользователю
     *
     * @param  User  $user  Пользователь, которому отправляется уведомление
     * @param  string  $notificationClass  Класс уведомления для создания
     * @param  array  $constructorParams  Дополнительные параметры конструктора
     */
    public static function sendNotification(User $user, string $notificationClass, array $constructorParams = []): void
    {
        $notification = new $notificationClass(...$constructorParams);
        $user->notify($notification);
    }

    /**
     * Быстрое создание и отправка уведомления без создания отдельного класса
     *
     * @param  User  $user  Пользователь, которому отправляется уведомление
     * @param  NotificationType  $type  Тип уведомления
     * @param  array  $data  Дополнительные данные для уведомления
     * @param  string|null  $title  Пользовательский заголовок (опционально)
     * @param  string|null  $message  Пользовательское сообщение (опционально)
     */
    public static function quickSend(
        User $user,
        NotificationType $type,
        array $data = [],
        ?string $title = null,
        ?string $message = null
    ): void {
        $notification = new CustomNotification($type, $data, $title, $message);
        $user->notify($notification);
    }

    /**
     * Отправляет уведомление анонимному получателю (например, на email)
     *
     * @param  string  $channel  Канал отправки (mail, slack и т.д.)
     * @param  string  $recipient  Адрес получателя (email, webhook url и т.д.)
     * @param  Notification  $notification  Экземпляр уведомления
     */
    public static function sendTo(string $channel, string $recipient, Notification $notification): void
    {
        NotificationFacade::route($channel, $recipient)->notify($notification);
    }

    /**
     * Отправляет уведомление и пользователю, и на указанный адрес
     *
     * @param  User  $user  Пользователь, которому отправляется уведомление
     * @param  string  $email  Email для отправки копии
     * @param  Notification  $notification  Экземпляр уведомления
     */
    public static function sendWithCopy(User $user, string $email, Notification $notification): void
    {
        // Отправка пользователю
        self::send($user, $notification);

        // Отправка копии на указанный email
        self::sendTo('mail', $email, $notification);
    }

    /**
     * Отправляет уведомление на несколько адресов электронной почты
     *
     * @param  array  $emails  Массив адресов электронной почты
     * @param  Notification  $notification  Экземпляр уведомления
     */
    public static function sendToEmails(array $emails, Notification $notification): void
    {
        foreach ($emails as $email) {
            self::sendTo('mail', $email, $notification);
        }
    }
}

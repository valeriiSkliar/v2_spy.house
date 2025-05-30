# Notifications System - Quick Start

## Быстрое Создание Уведомления (5 минут)

### Шаг 1: Определить тип уведомления

```php
// app/Enums/Frontend/NotificationType.php
case YOUR_NOTIFICATION = 'your_notification';
```

### Шаг 2: Создать класс уведомления

```bash
php artisan make:notification YourNotification
```

### Шаг 3: Реализовать уведомление

```php
<?php

namespace App\Notifications;

use App\Services\EmailService;
use App\Models\EmailLog;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\Facades\Log;

class YourNotification extends Notification
{
    public function via($notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail($notifiable): MailMessage
    {
        $mailData = [
            'username' => $notifiable->login,
            // ... other data
        ];

        try {
            $emailService = app(EmailService::class);
            $result = $emailService->send(
                $notifiable->email,
                'Your Subject',
                'your-template', // resources/views/emails/your-template.blade.php
                $mailData
            );

            EmailLog::create([
                'email' => $notifiable->email,
                'subject' => 'Your Subject',
                'template' => 'your-template',
                'status' => $result ? 'sent' : 'failed',
                'sent_at' => $result ? now() : null
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to send notification', [
                'user_id' => $notifiable->id,
                'error' => $e->getMessage()
            ]);
        }

        return (new MailMessage)
            ->subject('Your Subject')
            ->view('emails.your-template', $mailData);
    }

    public function toArray($notifiable): array
    {
        return [
            'type' => 'your_notification',
            'title' => 'Your Title',
            'message' => 'Your message',
        ];
    }
}
```

### Шаг 4: Создать email шаблон

```blade
<!-- resources/views/emails/your-template.blade.php -->
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Your Subject</title>
</head>
<body>
    <h1>Hello, {{ $username }}!</h1>
    <p>Your message content here.</p>
</body>
</html>
```

### Шаг 5: Отправить уведомление

```php
use App\Services\Notification\NotificationDispatcher;

NotificationDispatcher::sendNotification(
    $user,
    YourNotification::class
);
```

## Частые Паттерны

### Уведомление с параметрами

```php
class ParametrizedNotification extends Notification
{
    public function __construct(
        private string $param1,
        private string $param2
    ) {}

    public function toMail($notifiable): MailMessage
    {
        $mailData = [
            'param1' => $this->param1,
            'param2' => $this->param2,
            // ...
        ];
        // ...
    }
}

// Использование
NotificationDispatcher::sendNotification(
    $user,
    ParametrizedNotification::class,
    ['value1', 'value2']
);
```

### Массовая рассылка

```php
User::chunk(100, function ($users) {
    foreach ($users as $user) {
        NotificationDispatcher::sendNotification(
            $user,
            YourNotification::class
        );
    }
});
```

### Быстрое уведомление без класса

```php
NotificationDispatcher::quickSend(
    $user,
    NotificationType::YOUR_NOTIFICATION,
    ['data' => 'value'],
    'Quick Title',
    'Quick message'
);
```

## Чек-лист

- [ ] Добавлен тип в NotificationType enum
- [ ] Создан класс уведомления
- [ ] Реализованы методы via(), toMail(), toArray()
- [ ] Создан email шаблон
- [ ] Добавлено логирование в EmailLog
- [ ] Добавлена обработка ошибок
- [ ] Протестирована отправка

## Тестирование

```bash
# Создать тестовую команду
php artisan make:command TestYourNotification

# Или использовать tinker
php artisan tinker
>>> $user = User::first();
>>> $user->notify(new YourNotification());

# Проверить логи
tail -f storage/logs/laravel.log

# Проверить EmailLog
>>> EmailLog::latest()->first();
```

## Troubleshooting

**Письмо не отправляется:**

- Проверьте EmailLog на ошибки
- Убедитесь что EmailService настроен
- Проверьте шаблон email на синтаксические ошибки

**Уведомление в приложении не появляется:**

- Убедитесь что в via() указан 'database'
- Проверьте что toArray() возвращает корректные данные
- Проверьте таблицу notifications в БД

**Ошибки в логах:**

- Проверьте что все необходимые переменные переданы в шаблон
- Убедитесь что шаблон существует в resources/views/emails/
- Проверьте синтаксис Blade шаблона

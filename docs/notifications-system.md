# Система Уведомлений

## Обзор Архитектуры

Система уведомлений построена на базе Laravel Notifications с кастомными расширениями для интеграции с внешними сервисами email рассылки (Resend).

### Основные Компоненты

1. **NotificationDispatcher** - центральный сервис для отправки уведомлений
2. **EmailService** - сервис для отправки email через Resend API
3. **Event Listeners** - слушатели событий для автоматической отправки уведомлений
4. **Notification Classes** - классы уведомлений для разных типов сообщений
5. **NotificationType Enum** - перечисление типов уведомлений
6. **EmailLog Model** - модель для логирования отправленных писем
7. **User Notification Settings** - настройки пользователей для управления уведомлениями

## Структура Файлов

```
app/
├── Enums/Frontend/NotificationType.php
├── Events/User/                     # События пользователей
├── Listeners/Notifications/         # Слушатели для уведомлений
├── Models/EmailLog.php
├── Notifications/
│   ├── Auth/                       # Уведомления аутентификации
│   ├── Profile/                    # Уведомления профиля
│   ├── BaseNotification.php
│   └── CustomNotification.php
├── Services/
│   ├── EmailService.php
│   └── Notification/NotificationDispatcher.php
└── Models/User.php                 # Настройки уведомлений пользователя

resources/views/emails/             # Шаблоны писем
```

## Типы Каналов Доставки

1. **mail** - Email уведомления через Laravel + EmailService/Resend
2. **database** - Уведомления в приложении (сохраняются в БД)
3. **Будущие каналы**: SMS, Push, Slack, Telegram

## Создание Нового Уведомления

### 1. Добавить Тип Уведомления

```php
// app/Enums/Frontend/NotificationType.php
enum NotificationType: string
{
    // ... existing types
    case NEW_FEATURE_ANNOUNCEMENT = 'new_feature_announcement';
}
```

### 2. Создать Класс Уведомления

```php
<?php
// app/Notifications/System/NewFeatureAnnouncementNotification.php

namespace App\Notifications\System;

use App\Services\EmailService;
use App\Models\EmailLog;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\Facades\Log;

class NewFeatureAnnouncementNotification extends Notification
{
    private string $featureName;
    private string $description;

    public function __construct(string $featureName, string $description)
    {
        $this->featureName = $featureName;
        $this->description = $description;
    }

    /**
     * Определяем каналы доставки
     */
    public function via($notifiable): array
    {
        return ['mail', 'database'];
    }

    /**
     * Email уведомление через EmailService
     */
    public function toMail($notifiable): MailMessage
    {
        $mailData = [
            'username' => $notifiable->login,
            'featureName' => $this->featureName,
            'description' => $this->description,
            'loginUrl' => config('app.url') . '/login',
            // ... other template variables
        ];

        try {
            $emailService = app(EmailService::class);
            $result = $emailService->send(
                $notifiable->email,
                "New Feature: {$this->featureName}",
                'new-feature-announcement',
                $mailData
            );

            // Логирование
            EmailLog::create([
                'email' => $notifiable->email,
                'subject' => "New Feature: {$this->featureName}",
                'template' => 'new-feature-announcement',
                'status' => $result ? 'sent' : 'failed',
                'sent_at' => $result ? now() : null
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to send new feature announcement', [
                'user_id' => $notifiable->id,
                'error' => $e->getMessage()
            ]);
        }

        return (new MailMessage)
            ->subject("New Feature: {$this->featureName}")
            ->view('emails.new-feature-announcement', $mailData);
    }

    /**
     * Уведомление в приложении
     */
    public function toArray($notifiable): array
    {
        return [
            'type' => 'new_feature_announcement',
            'title' => "New Feature: {$this->featureName}",
            'message' => $this->description,
            'feature_name' => $this->featureName,
            'user_id' => $notifiable->id,
        ];
    }
}
```

### 3. Создать Email Шаблон

```blade
<!-- resources/views/emails/new-feature-announcement.blade.php -->
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>New Feature - {{ $featureName }}</title>
</head>
<body>
    <h1>Hello, {{ $username }}!</h1>
    <p>We're excited to announce a new feature: <strong>{{ $featureName }}</strong></p>
    <p>{{ $description }}</p>
    <a href="{{ $loginUrl }}">Check it out now!</a>
</body>
</html>
```

## Создание Event Listener

### 1. Создать Event

```php
<?php
// app/Events/System/NewFeatureReleased.php

namespace App\Events\System;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class NewFeatureReleased
{
    use Dispatchable, SerializesModels;

    public string $featureName;
    public string $description;

    public function __construct(string $featureName, string $description)
    {
        $this->featureName = $featureName;
        $this->description = $description;
    }
}
```

### 2. Создать Listener

```php
<?php
// app/Listeners/Notifications/NewFeatureReleasedListener.php

namespace App\Listeners\Notifications;

use App\Events\System\NewFeatureReleased;
use App\Models\User;
use App\Notifications\System\NewFeatureAnnouncementNotification;
use App\Services\Notification\NotificationDispatcher;
use Illuminate\Support\Facades\Log;

class NewFeatureReleasedListener
{
    public function handle(NewFeatureReleased $event): void
    {
        Log::info('Processing NewFeatureReleased event', [
            'feature' => $event->featureName
        ]);

        // Отправляем всем активным пользователям
        User::whereNotNull('email_verified_at')
            ->chunk(100, function ($users) use ($event) {
                foreach ($users as $user) {
                    NotificationDispatcher::sendNotification(
                        $user,
                        NewFeatureAnnouncementNotification::class,
                        [$event->featureName, $event->description]
                    );
                }
            });
    }
}
```

### 3. Зарегистрировать в EventServiceProvider

```php
// app/Providers/EventServiceProvider.php
protected $listen = [
    NewFeatureReleased::class => [
        NewFeatureReleasedListener::class,
    ],
];
```

## Способы Отправки Уведомлений

### 1. Через NotificationDispatcher (Рекомендуется)

```php
use App\Services\Notification\NotificationDispatcher;
use App\Notifications\System\NewFeatureAnnouncementNotification;

// Отправка конкретному пользователю
NotificationDispatcher::sendNotification(
    $user,
    NewFeatureAnnouncementNotification::class,
    ['Feature Name', 'Description']
);

// Быстрое уведомление без создания класса
NotificationDispatcher::quickSend(
    $user,
    NotificationType::NEW_FEATURE_ANNOUNCEMENT,
    ['feature' => 'New Dashboard'],
    'New Feature Available',
    'Check out our new dashboard!'
);
```

### 2. Через События (Для автоматических уведомлений)

```php
use App\Events\System\NewFeatureReleased;

// Запускаем событие - все слушатели выполнятся автоматически
NewFeatureReleased::dispatch('New Dashboard', 'Improved user experience');
```

### 3. Прямая отправка (Не рекомендуется)

```php
$user->notify(new NewFeatureAnnouncementNotification('Feature', 'Description'));
```

## Управление Настройками Уведомлений

### Проверка Настроек Пользователя

```php
// Проверить включены ли уведомления типа
if ($user->hasNotificationEnabled(NotificationType::NEW_FEATURE_ANNOUNCEMENT)) {
    // Отправить уведомление
}

// Проверить конкретный канал
if ($user->hasNotificationChannelEnabled(NotificationType::NEW_FEATURE_ANNOUNCEMENT, 'mail')) {
    // Отправить email
}
```

### Настройка Пользовательских Предпочтений

```php
// Включить/отключить тип уведомлений
$user->setNotificationEnabled(NotificationType::NEW_FEATURE_ANNOUNCEMENT, true);

// Настроить каналы для типа
$user->setNotificationChannels(NotificationType::NEW_FEATURE_ANNOUNCEMENT, ['mail', 'database']);
```

## Best Practices

### 1. Структура Уведомлений

- **Группируйте по папкам**: Auth/, Profile/, System/, Billing/ и т.д.
- **Наследуйтесь от BaseNotification** для общей логики
- **Используйте говорящие имена**: WelcomeNotification, PasswordChangedNotification

### 2. Email Шаблоны

- **Единый стиль**: используйте общие layout'ы
- **Мобильная адаптивность**: все шаблоны должны корректно отображаться на мобильных
- **Переменные в шаблонах**: передавайте всю необходимую информацию

### 3. Логирование

- **Всегда логируйте** отправку в EmailLog
- **Обрабатывайте ошибки** и логируйте исключения
- **Используйте структурированные логи** с контекстом

### 4. Производительность

- **Используйте queues** для массовых рассылок
- **Chunk пользователей** при отправке всем
- **Кэшируйте** настройки типов уведомлений

### 5. Тестирование

```php
// Создайте тестовую команду для проверки
php artisan make:command TestNotification
```

## Добавление Нового Канала Доставки

### 1. Создать Custom Channel

```php
<?php
// app/Notifications/Channels/TelegramChannel.php

namespace App\Notifications\Channels;

use Illuminate\Notifications\Notification;

class TelegramChannel
{
    public function send($notifiable, Notification $notification): void
    {
        if (!method_exists($notification, 'toTelegram')) {
            return;
        }

        $message = $notification->toTelegram($notifiable);

        // Логика отправки в Telegram
    }
}
```

### 2. Зарегистрировать Channel

```php
// app/Providers/AppServiceProvider.php
use Illuminate\Notifications\ChannelManager;
use App\Notifications\Channels\TelegramChannel;

public function boot()
{
    $this->app->make(ChannelManager::class)->extend('telegram', function () {
        return new TelegramChannel();
    });
}
```

### 3. Использовать в Уведомлениях

```php
public function via($notifiable): array
{
    return ['mail', 'database', 'telegram'];
}

public function toTelegram($notifiable): array
{
    return [
        'chat_id' => $notifiable->telegram_chat_id,
        'text' => 'Your notification message'
    ];
}
```

## Мониторинг и Отладка

### Команды для Тестирования

```bash
# Тестирование приветственного письма
php artisan test:welcome-email user@example.com

# Проверка логов
tail -f storage/logs/laravel.log

# Проверка EmailLog в БД
php artisan tinker
>>> EmailLog::latest()->take(10)->get()
```

### Полезные Запросы

```php
// Статистика отправленных писем
EmailLog::where('status', 'sent')
    ->where('created_at', '>=', now()->subDays(7))
    ->count();

// Неудачные отправки
EmailLog::where('status', 'failed')
    ->latest()
    ->get();
```

## Миграция и Настройка

### Обязательные Настройки

1. **Настроить Resend API** в EmailService
2. **Создать таблицу EmailLog** если её нет
3. **Настроить очереди** для асинхронной отправки
4. **Добавить новые типы** в NotificationTypesSeeder

### Переменные Окружения

```env
# Resend API
RESEND_API_KEY=your_api_key

# Email настройки
MAIL_FROM_ADDRESS=noreply@spy.house
MAIL_FROM_NAME="Spy.House"
MAIL_SUPPORT_EMAIL=support@spy.house

# Telegram (если используется)
TELEGRAM_BOT_TOKEN=your_bot_token
```

Эта документация должна служить основным руководством для всех будущих имплементаций системы уведомлений.

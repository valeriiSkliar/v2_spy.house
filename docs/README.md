# Documentation

Документация проекта Spy.House v2.

## Система Уведомлений

- **[Notifications System](notifications-system.md)** - полная документация по архитектуре системы уведомлений
- **[Quick Start Guide](notifications-quickstart.md)** - быстрое создание уведомлений за 5 минут

## Интеграции

- **[SweetAlert Integration](SWEETALERT_INTEGRATION.md)** - интеграция SweetAlert для красивых уведомлений
- **[reCAPTCHA Integration](RECAPTCHA_INTEGRATION.md)** - настройка reCAPTCHA защиты
- **[reCAPTCHA Setup](recaptcha-setup.md)** - пошаговая настройка reCAPTCHA
- **[reCAPTCHA Local Development](recaptcha-local-development.md)** - настройка для локальной разработки

## Функциональность

- **[Landings](landings.md)** - система лендингов и их загрузки
- **[Authentication](authentication.md)** - система аутентификации

## Архитектура Уведомлений (Краткий Обзор)

### Компоненты

```
NotificationDispatcher → EmailService → Resend API
                   ↓
            Laravel Notifications → Database
```

### Быстрый старт

```php
// 1. Создать уведомление
php artisan make:notification YourNotification

// 2. Отправить уведомление
NotificationDispatcher::sendNotification($user, YourNotification::class);
```

### Поддерживаемые каналы

- **Email** (через Resend API)
- **Database** (уведомления в приложении)
- **Расширяемо**: SMS, Push, Telegram, Slack

---

Для подробной информации по конкретным темам см. соответствующие файлы документации.

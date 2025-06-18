# 7-дневный триал период - Руководство по реализации

## Описание

Система 7-дневного триала автоматически активируется после подтверждения email пользователем и предоставляет доступ к премиум функциям на ограниченное время.

## Архитектура

### Поля в таблице users

- `is_trial_period` (boolean) - флаг активного триала
- `subscription_time_start` (timestamp) - дата начала триала
- `subscription_time_end` (timestamp) - дата окончания триала

### Основные методы User модели

#### `isTrialPeriod(): bool`

Проверяет активен ли триал:

- Проверяет флаг `is_trial_period`
- Проверяет что дата окончания не прошла
- Автоматически сбрасывает флаг при истечении

#### `activateTrialPeriod(): void`

Активирует 7-дневный триал:

- Устанавливает `is_trial_period = true`
- Устанавливает даты начала и окончания
- Сбрасывает флаг истечения подписки

#### `getTrialDaysLeft(): int`

Возвращает количество оставшихся дней триала

#### `currentTariff(): array`

Возвращает информацию о текущем тарифе с поддержкой триала

## Активация триала

Триал автоматически активируется в `EmailVerificationController::verify()` при следующих условиях:

- Email пользователя подтвержден
- У пользователя нет активной подписки
- У пользователя еще не активирован триал

```php
// Активируем триал период на 7 дней
if (!$user->hasActiveSubscription() && !$user->isTrialPeriod()) {
    $user->activateTrialPeriod();
}
```

## Автоматическое истечение

### Команда

```bash
# Ручной запуск
php artisan trial:expire

# Через очередь
php artisan trial:expire --queue
```

### Job

Класс `ExpireTrialPeriodsJob` обрабатывает истечение триалов в фоне с логированием.

### Планировщик

Автоматический запуск каждый день в 00:00 через `bootstrap/app.php`:

```php
->withSchedule(function ($schedule) {
    $schedule->command('trial:expire --queue')->daily();
})
```

### Проверка планировщика

```bash
php artisan schedule:list
```

## Отображение в интерфейсе

### Header и Sidebar

Триал отображается с типом `'trial'` в компонентах:

- `resources/views/partials/header.blade.php`
- `resources/views/partials/sidebar.blade.php`

### Переводы

Добавлены переводы в `lang/ru/tariffs.php`:

```php
'trial_info' => [
    'expires_at' => 'Триал до: :date',
    'days_left' => 'Осталось дней: :days',
],
```

## Тестирование

### Unit тесты

```bash
./vendor/bin/phpunit tests/Unit/TrialPeriodUnitTest.php
```

Покрывают:

- Активацию триала
- Истечение триала
- Подсчет оставшихся дней
- Логику currentTariff

### Feature тесты

```bash
./vendor/bin/phpunit tests/Feature/TrialPeriodTest.php
```

Покрывают:

- Полный цикл подтверждения email и активации триала
- Команды и job
- Интеграционные сценарии

## Мониторинг

### Логи

Job `ExpireTrialPeriodsJob` логирует:

- Истечение триала для каждого пользователя
- Общее количество обработанных пользователей

### Команды для отладки

```bash
# Просмотр планировщика
php artisan schedule:list

# Ручной запуск истечения
php artisan trial:expire

# Проверка команд
php artisan list | grep trial
```

## Безопасность

- Триал активируется только после подтверждения email
- Невозможна повторная активация триала
- Автоматическое истечение предотвращает злоупотребления
- Логирование всех действий для аудита

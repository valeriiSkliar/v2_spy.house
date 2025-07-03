# Система источников рекламы (Ad Sources)

## Описание

Система управления источниками рекламных данных для проекта spy.house. Позволяет централизованно управлять различными источниками рекламы и их отображением.

## Структура

### Модель AdSource

**Файл:** `app/Models/AdSource.php`

**Поля:**

- `id` - Первичный ключ
- `source_name` - Системное имя источника (уникальное, до 50 символов)
- `source_display_name` - Отображаемое имя источника (до 100 символов)
- `created_at` - Дата создания
- `updated_at` - Дата обновления

**Методы:**

- `findBySourceName(string $sourceName)` - Поиск источника по системному имени
- `getActive()` - Получение всех активных источников (отсортированы по display_name)
- `exists(string $sourceName)` - Проверка существования источника

### Миграция

**Файл:** `database/migrations/2024_01_15_000000_create_ad_sources_table.php`

Создает таблицу `ad_sources` с необходимыми полями и индексами.

### Фабрика

**Файл:** `database/factories/AdSourceFactory.php`

**Методы:**

- `definition()` - Базовое определение для генерации тестовых данных
- `forSource(string $sourceName, string $displayName)` - Создание конкретного источника
- `pushHouse()` - Создание Push House источника
- `tiktok()` - Создание TikTok источника
- `facebook()` - Создание Facebook источника
- `feedHouse()` - Создание Feed House источника

### Сидер

**Файл:** `database/seeders/AdSourceSeeder.php`

Заполняет таблицу базовыми источниками:

- Push House
- TikTok Ads
- Facebook Ads
- Feed House
- Google Ads
- Telegram Ads
- VK Ads
- Yandex Direct

## Использование

### Создание нового источника

```php
use App\Models\AdSource;

$source = AdSource::create([
    'source_name' => 'new_source',
    'source_display_name' => 'New Source Name',
]);
```

### Поиск источника

```php
// По системному имени
$source = AdSource::findBySourceName('push_house');

// Проверка существования
if (AdSource::exists('tiktok')) {
    // Источник существует
}
```

### Получение всех активных источников

```php
$activeSources = AdSource::getActive();
foreach ($activeSources as $source) {
    echo $source->source_display_name;
}
```

### Использование в тестах

```php
use App\Models\AdSource;

// Создание через фабрику
$source = AdSource::factory()->create();

// Создание конкретного источника
$pushHouse = AdSource::factory()->pushHouse()->create();
$tiktok = AdSource::factory()->tiktok()->create();
```

## Команды

### Запуск миграции

```bash
php artisan migrate --path=database/migrations/2024_01_15_000000_create_ad_sources_table.php
```

### Запуск сидера

```bash
php artisan db:seed --class=AdSourceSeeder
```

### Запуск тестов

```bash
php artisan test --filter AdSourceTest
```

## Тестирование

**Файл:** `tests/Unit/Models/AdSourceTest.php`

Покрывает:

- Создание источников
- Уникальность source_name
- Методы поиска и проверки существования
- Работу с фабрикой
- Сортировку активных источников

## Интеграция

Сидер `AdSourceSeeder` добавлен в `DatabaseSeeder::class` для автоматического выполнения при общем сидинге базы данных.

## Будущие расширения

1. **Статусы источников** - добавление поля `is_active` для управления активностью
2. **Конфигурация источников** - добавление JSON поля для хранения настроек
3. **Группировка источников** - добавление категорий/групп источников
4. **Приоритеты** - добавление поля для управления порядком отображения
5. **Метрики** - связь с таблицами статистики и метрик

## Changelog

### v1.0.0 (2024-01-15)

- ✅ Создана базовая структура AdSource
- ✅ Добавлена миграция для таблицы ad_sources
- ✅ Создана фабрика с методами для основных источников
- ✅ Добавлен сидер с базовыми источниками
- ✅ Написаны unit тесты
- ✅ Интеграция с DatabaseSeeder

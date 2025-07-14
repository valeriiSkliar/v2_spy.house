# API Parsers System

🚀 Модульная система парсеров для извлечения данных из API различных источников

## Быстрый старт

### 1. Установка

Система уже интегрирована в проект Laravel. Убедитесь, что зарегистрирован `ParserServiceProvider`:

```php
// bootstrap/providers.php
App\Providers\ParserServiceProvider::class,
```

### 2. Конфигурация

Добавьте в `.env` файл:

```env
# PushHouse API (API ключ опционален для открытых эндпоинтов)
PUSH_HOUSE_API_KEY=your_api_key_optional
PUSH_HOUSE_BASE_URL=https://api.pushhouse.com
```

**Важно**: PushHouse поддерживает работу с открытыми эндпоинтами без API ключа. Если `PUSH_HOUSE_API_KEY` не указан, парсер будет работать только с публичными данными.

### 3. Использование

#### Простое использование через Manager

```php
use App\Services\Parsers\ParserManager;

$manager = app(ParserManager::class);

// Получить кампании из PushHouse (с API ключом или без)
$campaigns = $manager->pushHouse()->fetchCampaigns(['status' => 'active']);

```

#### Работа с открытыми эндпоинтами PushHouse

```php
// PushHouse может работать без API ключа для открытых данных
$openParser = new PushHouseParser(null, [
    'base_url' => 'https://api.pushhouse.com'
]);

// Получение публичных данных
$publicOffers = $openParser->fetchOffers(['category' => 'public']);
$publicCreatives = $openParser->fetchCreatives(['format' => 'push']);
```

#### Множественные запросы

```php
$results = $manager->fetchMultiple([
    'pushhouse_campaigns' => [
        'parser' => 'pushhouse',
        'method' => 'fetchCampaigns',
        'params' => ['status' => 'active']
    ],
    'feed_house_campaigns' => [
        'parser' => 'feedhouse',
        'method' => 'fetchAds',
        'params' => []
    ]
]);
```

## Возможности

✅ **Автоматические ретраи** - встроенная обработка временных сбоев  
✅ **Rate limiting** - защита от превышения лимитов API  
✅ **Логирование** - подробные логи всех операций  
✅ **Обработка ошибок** - специализированные исключения  
✅ **Асинхронные запросы** - параллельное выполнение  
✅ **Пагинация** - автоматическая обработка больших наборов данных  
✅ **Мониторинг** - health check и статистика  
✅ **Расширяемость** - легкое добавление новых парсеров

## Поддерживаемые API

### PushHouse

- 🎯 Кампании
- 🎨 Креативы
- 📊 Статистика
- 🎁 Офферы

## Архитектура

```
app/Services/Parsers/
├── BaseParser.php             # Базовый абстрактный класс
├── PushHouseParser.php        # PushHouse API парсер
├── FeedHouseParser.php        # FeedHouse API парсер
├── ParserManager.php          # Менеджер парсеров
└── Exceptions/
    ├── ParserException.php     # Базовое исключение
    ├── RateLimitException.php  # Rate limit исключение
    └── ApiKeyException.php     # API key исключение
```

## Примеры использования

### Обработка ошибок

```php
try {
    $data = $manager->pushHouse()->fetchCampaigns();
} catch (ApiKeyException $e) {
    // Неверный API ключ
    echo "API Key error: " . $e->getMessage();
} catch (RateLimitException $e) {
    // Превышен лимит запросов
    echo "Rate limit exceeded, retry after: " . $e->getRetryAfter() . " seconds";
} catch (ParserException $e) {
    // Общая ошибка парсера
    echo "Parser error: " . $e->getMessage();
}
```

### Мониторинг

```php
// Health check всех парсеров
$health = $manager->healthCheck();

// Статистика использования
$stats = $manager->getStats();

// Конфигурации парсеров
$configs = $manager->getConfigs();
```

## Конфигурация

### Rate Limits (по умолчанию)

- **PushHouse**: 1000 запросов/минуту
- **FeedHouse**: 100 запросов/минуту

### Timeouts

- **PushHouse**: 45 секунд
- **FeedHouse**: 60 секунд

### Retries

- **Максимум**: 3 попытки
- **Задержка**: экспоненциальная (1, 2, 4 секунды)

## Логирование

Все операции логируются в отдельный канал `parsers`:

```bash
tail -f storage/logs/parsers.log
```

Типы логов:

- **INFO**: Успешные операции
- **WARNING**: Ретраи и предупреждения
- **ERROR**: Критические ошибки

## Расширение

### Добавление нового парсера

1. Создайте класс, наследующийся от `BaseParser`
2. Реализуйте абстрактные методы `fetchData()` и `parseItem()`
3. Добавьте конфигурацию в `config/services.php`
4. Зарегистрируйте в `ParserServiceProvider`
5. Добавьте в `ParserManager::PARSERS`

### Пример нового парсера

```php
class FacebookParser extends BaseParser
{
    public function fetchData(array $params = []): array
    {
        // Ваша реализация
    }

    public function parseItem(array $item): array
    {
        // Ваша реализация
    }
}
```

## Тестирование

```bash
# Unit тесты
php artisan test tests/Unit/Services/Parsers/

# Feature тесты
php artisan test tests/Feature/Parsers/

# Запуск примеров
php examples/ParserUsage.php
```

## Производительность

### Рекомендации

- Используйте `fetchMultiple()` для параллельных запросов
- Мониторьте rate limits через `getStats()`
- Кэшируйте результаты когда возможно

### Benchmarks

- **Одиночный запрос**: ~200-500ms
- **Множественные запросы**: ~300-800ms (параллельно)

## Безопасность

🔐 **API ключи маскируются в логах**  
🔐 **Конфигурация через переменные окружения**  
🔐 **Валидация входных параметров**  
🔐 **Rate limiting защита**

## Поддержка

### Документация

- 📖 [Полная документация](docs/api-parsers-system.md)
- 💡 [Примеры использования](examples/ParserUsage.php)
- 🧪 [Тесты](tests/Unit/Services/Parsers/)

### Мониторинг

```php
// Health check всех парсеров
$health = app(ParserManager::class)->healthCheck();

foreach ($health as $parser => $status) {
    echo "{$parser}: {$status['status']}\n";
}
```

### Отладка

```php
// Включить детальное логирование
// .env
LOG_PARSERS_LEVEL=debug

// Получить статистику
$stats = $manager->getStats('pushhouse');
echo "Requests remaining: " . $stats['requests_remaining'];
```

## Changelog

### v1.0.0 (2024-01-15)

✨ **Новые возможности:**

- Базовая архитектура парсеров
- Поддержка PushHouse API
- Поддержка FeedHouse API
- Автоматические ретраи и rate limiting
- Система логирования
- ParserManager для централизованного управления
- Обработка ошибок и исключения
- Асинхронные запросы
- Health check и мониторинг

## Лицензия

Этот проект является частью SpyHouse платформы.

---

# API Parsers System

Модульная система парсеров для извлечения данных из API различных источников (PushHouse, TikTok и др.).

## Архитектура

### Компоненты системы

```
app/Services/Parsers/
├── BaseParser.php              # Абстрактный базовый класс
├── PushHouseParser.php         # Парсер для PushHouse API
├── TikTokParser.php           # Парсер для TikTok Business API
├── ParserManager.php          # Менеджер для управления парсерами
└── Exceptions/
    ├── ParserException.php     # Базовое исключение
    ├── RateLimitException.php  # Исключение превышения лимитов
    └── ApiKeyException.php     # Исключение аутентификации
```

### Принципы архитектуры

1. **Модульность**: Каждый источник данных имеет отдельный парсер
2. **Наследование**: Все парсеры наследуются от `BaseParser`
3. **Единообразие**: Унифицированный интерфейс для всех парсеров
4. **Обработка ошибок**: Специализированные исключения для разных типов ошибок
5. **Rate Limiting**: Встроенная защита от превышения лимитов API
6. **Логирование**: Подробное логирование всех операций

## Базовый класс BaseParser

### Основные возможности

- HTTP-запросы с автоматическими ретраями
- Rate limiting с настраиваемыми лимитами
- Обработка ошибок и исключений
- Логирование запросов и ответов
- Поддержка пагинации
- Асинхронные запросы через HTTP pool

### Абстрактные методы

```php
abstract public function fetchData(array $params = []): array;
abstract public function parseItem(array $item): array;
```

### Конфигурация

```php
$options = [
    'timeout' => 30,           // Таймаут запросов в секундах
    'rate_limit' => 60,        // Лимит запросов в минуту
    'max_retries' => 3,        // Максимальное количество ретраев
    'retry_delay' => 1,        // Задержка между ретраями в секундах
    'requires_auth' => true    // Требуется ли аутентификация (false для открытых API)
];
```

## Конкретные парсеры

### PushHouseParser

Парсер для работы с PushHouse API. **Особенность**: поддерживает как аутентифицированные, так и открытые эндпоинты.

#### Доступные эндпоинты

- `campaigns` - Кампании
- `creatives` - Креативы
- `statistics` - Статистика
- `offers` - Офферы

#### Режимы работы

**С аутентификацией** (полный доступ):

```php
$parser = new PushHouseParser('your-api-key', [
    'base_url' => 'https://api.pushhouse.com'
]);
```

**Без аутентификации** (только открытые данные):

```php
$parser = new PushHouseParser(null, [
    'base_url' => 'https://api.pushhouse.com'
]);
```

#### Методы

```php
// Получение кампаний
$campaigns = $parser->fetchCampaigns([
    'status' => 'active'
]);

// Получение креативов
$creatives = $parser->fetchCreatives([
    'format' => 'push'
]);

// Получение статистики
$statistics = $parser->fetchStatistics([
    'date_from' => '2024-01-01',
    'date_to' => '2024-01-31'
]);

// Получение офферов
$offers = $parser->fetchOffers(['category' => 'gaming']);
```

#### Аутентификация

Использует заголовок `X-API-Key` для аутентификации.

### TikTokParser

Парсер для работы с TikTok Business API.

#### Доступные эндпоинты

- `campaigns` - Кампании
- `adgroups` - Группы объявлений
- `ads` - Объявления
- `creatives` - Креативы
- `reports` - Отчеты

#### Методы

```php
// Получение кампаний
$campaigns = $parser->fetchCampaigns();

// Получение групп объявлений
$adgroups = $parser->fetchAdGroups(['campaign_id' => '123']);

// Получение объявлений
$ads = $parser->fetchAds(['adgroup_id' => '456']);

// Получение креативов
$creatives = $parser->fetchCreatives();

// Получение отчетов
$reports = $parser->fetchReports([
    'data_level' => 'AUCTION_CAMPAIGN',
    'start_date' => '2024-01-01',
    'end_date' => '2024-01-31'
]);
```

#### Аутентификация

Использует заголовок `Access-Token` и требует `advertiser_id`.

## ParserManager

Централизованный менеджер для работы с парсерами.

### Основные методы

```php
use App\Services\Parsers\ParserManager;

$manager = app(ParserManager::class);

// Получение конкретного парсера
$pushHouse = $manager->pushHouse();
$tikTok = $manager->tikTok();

// Получение парсера по имени
$parser = $manager->parser('pushhouse');

// Множественные запросы
$results = $manager->fetchMultiple([
    'campaigns' => [
        'parser' => 'pushhouse',
        'method' => 'fetchCampaigns',
        'params' => ['status' => 'active']
    ],
    'ads' => [
        'parser' => 'tiktok',
        'method' => 'fetchAds',
        'params' => []
    ]
]);

// Проверка здоровья парсеров
$health = $manager->healthCheck();

// Получение статистики
$stats = $manager->getStats();

// Получение конфигураций
$configs = $manager->getConfigs();
```

## Конфигурация

### Файл config/services.php

```php
'push_house' => [
    'api_key' => env('PUSH_HOUSE_API_KEY'),
    'base_url' => env('PUSH_HOUSE_BASE_URL', 'https://api.pushhouse.com'),
    'rate_limit' => env('PUSH_HOUSE_RATE_LIMIT', 1000),
    'timeout' => env('PUSH_HOUSE_TIMEOUT', 45),
    'max_retries' => env('PUSH_HOUSE_MAX_RETRIES', 3),
    'retry_delay' => env('PUSH_HOUSE_RETRY_DELAY', 2)
],

'tiktok' => [
    'api_key' => env('TIKTOK_API_KEY'),
    'advertiser_id' => env('TIKTOK_ADVERTISER_ID'),
    'base_url' => env('TIKTOK_BASE_URL', 'https://business-api.tiktok.com/open_api/v1.3'),
    'rate_limit' => env('TIKTOK_RATE_LIMIT', 100),
    'timeout' => env('TIKTOK_TIMEOUT', 60),
    'max_retries' => env('TIKTOK_MAX_RETRIES', 3),
    'retry_delay' => env('TIKTOK_RETRY_DELAY', 3)
],
```

### Переменные окружения .env

```env
# PushHouse API (API ключ опционален для открытых эндпоинтов)
PUSH_HOUSE_API_KEY=your_pushhouse_api_key_optional
PUSH_HOUSE_BASE_URL=https://api.pushhouse.com
PUSH_HOUSE_RATE_LIMIT=1000
PUSH_HOUSE_TIMEOUT=45
PUSH_HOUSE_MAX_RETRIES=3
PUSH_HOUSE_RETRY_DELAY=2

# TikTok Business API
TIKTOK_API_KEY=your_tiktok_access_token
TIKTOK_ADVERTISER_ID=your_advertiser_id
TIKTOK_BASE_URL=https://business-api.tiktok.com/open_api/v1.3
TIKTOK_RATE_LIMIT=100
TIKTOK_TIMEOUT=60
TIKTOK_MAX_RETRIES=3
TIKTOK_RETRY_DELAY=3

# Логирование парсеров
LOG_PARSERS_LEVEL=info
LOG_PARSERS_DAYS=7
```

**Примечание**: Для PushHouse API ключ является опциональным. Если не указан, парсер будет работать только с открытыми эндпоинтами.

## Обработка ошибок

### Типы исключений

#### ParserException

Базовое исключение для всех ошибок парсера.

```php
try {
    $data = $parser->fetchData();
} catch (ParserException $e) {
    echo "Parser error: " . $e->getMessage();
    echo "Context: " . json_encode($e->getContext());
}
```

#### ApiKeyException

Ошибки аутентификации и авторизации.

```php
try {
    $data = $parser->fetchData();
} catch (ApiKeyException $e) {
    echo "API Key error: " . $e->getMessage();
    echo "Masked key: " . $e->getMaskedApiKey();
}
```

#### RateLimitException

Превышение лимитов запросов.

```php
try {
    $data = $parser->fetchData();
} catch (RateLimitException $e) {
    echo "Rate limit exceeded: " . $e->getMessage();
    echo "Retry after: " . $e->getRetryAfter() . " seconds";

    // Можно реализовать автоматическую задержку
    sleep($e->getRetryAfter());
    // Повторить запрос
}
```

### Стратегии обработки ошибок

1. **Автоматические ретраи**: Встроенные в BaseParser
2. **Логирование**: Все ошибки логируются в канал 'parsers'
3. **Graceful degradation**: Система продолжает работать при сбоях отдельных парсеров
4. **Circuit breaker**: Временное отключение проблемных парсеров

## Логирование

Система использует отдельный канал логирования `parsers`.

### Конфигурация логирования

```php
// config/logging.php
'parsers' => [
    'driver' => 'daily',
    'path' => storage_path('logs/parsers.log'),
    'level' => env('LOG_PARSERS_LEVEL', 'info'),
    'days' => env('LOG_PARSERS_DAYS', 7),
    'replace_placeholders' => true,
],
```

### Типы логов

- **INFO**: Успешные запросы и операции
- **WARNING**: Ретраи и временные проблемы
- **ERROR**: Критические ошибки и исключения

### Структура логов

```json
{
  "level": "info",
  "message": "API Request",
  "context": {
    "parser": "PushHouse",
    "method": "GET",
    "url": "https://api.pushhouse.com/campaigns",
    "params": { "status": "active" },
    "timestamp": "2024-01-15T10:30:00.000Z"
  }
}
```

## Rate Limiting

### Механизм работы

1. **Счетчик запросов**: Хранится в Redis/Cache
2. **Временные окна**: По умолчанию 1 минута
3. **Проверка лимитов**: Перед каждым запросом
4. **Автоматическое ожидание**: При превышении лимитов

### Настройка лимитов

```php
// Глобальные лимиты в config/services.php
'rate_limit' => 1000, // запросов в минуту

// Или при создании парсера
$parser = new PushHouseParser($apiKey, [
    'rate_limit' => 500 // переопределить лимит
]);
```

### Мониторинг лимитов

```php
$stats = $parser->getStats();
echo "Requests this minute: " . $stats['requests_this_minute'];
echo "Requests remaining: " . $stats['requests_remaining'];
```

## Асинхронные запросы

### HTTP Pool в BaseParser

BaseParser поддерживает асинхронные запросы через Laravel HTTP Pool.

```php
// Пример использования через ParserManager
$results = $manager->fetchMultiple([
    'pushhouse_campaigns' => [
        'parser' => 'pushhouse',
        'method' => 'fetchCampaigns',
        'params' => ['status' => 'active']
    ],
    'tiktok_campaigns' => [
        'parser' => 'tiktok',
        'method' => 'fetchCampaigns',
        'params' => []
    ]
]);
```

### Преимущества асинхронных запросов

1. **Производительность**: Параллельное выполнение запросов
2. **Эффективность**: Лучшее использование ресурсов
3. **Масштабируемость**: Обработка больших объемов данных

## Тестирование

### Unit тесты

```php
// tests/Unit/Services/Parsers/BaseParserTest.php
public function testMakeRequest()
{
    $parser = new TestParser('test-key', 'https://api.test.com');

    Http::fake([
        'api.test.com/*' => Http::response(['data' => []], 200)
    ]);

    $response = $parser->makeRequest('test-endpoint');

    $this->assertTrue($response->successful());
}
```

### Integration тесты

```php
// tests/Feature/Parsers/PushHouseParserTest.php
public function testFetchCampaigns()
{
    $parser = app(PushHouseParser::class);

    $campaigns = $parser->fetchCampaigns();

    $this->assertArrayHasKey('data', $campaigns);
    $this->assertArrayHasKey('metadata', $campaigns);
}
```

## Мониторинг и отладка

### Health Check

```

```

# План реализации FeedHouseParser

Цель: Создать надёжный, сохраняющий состояние парсер для feed.house, который полностью интегрирован в существующую архитектуру приложения и использует модель AdSource для хранения своего состояния (lastId).

---

## Анализ структуры данных FeedHouse API

### ✅ Подтверждённая структура данных (протестировано)

```json
[
  {
    "id": 560720,
    "feedId": 8,
    "feedName": "RollerAds push main",
    "adNetwork": "rollerads",
    "campaignId": 545138,
    "format": "push",
    "status": "active",
    "countryIso": null,
    "browser": null,
    "os": null,
    "user": null,
    "site": null,
    "deviceType": null,
    "title": "Estudante Ganha R$57.000",
    "text": "por mês com Amazon",
    "icon": "https://wnt-some-push.net/icn/...",
    "image": "https://cdn4image.com/creatives/516/76/360_5_1684709713136.webp",
    "url": "https://wnt-some-push.net/clk/...",
    "seenCount": 1,
    "lastSeenAt": "2023-10-25T13:15:52Z",
    "createdAt": "2023-10-25T13:15:52Z"
  }
]
```

### 🔍 Особенности реальных данных

1. **Многие поля null** - `countryIso`, `browser`, `os`, `user`, `site`, `deviceType` часто null
2. **ID от 560000+** - старые данные, не восьмизначные
3. **Стандартные форматы** - `push`, `inpage`
4. **Активный статус** - все протестированные имеют `"status": "active"`
5. **Консистентные URL** - иконки и изображения на CDN

### Сравнение структур данных

| Поле             | FeedHouse API | PushHouse API     | Комментарий                              |
| ---------------- | ------------- | ----------------- | ---------------------------------------- |
| ID               | `id`          | `id`              | ✅ Совпадает                             |
| Заголовок        | `title`       | `title`           | ✅ Совпадает                             |
| Описание         | `text`        | `text`            | ✅ Совпадает                             |
| Иконка           | `icon`        | `icon`            | ✅ Совпадает                             |
| Изображение      | `image`       | `img`             | ⚠️ Разные названия                       |
| URL              | `url`         | `url`             | ✅ Совпадает                             |
| Страна           | `countryIso`  | `country`         | ⚠️ Разные названия                       |
| Статус           | `status`      | `isActive`        | ⚠️ Разные форматы                        |
| Дата создания    | `createdAt`   | `created_at`      | ⚠️ Разные названия                       |
| Цена             | ❌ Нет        | `cpc`/`price_cpc` | ❌ Отсутствует в FeedHouse               |
| Платформа        | ❌ Прямо нет  | `platform`        | ⚠️ Нужно определять по `os`+`deviceType` |
| Формат           | `format`      | ❌ Нет            | ✅ Есть в FeedHouse                      |
| Сеть             | `adNetwork`   | ❌ Нет            | ✅ Дополнительное поле                   |
| Браузер          | `browser`     | ❌ Нет            | ✅ Дополнительное поле                   |
| ОС               | `os`          | ❌ Нет            | ✅ Дополнительное поле                   |
| Устройство       | `deviceType`  | ❌ Нет            | ✅ Дополнительное поле                   |
| Взрослый контент | ❌ Нет        | `isAdult`         | ❌ Нужно определять эвристически         |

### Ключевые различия

1. **Отсутствие цены (CPC)** - FeedHouse не предоставляет информацию о цене
2. **Богатые метаданные** - FeedHouse содержит больше информации о браузере, ОС, устройстве
3. **Готовый формат** - FeedHouse уже предоставляет поле `format` (push/inpage)
4. **Статус как строка** - вместо boolean `isActive` используется строка `status`
5. **ISO код страны** - более стандартизированный формат `countryIso`

---

## Стратегия курсорной пагинации FeedHouse

### 🎯 Подтверждённая стратегия парсинга

На основе тестирования API определена оптимальная стратегия:

#### 1. Курсорная пагинация через `lastId`

```javascript
// Псевдокод стратегии
const LIMIT = 1000; // Максимальный размер страницы
let lastId = adSource.parser_state?.lastId || null;
let allData = [];

while (true) {
  const url = `/feed-campaigns?key=${apiKey}&formats=push,inpage&adNetworks=rollerads,richads&limit=${LIMIT}${
    lastId ? `&lastId=${lastId}` : ''
  }`;

  const response = await fetch(url);
  const data = await response.json();

  if (!data.length) break; // Конец данных

  allData.push(...data);
  lastId = Math.max(...data.map(item => item.id)); // Новый курсор

  // КРИТИЧНО: Сохраняем состояние после каждой итерации
  adSource.parser_state = { lastId };
  await adSource.save();

  if (data.length < LIMIT) break; // Последняя страница
}
```

#### 2. Ключевые особенности

- **Порядок**: API возвращает данные от старых к новым (по `id`)
- **Курсор**: `lastId` - это максимальный `id` из предыдущего ответа
- **Фильтрация**: Поддерживается `formats` и `adNetworks`
- **Лимит**: Рекомендуется 1000 для производительности, 10 для тестов
- **Состояние**: Сохранение `lastId` в `AdSource.parser_state` после каждой итерации

#### 3. Обработка особых случаев

- **Первый запуск**: `lastId = null` → получаем самые старые записи
- **Продолжение**: `lastId = последний_сохранённый_id` → получаем новые записи
- **Пустой ответ**: Завершение парсинга
- **Неполная страница**: Завершение парсинга (data.length < LIMIT)

#### 4. Rate Limiting

- **Задержка между запросами**: 500ms
- **Максимум запросов**: Не ограничен API, но рекомендуется разумное ограничение

---

## Этап 1: Реализация класса `FeedHouseParser`

### 1.1 Создание файла

- **Файл**: `app/Services/Parsers/FeedHouseParser.php`

### 1.2 Структура класса

```php
<?php

declare(strict_types=1);

namespace App\Services\Parsers;

use App\Services\Parsers\Exceptions\ParserException;
use App\Models\AdSource;
use Illuminate\Support\Facades\Log;

/**
 * FeedHouse API Parser
 *
 * Парсер для извлечения данных из FeedHouse Business API
 * Поддерживает получение кампаний, объявлений и креативов
 * Использует модель AdSource для сохранения состояния парсинга (lastId)
 *
 * @package App\Services\Parsers
 * @author SeniorSoftwareEngineer
 * @version 1.0.0
 */
class FeedHouseParser extends BaseParser
{
    /**
     * Initialize FeedHouse parser
     *
     * @param string $apiKey FeedHouse API access token
     * @param string $advertiserId FeedHouse advertiser ID
     * @param array $options Additional configuration options
     */
    public function __construct(string $apiKey, string $advertiserId, array $options = [])
    {
        $baseUrl = $options['base_url'] ?? config('services.feedhouse.base_url', 'https://api.feed.house/internal/v1/feed-campaigns');

        $this->advertiserId = $advertiserId;

        // FeedHouse specific options
        $feedHouseOptions = array_merge([
            'timeout' => 60,
            'rate_limit' => config('services.feedhouse.rate_limit', 100),
            'max_retries' => 3,
            'retry_delay' => 3,
            'parser_name' => 'FeedHouse'
        ], $options);

        parent::__construct($baseUrl, $apiKey, $feedHouseOptions);
    }

    /**
     * Fetch data from FeedHouse API with AdSource state management
     *
     * @param array $params Request parameters
     * @return array Fetched data
     * @throws ParserException
     */
    public function fetchData(array $params = []): array
    {
        // Основная логика парсинга без AdSource в конструкторе
        // AdSource будет передаваться через параметры или специальные методы
    }

    /**
     * Parse individual item from FeedHouse API
     *
     * @param array $item Raw item data from API
     * @return array Parsed item data
     */
    public function parseItem(array $item): array
    {
        // Парсинг отдельного элемента
        return $item; // или трансформация при необходимости
    }

    /**
     * Fetch data with AdSource state management
     * Основной метод для работы с состоянием AdSource
     *
     * @param AdSource $adSource Модель источника для сохранения состояния
     * @param array $params Дополнительные параметры
     * @return array Результат парсинга
     */
    public function fetchWithStateManagement(AdSource $adSource, array $params = []): array
    {
        // Здесь будет основная логика с управлением состоянием
    }
}
```

### 1.3 Детали реализации `fetchWithStateManagement()`

```php
/**
 * Fetch data with AdSource state management using cursor pagination
 * Использует курсорную пагинацию через lastId для эффективного парсинга
 *
 * @param AdSource $adSource Модель источника для сохранения состояния
 * @param array $params Дополнительные параметры
 * @return array Результат парсинга
 */
public function fetchWithStateManagement(AdSource $adSource, array $params = []): array
{
    try {
        // 1. Обновляем статус на 'running'
        $adSource->update(['parser_status' => 'running']);

        // 2. Получаем lastId из состояния
        $lastId = $adSource->parser_state['lastId'] ?? null;

        // 3. Определяем режим работы
        $mode = $params['mode'] ?? 'regular';
        if ($mode === 'initial_scan') {
            $lastId = null; // Сброс для полного скана
        }

        // 4. Настройки пагинации
        $limit = $params['limit'] ?? 1000; // Размер страницы (1000 для продакшена, 10 для тестов)
        $formats = $params['formats'] ?? ['push', 'inpage'];
        $adNetworks = $params['adNetworks'] ?? ['rollerads', 'richads'];

        // 5. Выполняем запросы к API с курсорной пагинацией
        $allData = [];
        $currentLastId = $lastId;
        $pageCount = 0;

        while (true) {
            $pageCount++;

            // Формируем параметры запроса
            $queryParams = [
                'limit' => $limit,
                'formats' => implode(',', $formats),
                'adNetworks' => implode(',', $adNetworks)
            ];

            // Добавляем lastId только если он есть
            if ($currentLastId !== null) {
                $queryParams['lastId'] = $currentLastId;
            }

            // Выполняем запрос (без endpoint, так как URL уже включает путь)
            $response = $this->makeRequest('', $queryParams);
            $data = $response->json();

            // Проверяем, есть ли данные
            if (empty($data) || !is_array($data)) {
                Log::info("FeedHouse: No more data on page {$pageCount}");
                break; // Нет больше данных
            }

            // 6. Обрабатываем полученные данные
            $pageData = [];
            foreach ($data as $item) {
                $parsedItem = $this->parseItem($item);
                if (!empty($parsedItem)) { // Проверяем, что парсинг успешен
                    $pageData[] = $parsedItem;
                    $allData[] = $parsedItem;
                }

                // Обновляем курсор на максимальный ID
                $currentLastId = max($currentLastId ?? 0, $item['id']);
            }

            // 7. КРИТИЧНО: Сохраняем состояние после каждой итерации
            $adSource->parser_state = ['lastId' => $currentLastId];
            $adSource->save();

            Log::info("FeedHouse: Page {$pageCount} processed", [
                'lastId' => $currentLastId,
                'items_received' => count($data),
                'items_parsed' => count($pageData),
                'total_parsed' => count($allData)
            ]);

            // 8. Проверяем условия завершения
            if (count($data) < $limit) {
                Log::info("FeedHouse: Last page reached (received {count} < {limit})", [
                    'count' => count($data),
                    'limit' => $limit
                ]);
                break; // Последняя страница
            }

            // 9. Rate limiting между запросами
            usleep(500000); // 0.5 сек между запросами
        }

        // 10. Успешное завершение
        $adSource->update([
            'parser_status' => 'idle',
            'parser_last_error' => null,
            'parser_last_error_at' => null,
            'parser_last_error_message' => null
        ]);

        Log::info("FeedHouse: Parsing completed successfully", [
            'total_items' => count($allData),
            'final_lastId' => $currentLastId,
            'pages_processed' => $pageCount
        ]);

        return $allData;

    } catch (\Exception $e) {
        // 11. Обработка ошибок с сохранением в AdSource
        $adSource->update([
            'parser_status' => 'failed',
            'parser_last_error' => [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ],
            'parser_last_error_at' => now(),
            'parser_last_error_message' => $e->getMessage(),
            'parser_last_error_code' => $e->getCode(),
        ]);

        Log::error("FeedHouse: Parsing failed", [
            'error' => $e->getMessage(),
            'adSource_id' => $adSource->id,
            'last_successful_lastId' => $currentLastId ?? 'none'
        ]);

        throw new ParserException("FeedHouse parsing failed: " . $e->getMessage(), 0, $e);
    }
}
```

## Этап 2: Интеграция в `ParserManager`

### 2.1 Обновление PARSERS массива

```php
private const PARSERS = [
    'pushhouse' => PushHouseParser::class,
    'tiktok' => TikTokParser::class,
    'feedhouse' => FeedHouseParser::class, // Добавляем FeedHouse
];
```

### 2.2 Добавление метода feedHouse() (стандартный)

```php
/**
 * Get FeedHouse parser
 *
 * @return FeedHouseParser
 */
public function feedHouse(): FeedHouseParser
{
    return $this->parser('feedhouse');
}
```

### 2.3 Добавление специального метода для работы с AdSource

```php
/**
 * Get FeedHouse parser with AdSource state management
 *
 * @param AdSource $adSource AdSource model for state management
 * @param array $params Additional parameters
 * @return array Parsing results
 */
public function feedHouseWithState(AdSource $adSource, array $params = []): array
{
    $parser = $this->feedHouse();
    return $parser->fetchWithStateManagement($adSource, $params);
}
```

## Этап 3: Конфигурация

### 3.1 Обновление config/services.php

```php
'feedhouse' => [
    'api_key' => env('FEEDHOUSE_API_KEY'), // Основной ключ доступа
    'base_url' => env('FEEDHOUSE_BASE_URL', 'https://api.feed.house/internal/v1/feed-campaigns'),
    'rate_limit' => env('FEEDHOUSE_RATE_LIMIT', 100), // Запросов в минуту
    'timeout' => env('FEEDHOUSE_TIMEOUT', 60), // Таймаут запроса в секундах
    'max_retries' => env('FEEDHOUSE_MAX_RETRIES', 3), // Количество повторов
    'retry_delay' => env('FEEDHOUSE_RETRY_DELAY', 3), // Задержка между повторами

    // Параметры курсорной пагинации
    'default_limit' => env('FEEDHOUSE_DEFAULT_LIMIT', 1000), // Размер страницы
    'default_formats' => ['push', 'inpage'], // Форматы по умолчанию
    'default_networks' => ['rollerads', 'richads'], // Сети по умолчанию

    // Authentication методы (query parameter или header)
    'auth_method' => env('FEEDHOUSE_AUTH_METHOD', 'query'), // 'query' или 'header'
    'auth_header_name' => 'X-Api-Key', // Название заголовка для аутентификации
],
```

### 3.2 Переменные .env

```env
# FeedHouse API Configuration
FEEDHOUSE_API_KEY=aa880679aa4aea25017311c6e8ed024c
FEEDHOUSE_BASE_URL=https://api.feed.house/internal/v1/feed-campaigns
FEEDHOUSE_RATE_LIMIT=100
FEEDHOUSE_TIMEOUT=60
FEEDHOUSE_MAX_RETRIES=3
FEEDHOUSE_RETRY_DELAY=3

# Пагинация и фильтрация
FEEDHOUSE_DEFAULT_LIMIT=1000
FEEDHOUSE_AUTH_METHOD=query
```

## Этап 4: Создание Artisan-команды

### 4.1 Создание команды

```bash
php artisan make:command ParseFeedHouseCommand
```

### 4.2 Реализация команды

```php
<?php

namespace App\Console\Commands;

use App\Models\AdSource;
use App\Services\Parsers\ParserManager;
use Illuminate\Console\Command;

class ParseFeedHouseCommand extends Command
{
    protected $signature = 'parser:feedhouse
                           {--mode=regular : Режим парсинга (regular|initial_scan)}
                           {--source=feedhouse : Название источника в базе данных}';

    protected $description = 'Run FeedHouse parser with state management';

    public function handle(ParserManager $parserManager)
    {
        $sourceName = $this->option('source');
        $mode = $this->option('mode');

        // Находим модель AdSource
        $adSource = AdSource::where('source_name', $sourceName)->first();

        if (!$adSource) {
            $this->error("AdSource with name '{$sourceName}' not found");
            return 1;
        }

        $this->info("Starting FeedHouse parsing...");
        $this->info("Source: {$adSource->source_display_name}");
        $this->info("Mode: {$mode}");

        try {
            // Используем ParserManager с AdSource state management
            $results = $parserManager->feedHouseWithState($adSource, [
                'mode' => $mode
            ]);

            $this->info("Parsing completed successfully!");
            $this->info("Total items processed: " . count($results));

        } catch (\Exception $e) {
            $this->error("Parsing failed: " . $e->getMessage());
            return 1;
        }

        return 0;
    }
}
```

## Этап 5: Аутентификация FeedHouse

### 5.1 Переопределение методов аутентификации в FeedHouseParser

```php
/**
 * FeedHouse поддерживает два метода аутентификации:
 * 1. Query parameter: ?key=api_key (по умолчанию)
 * 2. Header: X-Api-Key: api_key
 */

protected function getAuthHeaders(): array
{
    $headers = [
        'Accept' => 'application/json',
        'User-Agent' => 'SpyHouse-FeedHouse-Parser/1.0'
    ];

    // Если используется header аутентификация
    $authMethod = config('services.feedhouse.auth_method', 'query');
    if ($authMethod === 'header' && !empty($this->apiKey)) {
        $headerName = config('services.feedhouse.auth_header_name', 'X-Api-Key');
        $headers[$headerName] = $this->apiKey;
    }

    return $headers;
}

protected function makeRequest(string $endpoint = '', array $params = []): \Illuminate\Http\Client\Response
{
    // Добавляем аутентификацию через query parameter (по умолчанию)
    $authMethod = config('services.feedhouse.auth_method', 'query');
    if ($authMethod === 'query' && !empty($this->apiKey)) {
        $params['key'] = $this->apiKey;
    }

    // Вызываем родительский метод
    return parent::makeRequest($endpoint, $params);
}
```

## Этап 6: Использование

### 6.1 Через Artisan команду

```bash
# Обычный инкрементальный парсинг
php artisan parser:feedhouse --mode=regular

# Полный скан с начала
php artisan parser:feedhouse --mode=initial_scan

# Для конкретного источника
php artisan parser:feedhouse --source=feedhouse --mode=regular
```

### 6.2 Программное использование

```php
use App\Services\Parsers\ParserManager;
use App\Models\AdSource;

$parserManager = app(ParserManager::class);
$adSource = AdSource::findBySourceName('feedhouse');

// С управлением состоянием
$results = $parserManager->feedHouseWithState($adSource, [
    'mode' => 'regular'
]);

// Обычное использование (без состояния)
$parser = $parserManager->feedHouse();
$results = $parser->fetchData([
    'endpoint' => 'campaigns'
]);
```

---

## Архитектурные принципы

1. **Следование паттерну BaseParser** - конструктор принимает стандартные параметры
2. **Dependency injection через методы** - AdSource передается в специальные методы
3. **Полная совместимость с ParserManager** - стандартная интеграция
4. **Гибридный подход** - поддержка работы с состоянием и без него
5. **Консистентная архитектура** - следует паттернам PushHouseParser и TikTokParser
6. **Реализация всех абстрактных методов** - включая обязательный parseItem()
7. **Интеграция в DI контейнер** - автоматическое создание через app->make()

Этот план полностью соответствует существующей архитектуре и документации системы парсеров.

---

## Ключевые различия FeedHouse vs PushHouse

### 🔄 Архитектурные различия

| Аспект               | PushHouse                    | FeedHouse                               | Комментарий                               |
| -------------------- | ---------------------------- | --------------------------------------- | ----------------------------------------- |
| **Пагинация**        | Path-based (`/ads/5/active`) | Cursor-based (`?lastId=123`)            | FeedHouse эффективнее для больших объемов |
| **Аутентификация**   | Опциональная (null API key)  | Обязательная (query param/header)       | FeedHouse требует валидный ключ           |
| **Структура ответа** | Прямой массив                | Прямой массив                           | Совпадает ✅                              |
| **Фильтрация**       | По статусу в URL             | По formats/adNetworks в query           | FeedHouse богаче параметрами              |
| **Метаданные**       | Минимальные                  | Богатые (feedId, campaignId, seenCount) | FeedHouse предоставляет больше контекста  |
| **Обработка null**   | Редко                        | Часто (countryIso, browser, os)         | FeedHouse требует robust null handling    |

### 📊 Особенности данных FeedHouse

#### 1. Богатые метаданные

```php
// FeedHouse предоставляет дополнительную информацию:
'feedId' => 8,
'feedName' => 'RollerAds push main',
'campaignId' => 545138,
'seenCount' => 1,
'lastSeenAt' => '2023-10-25T13:15:52Z'
```

#### 2. Частые null значения

```php
// Многие поля могут быть null, требуют fallback логики:
'countryIso' => null,    // Определение по IP или default
'browser' => null,       // Определение эвристически
'os' => null,           // Fallback на 'Unknown'
'deviceType' => null    // Fallback на 'Unknown'
```

#### 3. Курсорная пагинация

```php
// Состояние сохраняется между запросами:
$lastId = $adSource->parser_state['lastId'] ?? null;

// Каждый запрос продвигает курсор:
$newLastId = max(array_column($response, 'id'));
$adSource->parser_state = ['lastId' => $newLastId];
```

### 🚀 Преимущества FeedHouse подхода

1. **Эффективная пагинация** - курсор быстрее offset для больших датасетов
2. **Сохранение состояния** - можно возобновить парсинг с любого момента
3. **Богатые фильтры** - точная настройка по форматам и сетям
4. **Метаданные** - больше контекста для анализа креативов
5. **Масштабируемость** - API оптимизирован для высоких нагрузок

### ⚠️ Вызовы реализации FeedHouse

1. **Null handling** - много полей могут быть null
2. **Определение платформы** - требуется анализ os + deviceType
3. **Детекция adult контента** - эвристический анализ title + text
4. **Управление состоянием** - критична надёжность сохранения lastId
5. **Обработка ошибок** - важно не потерять прогресс парсинга

---

## Интеграция в существующую архитектуру

### 📋 TODO List для внедрения

1. **Создать FeedHouseParser класс** (следует паттерну BaseParser)
2. **Создать FeedHouseCreativeDTO** (обязательно из-за сложности трансформаций)
3. **Добавить в ParserManager** (стандартная интеграция)
4. **Создать Artisan команду** (ParseFeedHouseCommand)
5. **Обновить конфигурацию** (config/services.php + .env)
6. **Создать миграцию AdSource** (если нужны дополнительные поля)
7. **Написать тесты** (unit + integration)
8. **Обновить документацию** (README + API docs)

### 🔧 Совместимость с текущей системой

FeedHouseParser полностью совместим с:

- ✅ BaseParser архитектурой
- ✅ ParserManager интеграцией
- ✅ AdSource state management
- ✅ Существующими тестами
- ✅ Logging системой
- ✅ Error handling паттернами

### 🎯 Готовность к реализации

План готов к реализации. Все архитектурные решения протестированы на реальном API FeedHouse и соответствуют существующим паттернам проекта.

---

## Рекомендации по реализации и тестированию

### 🧪 Стратегия тестирования

#### 1. Unit тесты

```php
// Тестирование курсорной пагинации
testCursorPaginationLogic()
testStateManagementWithAdSource()
testNullValueHandling()
testAdultContentDetection()
testPlatformDetermination()
```

#### 2. Integration тесты

```php
// Тестирование с реальным API (sandbox)
testRealAPIConnection()
testFullParsingCycle()
testErrorRecovery()
testStatePersistence()
```

#### 3. Тестовые данные

- Использовать `limit=10` для всех тестов
- Мокать реальные ответы API для unit тестов
- Создать фикстуры с различными сценариями null значений

### 🔧 Этапы внедрения

#### Фаза 1: Базовая реализация

1. Создать `FeedHouseParser` с базовой функциональностью
2. Реализовать курсорную пагинацию
3. Добавить state management через AdSource
4. Написать unit тесты

#### Фаза 2: DTO и трансформации

1. Создать `FeedHouseCreativeDTO`
2. Реализовать все трансформации данных
3. Добавить валидацию и обработку ошибок
4. Расширить тестовое покрытие

#### Фаза 3: Интеграция

1. Интегрировать в ParserManager
2. Создать Artisan команду
3. Обновить конфигурацию
4. Провести integration тесты

#### Фаза 4: Production готовность

1. Оптимизация производительности
2. Мониторинг и логирование
3. Документация API
4. Stress тестирование

### 🚨 Критические моменты

1. **Сохранение состояния**: Каждая итерация ДОЛЖНА сохранять `lastId`
2. **Обработка ошибок**: Не потерять прогресс при сбоях API
3. **Rate limiting**: Соблюдать ограничения API (500ms между запросами)
4. **Null safety**: Все поля могут быть null, предусмотреть fallback
5. **Memory management**: При больших объёмах данных контролировать память

### 📈 Метрики успеха

- ✅ Парсинг 1000+ креативов без потери данных
- ✅ Корректное восстановление после сбоев
- ✅ Обработка всех null значений
- ✅ Производительность: >500 креативов/минуту
- ✅ 100% покрытие тестами критического кода

## Этап 7: Создание FeedHouseCreativeDTO

### 7.1 Создание файла DTO

- **Файл**: `app/Http/DTOs/Parsers/FeedHouseCreativeDTO.php`

### 7.2 Структура DTO

```php
<?php

namespace App\Http\DTOs\Parsers;

use App\Enums\Frontend\AdvertisingFormat;
use App\Enums\Frontend\AdvertisingStatus;
use App\Enums\Frontend\Platform;
use App\Models\AdvertismentNetwork;
use App\Services\Parsers\CreativePlatformNormalizer;
use App\Services\Parsers\CountryCodeNormalizer;
use App\Services\Parsers\SourceNormalizer;
use Carbon\Carbon;

/**
 * DTO для креативов FeedHouse API
 *
 * Обеспечивает типизацию, валидацию и трансформацию данных
 * от FeedHouse API в унифицированный формат для записи в БД
 *
 * @package App\Http\DTOs\Parsers
 * @author SeniorSoftwareEngineer
 * @version 1.0.0
 */
class FeedHouseCreativeDTO
{
    public function __construct(
        public readonly int $externalId,
        public readonly string $title,
        public readonly string $text,
        public readonly string $iconUrl,
        public readonly string $imageUrl,
        public readonly string $targetUrl,
        public readonly string $countryCode,
        public readonly Platform $platform,
        public readonly AdvertisingFormat $format,
        public readonly string $adNetwork,
        public readonly string $browser,
        public readonly string $os,
        public readonly string $deviceType,
        public readonly bool $isActive,
        public readonly bool $isAdult,
        public readonly Carbon $createdAt,
        public readonly int $seenCount = 0,
        public readonly ?Carbon $lastSeenAt = null,
        public readonly string $source = 'feedhouse'
    ) {}

    /**
     * Создает DTO из сырых данных API FeedHouse
     *
     * @param array $data Сырые данные от FeedHouse API
     * @return self
     */
    public static function fromApiResponse(array $data): self
    {
        return new self(
            externalId: (int) ($data['id'] ?? 0),
            title: $data['title'] ?? '',
            text: $data['text'] ?? '',
            iconUrl: $data['icon'] ?? '',
            imageUrl: $data['image'] ?? '', // Note: 'image', not 'img'
            targetUrl: $data['url'] ?? '',
            countryCode: strtoupper($data['countryIso'] ?? ''),
            platform: self::determinePlatformFromMetadata($data),
            format: self::normalizeFormat($data['format'] ?? 'push'),
            adNetwork: $data['adNetwork'] ?? 'unknown',
            browser: $data['browser'] ?? '',
            os: $data['os'] ?? '',
            deviceType: $data['deviceType'] ?? '',
            isActive: self::normalizeStatus($data['status'] ?? 'inactive'),
            isAdult: self::detectAdultContent($data),
            createdAt: self::parseCreatedAt($data['createdAt'] ?? null),
            seenCount: (int) ($data['seenCount'] ?? 0),
            lastSeenAt: self::parseLastSeenAt($data['lastSeenAt'] ?? null)
        );
    }

    /**
     * Определяет платформу на основе метаданных
     *
     * @param array $data Данные с метаинформацией
     * @return Platform
     */
    private static function determinePlatformFromMetadata(array $data): Platform
    {
        $os = strtolower($data['os'] ?? '');
        $deviceType = strtolower($data['deviceType'] ?? '');

        // Мобильные платформы
        if (str_contains($os, 'android') || str_contains($os, 'ios')) {
            return Platform::MOBILE;
        }

        // По типу устройства
        if (str_contains($deviceType, 'phone') || str_contains($deviceType, 'mobile')) {
            return Platform::MOBILE;
        }

        if (str_contains($deviceType, 'tablet')) {
            return Platform::TABLET;
        }

        // Desktop по умолчанию для Windows, MacOS, Linux
        if (str_contains($os, 'windows') || str_contains($os, 'macos') || str_contains($os, 'linux')) {
            return Platform::DESKTOP;
        }

        // Fallback
        return Platform::MOBILE;
    }

    /**
     * Нормализует формат рекламы
     *
     * @param string $format Формат от API
     * @return AdvertisingFormat
     */
    private static function normalizeFormat(string $format): AdvertisingFormat
    {
        return match (strtolower($format)) {
            'push' => AdvertisingFormat::PUSH,
            'inpage' => AdvertisingFormat::INPAGE,
            'native' => AdvertisingFormat::NATIVE,
            'banner' => AdvertisingFormat::BANNER,
            default => AdvertisingFormat::PUSH, // Fallback
        };
    }

    /**
     * Нормализует статус активности
     *
     * @param string $status Статус от API
     * @return bool
     */
    private static function normalizeStatus(string $status): bool
    {
        return strtolower($status) === 'active';
    }

    /**
     * Детектирует взрослый контент эвристически
     *
     * @param array $data Данные креатива
     * @return bool
     */
    private static function detectAdultContent(array $data): bool
    {
        $text = strtolower(($data['title'] ?? '') . ' ' . ($data['text'] ?? ''));

        $adultKeywords = [
            'sex', 'dating', 'adult', 'porn', 'xxx', 'sexy', 'hot girls',
            'escorts', 'hookup', 'nude', 'erotic', 'massage', 'intimate'
        ];

        foreach ($adultKeywords as $keyword) {
            if (str_contains($text, $keyword)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Безопасный парсинг даты создания
     *
     * @param mixed $dateValue Значение даты от API
     * @return Carbon Валидная дата
     */
    private static function parseCreatedAt($dateValue): Carbon
    {
        if (empty($dateValue)) {
            return now();
        }

        try {
            $parsedDate = Carbon::parse($dateValue);

            if ($parsedDate->year <= 1970) {
                return now();
            }

            if ($parsedDate->isFuture() && $parsedDate->diffInYears(now()) > 1) {
                return now();
            }

            return $parsedDate;
        } catch (\Exception $e) {
            return now();
        }
    }

    /**
     * Безопасный парсинг даты последнего просмотра
     *
     * @param mixed $dateValue Значение даты от API
     * @return Carbon|null Валидная дата или null
     */
    private static function parseLastSeenAt($dateValue): ?Carbon
    {
        if (empty($dateValue)) {
            return null;
        }

        try {
            return Carbon::parse($dateValue);
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Преобразует DTO в массив для записи в БД
     *
     * @return array Данные для записи в таблицу creatives
     */
    public function toDatabase(): array
    {
        return [
            // Основные поля
            'external_id' => $this->externalId,
            'title' => $this->title,
            'description' => $this->text,
            'icon_url' => $this->iconUrl,
            'main_image_url' => $this->imageUrl,
            'landing_url' => $this->targetUrl,
            'platform' => $this->platform->value,
            'format' => $this->format->value,
            'is_adult' => $this->isAdult,
            'external_created_at' => $this->createdAt,

            // Нормализованные foreign key поля
            'source_id' => SourceNormalizer::normalizeSourceName($this->source),
            'country_id' => CountryCodeNormalizer::normalizeCountryCode($this->countryCode),
            'advertisment_network_id' => AdvertismentNetwork::where('network_name', 'feedhouse')->first()?->id,

            // Статус
            'status' => $this->isActive ? AdvertisingStatus::Active : AdvertisingStatus::Inactive,

            // Метаданные (JSON поля)
            'metadata' => [
                'adNetwork' => $this->adNetwork,
                'browser' => $this->browser,
                'os' => $this->os,
                'deviceType' => $this->deviceType,
                'seenCount' => $this->seenCount,
                'lastSeenAt' => $this->lastSeenAt?->toISOString(),
                'source_api' => 'feedhouse_business_api'
            ],

            // Уникальный хеш
            'combined_hash' => $this->generateCombinedHash(),

            // Временные метки
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }

    /**
     * Генерирует уникальный хеш для креатива
     *
     * @return string SHA256 хеш для идентификации креатива
     */
    private function generateCombinedHash(): string
    {
        $data = [
            'external_id' => $this->externalId,
            'source' => $this->source,
            'title' => $this->title,
            'text' => $this->text,
            'country' => $this->countryCode,
            'adNetwork' => $this->adNetwork,
        ];

        return hash('sha256', json_encode($data, JSON_UNESCAPED_UNICODE));
    }

    /**
     * Валидация данных DTO
     *
     * @return bool true если данные валидны
     */
    public function isValid(): bool
    {
        // Базовая валидация
        if (empty($this->externalId) || empty($this->countryCode)) {
            return false;
        }

        // Проверяем наличие хотя бы заголовка или текста
        if (empty($this->title) && empty($this->text)) {
            return false;
        }

        // Проверяем наличие хотя бы одного изображения
        if (empty($this->iconUrl) && empty($this->imageUrl)) {
            return false;
        }

        // Проверяем валидность URL
        if (!empty($this->targetUrl) && !filter_var($this->targetUrl, FILTER_VALIDATE_URL)) {
            return false;
        }

        return true;
    }

    /**
     * Проверяет, является ли креатив дубликатом
     *
     * @param array $existingCreatives Массив существующих креативов
     * @return bool true если дубликат найден
     */
    public function isDuplicate(array $existingCreatives): bool
    {
        $currentHash = $this->generateCombinedHash();

        foreach ($existingCreatives as $creative) {
            if (isset($creative['combined_hash']) && $creative['combined_hash'] === $currentHash) {
                return true;
            }
        }

        return false;
    }
}
```

### 7.3 Интеграция DTO в FeedHouseParser

Обновляем метод `parseItem()` в `FeedHouseParser`:

```php
/**
 * Parse individual item from FeedHouse API
 * Использует FeedHouseCreativeDTO для типизации и валидации
 *
 * @param array $item Raw item data from API
 * @return array Processed item data
 */
public function parseItem(array $item): array
{
    try {
        $dto = FeedHouseCreativeDTO::fromApiResponse($item);

        if (!$dto->isValid()) {
            Log::warning("FeedHouse: Invalid creative data", [
                'external_id' => $dto->externalId,
                'title' => $dto->title
            ]);
            return [];
        }

        return $dto->toDatabase();

    } catch (\Exception $e) {
        Log::error("FeedHouse: Failed to parse creative", [
            'error' => $e->getMessage(),
            'item' => $item
        ]);
        return [];
    }
}
```

---

## Этап 8: Различия в создании DTO между PushHouse и FeedHouse

### 8.1 Когда создавать DTO для PushHouse

DTO для PushHouse необходимо создавать в следующих случаях:

1. **Типизация данных** - когда нужна строгая типизация полей
2. **Валидация входных данных** - проверка корректности данных от API
3. **Трансформация структуры** - когда структура API не совпадает с БД
4. **Нормализация значений** - приведение к единому формату (платформа, статус)
5. **Генерация вычисляемых полей** - создание hash, определение формата
6. **Обработка ошибок** - централизованная обработка некорректных данных

### 8.2 Когда создавать DTO для FeedHouse

Для FeedHouse DTO создание **ОБЯЗАТЕЛЬНО** по следующим причинам:

1. **Кардинально другая структура данных** - поля имеют разные названия
2. **Отсутствие цены (CPC)** - нужно устанавливать значение по умолчанию
3. **Определение платформы по метаданным** - анализ `os` + `deviceType`
4. **Детекция взрослого контента** - эвристический анализ текста
5. **Нормализация формата** - прямое преобразование строки в enum
6. **Богатые метаданные** - сохранение дополнительной информации
7. **Валидация специфичных полей** - проверка `countryIso`, `adNetwork`

### 8.3 Сравнительная таблица необходимости DTO

| Критерий              | PushHouse      | FeedHouse       | Комментарий                             |
| --------------------- | -------------- | --------------- | --------------------------------------- |
| Структура данных      | Простая        | Сложная         | FeedHouse имеет больше полей            |
| Совпадение с БД       | Частичное      | Минимальное     | FeedHouse требует больше трансформаций  |
| Валидация             | Базовая        | Расширенная     | FeedHouse нужны дополнительные проверки |
| Нормализация          | Стандартная    | Специфичная     | FeedHouse требует эвристики             |
| Метаданные            | Минимальные    | Богатые         | FeedHouse предоставляет больше данных   |
| **Необходимость DTO** | **Желательно** | **Обязательно** |                                         |

### 8.4 Рекомендации по использованию DTO

**Для PushHouse:**

- DTO рекомендуется для типизации и валидации
- Можно обойтись без DTO для простых случаев
- Используйте DTO при необходимости сложной обработки

**Для FeedHouse:**

- DTO обязательно из-за сложности трансформаций
- Невозможно обойтись без DTO из-за кардинальных различий
- DTO является единственным способом корректной обработки

---

Диаграмма классов для сравнения методов парсеровclassDiagram для визуализации различий между парсерами
graph TB
subgraph "API Responses"
PH[PushHouse API]
FH[FeedHouse API]
end

    subgraph "Data Transformation"
        PHDT[PushHouse DTO<br/>- Simple validation<br/>- Basic normalization<br/>- Platform detection]
        FHDT[FeedHouse DTO<br/>- Complex validation<br/>- Rich metadata<br/>- Adult content detection<br/>- Platform derivation]
    end

    subgraph "Database"
        DB[(Database<br/>creatives table)]
    end

    PH -->|"Simple Structure<br/>• id, title, text<br/>• icon, img, url<br/>• cpc, country<br/>• isActive"| PHDT

    FH -->|"Complex Structure<br/>• id, title, text<br/>• icon, image, url<br/>• countryIso, status<br/>• format, adNetwork<br/>• browser, os, deviceType<br/>• seenCount, lastSeenAt"| FHDT

    PHDT -->|"Standard Fields<br/>+ Generated Hash"| DB
    FHDT -->|"Normalized Fields<br/>+ Rich Metadata<br/>+ Generated Hash"| DB

    style PHDT fill:#e1f5fe
    style FHDT fill:#fff3e0
    style FH fill:#ffecb3
    style PH fill:#e8f5e8

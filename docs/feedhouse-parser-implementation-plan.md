# План реализации FeedHouseParser

Цель: Создать надёжный, сохраняющий состояние парсер для feed.house, который полностью интегрирован в существующую архитектуру приложения и использует модель AdSource для хранения своего состояния (lastId).

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

        // 4. Выполняем запросы к API с сохранением состояния
        $allData = [];
        $currentLastId = $lastId;

        while (true) {
            $response = $this->makeRequest('', [
                'advertiser_id' => $this->advertiserId,
                'last_id' => $currentLastId,
                'limit' => 1000
            ]);

            $data = $response->json();

            if (empty($data['data'])) {
                break; // Нет больше данных
            }

            // 5. Обрабатываем полученные данные
            foreach ($data['data'] as $item) {
                $allData[] = $this->parseItem($item);
                $currentLastId = max($currentLastId ?? 0, $item['id']);
            }

            // 6. КРИТИЧНО: Сохраняем состояние после каждой итерации
            $adSource->parser_state = ['lastId' => $currentLastId];
            $adSource->save();

            Log::info("FeedHouse: State saved", [
                'lastId' => $currentLastId,
                'items_count' => count($data['data'])
            ]);

            // Rate limiting
            usleep(500000); // 0.5 сек между запросами

            if (count($data['data']) < 1000) {
                break; // Последняя страница
            }
        }

        // 7. Успешное завершение
        $adSource->update([
            'parser_status' => 'idle',
            'parser_last_error' => null,
            'parser_last_error_at' => null,
            'parser_last_error_message' => null
        ]);

        Log::info("FeedHouse: Parsing completed successfully", [
            'total_items' => count($allData),
            'final_lastId' => $currentLastId
        ]);

        return $allData;

    } catch (\Exception $e) {
        // 8. Обработка ошибок с сохранением в AdSource
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
            'adSource_id' => $adSource->id
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
    'api_key' => env('FEEDHOUSE_API_KEY'),
    'advertiser_id' => env('FEEDHOUSE_ADVERTISER_ID'),
    'base_url' => env('FEEDHOUSE_BASE_URL', 'https://api.feed.house/internal/v1/feed-campaigns'),
    'rate_limit' => env('FEEDHOUSE_RATE_LIMIT', 100),
    'timeout' => env('FEEDHOUSE_TIMEOUT', 60),
    'max_retries' => env('FEEDHOUSE_MAX_RETRIES', 3),
    'retry_delay' => env('FEEDHOUSE_RETRY_DELAY', 3)
],
```

### 3.2 Переменные .env

```env
FEEDHOUSE_API_KEY=your_feedhouse_access_token
FEEDHOUSE_ADVERTISER_ID=your_advertiser_id
FEEDHOUSE_BASE_URL=https://api.feed.house/internal/v1/feed-campaigns
FEEDHOUSE_RATE_LIMIT=100
FEEDHOUSE_TIMEOUT=60
FEEDHOUSE_MAX_RETRIES=3
FEEDHOUSE_RETRY_DELAY=3
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

### 5.1 Переопределение getAuthHeaders() в FeedHouseParser

```php
protected function getAuthHeaders(): array
{
    $headers = [
        'Accept' => 'application/json',
        'User-Agent' => 'SpyHouse-Parser/1.0'
    ];

    // FeedHouse использует заголовок Access-Token
    if (!empty($this->apiKey)) {
        $headers['Access-Token'] = $this->apiKey;
    }

    return $headers;
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

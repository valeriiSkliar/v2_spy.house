# Push.House Parser Workflow

Этот документ описывает полный workflow для периодического парсинга API Push.House, которое не предоставляет эндпоинты для получения только новых данных.

## Обзор

Основная задача — эффективно получать полный список активных объявлений, сравнивать его с текущим состоянием в нашей базе данных, определять новые и деактивированные объявления, обновлять БД и ставить в очередь отложенные задания (Jobs) для дальнейшей обработки.

## Пример данных API

API Push.House возвращает массив объектов со следующей структурой:

```json
[
  {
    "id": 1393905,
    "title": "😱 PARABÉNS! VOCÊ GANHOU! 💵",
    "text": "✅ BÔNUS R$4,675 + 100 GIRADAS GRÁTIS 🤑",
    "icon": "https://s3.push.house/push.house-camps/20066/68667c5018fa6.png",
    "img": "https://s3.push.house/push.house-camps/20066/68667c50306a2.png",
    "url": "https://oxwheele.life/?creative_id={camp}&source={site}",
    "cpc": "0.00770000",
    "country": "BR",
    "platform": "Mob",
    "isAdult": false,
    "isActive": false,
    "created_at": "2025-07-03"
  },
  {
    "id": 1393904,
    "title": "",
    "text": "",
    "icon": "https://s3.push.house/push.house-camps/82883/",
    "img": "https://s3.push.house/push.house-camps/82883/",
    "url": "https://100app.pro/click.php?key=xx0bhpzqo8pzb9xunjhy&click_id={click_id}&price={price}&site={site}&camp={camp}&feed={feed}&country={country}&city={city}&os={os}&browser={browser}&format={format}&lang={lang}&pdpid={pdpid}",
    "cpc": "0.00038000",
    "country": "JP",
    "platform": "Mob",
    "isAdult": true,
    "isActive": false,
    "created_at": "2025-07-03"
  },
  {
    "id": 1393903,
    "title": "Stop working with 1 hand!",
    "text": "Finally start meeting hotties in {CITY}",
    "icon": "https://s3.push.house/push.house-camps/61934/68667a3f6df58.png",
    "img": "https://s3.push.house/push.house-camps/61934/68667a3f5cdfe.png",
    "url": "https://rku2mrp.hot-partner-theclick.com/ygzgxx3?s1={campaign_id}&cid={click_id}&site={site}&city={city}&browser={browser}&os={os}&feed={feed}",
    "cpc": "0.05100000",
    "country": "NZ",
    "platform": "Mob",
    "isAdult": true,
    "isActive": false,
    "created_at": "2025-07-03"
  }
]
```

**Ключевые поля:**

- `id` - внешний идентификатор объявления (используется как `external_id` в нашей БД)
- `title`, `text` - заголовок и текст объявления
- `icon`, `img` - URL изображений
- `url` - целевая ссылка с макросами
- `cpc` - стоимость за клик
- `country` - код страны (ISO)
- `platform` - платформа (Mob/Desktop)
- `isAdult` - флаг взрослого контента
- `isActive` - статус активности объявления
- `created_at` - дата создания

## DTO и трансформация данных

### Принципы работы с DTO

Для каждого источника данных (push_house, facebook,tiktok, feed_house ) AdSource::class
**ОБЯЗАТЕЛЬНО** создается индивидуальный DTO (Data Transfer Object), который выполняет следующие функции:

1. **Типизация данных** - обеспечивает строгую типизацию входящих данных от API
2. **Трансформация** - преобразует структуру данных источника в унифицированный формат для записи в БД
3. **Валидация** - проверяет корректность и полноту данных перед обработкой
4. **Стандартизация** - приводит данные разных источников к единому формату

### Пример DTO для Push.House

```php
<?php

namespace App\Http\DTOs\Parsers;

use App\Enums\Frontend\AdvertisingFormat;
use App\Enums\Frontend\AdvertisingStatus;
use App\Enums\Frontend\Platform;
use App\Services\Parsers\CreativePlatformNormalizer;
use App\Services\Parsers\CountryCodeNormalizer;
use App\Services\Parsers\SourceNormalizer;
use Carbon\Carbon;

class PushHouseCreativeDTO
{
    public function __construct(
        public readonly int $externalId,
        public readonly string $title,
        public readonly string $text,
        public readonly string $iconUrl,
        public readonly string $imageUrl,
        public readonly string $targetUrl,
        public readonly float $cpc,
        public readonly string $countryCode,
        public readonly Platform $platform,
        public readonly bool $isAdult,
        public readonly bool $isActive,
        public readonly Carbon $createdAt,
        public readonly string $source = 'push_house'
    ) {}

    /**
     * Создает DTO из сырых данных API Push.House
     */
    public static function fromApiResponse(array $data): self
    {
        return new self(
            externalId: $data['id'],
            title: $data['title'] ?? '',
            text: $data['text'] ?? '',
            iconUrl: $data['icon'] ?? '',
            imageUrl: $data['img'] ?? '',
            targetUrl: $data['url'] ?? '',
            cpc: (float) ($data['cpc'] ?? 0),
            countryCode: strtoupper($data['country'] ?? ''),
            platform: CreativePlatformNormalizer::normalizePlatform(
                $data['platform'] ?? 'Mob',
                'push_house'
            ),
            isAdult: (bool) ($data['isAdult'] ?? false),
            isActive: (bool) ($data['isActive'] ?? false),
            createdAt: Carbon::parse($data['created_at'] ?? now())
        );
    }

    /**
     * Преобразует DTO в массив для записи в БД
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
            'is_adult' => $this->isAdult,
            'external_created_at' => $this->createdAt,

            // Нормализованные foreign key поля
            'source_id' => SourceNormalizer::normalizeSourceName($this->source),
            'country_id' => CountryCodeNormalizer::normalizeCountryCode($this->countryCode),

            // Преобразование boolean в enum для статуса
            'status' => $this->isActive ? AdvertisingStatus::Active : AdvertisingStatus::Inactive,

            // Обязательные поля БД с значениями по умолчанию
            'format' => AdvertisingFormat::PUSH, // Push.House всегда push формат
            'combined_hash' => $this->generateCombinedHash(),

            // Стандартные временные метки
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }

    /**
     * Генерирует уникальный хеш для креатива
     */
    private function generateCombinedHash(): string
    {
        $data = [
            'external_id' => $this->externalId,
            'source' => $this->source,
            'title' => $this->title,
            'text' => $this->text,
            'country' => $this->countryCode,
        ];

        return hash('sha256', json_encode($data, JSON_UNESCAPED_UNICODE));
    }

    /**
     * Валидация данных DTO
     */
    public function isValid(): bool
    {
        return !empty($this->externalId)
            && !empty($this->countryCode);
    }
}
```

### Процесс трансформации

1. **Получение данных** - API возвращает массив сырых данных
2. **Создание DTO** - каждый элемент преобразуется в `PushHouseCreativeDTO::fromApiResponse()`
3. **Валидация** - проверка данных через `$dto->isValid()`
4. **Трансформация** - преобразование в формат БД через `$dto->toDatabase()`
5. **Сохранение** - массовая вставка трансформированных данных

```php
// Пример использования в сервисе
$apiResponse = $this->fetchFromPushHouseApi();
$dtoCollection = collect($apiResponse)
    ->map(fn($item) => PushHouseCreativeDTO::fromApiResponse($item))
    ->filter(fn($dto) => $dto->isValid());

$dataForDatabase = $dtoCollection
    ->map(fn($dto) => $dto->toDatabase())
    ->all();

DB::table('creatives')->insert($dataForDatabase);
```

### Преимущества подхода

- **Безопасность типов** - исключает ошибки типизации
- **Единообразие** - все источники приводятся к единому формату
- **Тестируемость** - DTO легко тестировать изолированно
- **Расширяемость** - добавление нового источника не влияет на существующие
- **Читаемость** - явная структура данных для каждого источника

## Нормализация платформ

### Архитектура системы нормализации

Для унификации значений платформ от разных источников используется паттерн Strategy с регистрацией нормализаторов:

```php
// Интерфейс для всех нормализаторов
interface PlatformNormalizerInterface
{
    public function normalize(string $platformValue): Platform;
    public function canHandle(string $source): bool;
}

// Основной координатор
class CreativePlatformNormalizer
{
    // Автоматически подбирает нужный нормализатор по источнику
    public static function normalizePlatform(string $platformValue, string $source): Platform;
}
```

### Добавление нового источника

Для добавления поддержки нового источника (например, Facebook Ads):

1. **Создать нормализатор**:

```php
class FacebookAdsPlatformNormalizer implements PlatformNormalizerInterface
{
    private const PLATFORM_MAPPING = [
        'mobile_app' => Platform::MOBILE,
        'mobile_web' => Platform::MOBILE,
        'desktop' => Platform::DESKTOP,
        'tablet' => Platform::DESKTOP, // Решение бизнес-логики
    ];

    public function normalize(string $platformValue): Platform
    {
        $normalized = strtolower(trim($platformValue));
        return self::PLATFORM_MAPPING[$normalized] ?? Platform::MOBILE;
    }

    public function canHandle(string $source): bool
    {
        return strtolower($source) === 'facebook_ads';
    }
}
```

2. **Зарегистрировать в основном классе**:

```php
// В CreativePlatformNormalizer::registerDefaultNormalizers()
$this->registerNormalizer(new FacebookAdsPlatformNormalizer());
```

3. **Использовать в DTO**:

```php
platform: CreativePlatformNormalizer::normalizePlatform(
    $data['device_platform'] ?? 'mobile_app',
    'facebook_ads'
),
```

### Принципы нормализации

- **Fallback значение**: Всегда возвращать `Platform::MOBILE` при неизвестном значении
- **Case-insensitive**: Приводить входные значения к нижнему регистру
- **Trim whitespace**: Удалять пробельные символы
- **Бизнес-логика**: Планшеты обычно мапятся на `DESKTOP` для рекламных целей

## Workflow Steps

### 1. Инициация (Триггер)

- **Artisan-команда**: Создается команда `php artisan parsers:run-push-house` как единая точка входа для запуска парсера.
- **Планировщик (Scheduler)**: Команда регистрируется в `bootstrap/app.phps` для периодического запуска.
  ```php
  // bootstrap/app.php
  $schedule->command('parsers:run-push-house')
           ->everyFifteenMinutes()
           ->withoutOverlapping(); // Важно: предотвращает запуск нового парсера, если старый еще работает.
  ```

### 2. Получение и подготовка данных

- **Запрос к API**: Сервис `PushHouseParser` выполняет HTTP-запрос к API Push.House и получает полный список активных объявлений.
- **Создание DTO**: Ответ (JSON) преобразуется в коллекцию `PushHouseCreativeDTO` объектов через `fromApiResponse()` для обеспечения типизации и стандартизации.
- **Валидация**: Каждый DTO проверяется через `isValid()` метод, невалидные данные отфильтровываются.
- **Извлечение ID**: Из коллекции валидных DTO извлекаются все уникальные внешние идентификаторы (`external_id`) и сохраняются в массив `$apiAdIds`.

### 3. Определение новых и деактивированных объявлений

Это ключевой шаг для синхронизации данных.

- **Получение существующих ID из БД**: Выполняется один запрос для получения всех `external_id` из локальной базы данных.
  ```sql
  SELECT external_id FROM ads WHERE source = 'push_house';
  ```
  Результат сохраняется в массив `$dbAdIds`.
- **Поиск новых объявлений**: Находятся ID, которые есть в `$apiAdIds`, но отсутствуют в `$dbAdIds`.
  ```php
  $newAdIds = array_diff($apiAdIds, $dbAdIds);
  ```
- **Поиск деактивированных объявлений**: Находятся ID, которые есть в `$dbAdIds`, но отсутствуют в `$apiAdIds`.
  ```php
  $deactivatedAdIds = array_diff($dbAdIds, $apiAdIds);
  ```

### 4. Синхронизация с базой данных

Все операции записи в БД должны быть обернуты в **транзакцию** для обеспечения целостности данных.

```php
DB::transaction(function () use ($newAdIds, $deactivatedAdIds, $dtoCollection) {
    // 1. Обработка новых объявлений
    $newCreativesData = $dtoCollection
        ->filter(fn($dto) => in_array($dto->externalId, $newAdIds))
        ->map(fn($dto) => $dto->toDatabase())
        ->all();

    if (!empty($newCreativesData)) {
        DB::table('creatives')->insert($newCreativesData);
    }

    // 2. Обработка деактивированных объявлений
    if (!empty($deactivatedAdIds)) {
        DB::table('creatives')
            ->whereIn('external_id', $deactivatedAdIds)
            ->where('source', 'push_house')
            ->update(['is_active' => false, 'updated_at' => now()]);
    }
});
```

### 5. Постановка отложенных заданий (Jobs) в очередь

Этот шаг выполняется **после** успешного завершения транзакции.

- **Для новых объявлений**:

  1.  Получаем внутренние ID только что созданных записей.
      ```php
      $localIdsForNewAds = DB::table('creatives')->whereIn('external_id', $newAdIds)->pluck('id');
      ```
  2.  **Диспетчеризация заданий**: Создаем одно "пакетное" задание, которое принимает массив ID, чтобы не перегружать очередь.
      ```php
      if ($localIdsForNewAds->isNotEmpty()) {
          ProcessNewAdsBatch::dispatch($localIdsForNewAds->all());
      }
      ```

- **Для деактивированных объявлений**:
  1.  Аналогично получаем внутренние ID для обновленных записей.
      ```php
      $localIdsForDeactivatedAds = DB::table('creatives')->whereIn('external_id', $deactivatedAdIds)->pluck('id');
      ```
  2.  **Диспетчеризация заданий**: Отправляем отдельное пакетное задание для обработки деактивации.
      ```php
      if ($localIdsForDeactivatedAds->isNotEmpty()) {
          ProcessDeactivatedAdsBatch::dispatch($localIdsForDeactivatedAds->all());
      }
      ```

## Итоговая схема

1.  **Cron** -> **Artisan Command**.
2.  **Fetch** с API -> **DTO Collection** -> валидация -> `$apiAdIds`.
3.  **Compare**:
    - `$newAdIds = $apiAdIds - $dbAdIds`.
    - `$deactivatedAdIds = $dbAdIds - $apiAdIds`.
4.  **DB Transaction**:
    - **Transform**: DTO -> database format через `toDatabase()`.
    - **INSERT** новые, **UPDATE** деактивированные.
5.  **Dispatch Jobs**:
    - `SELECT` локальные ID для новых -> `ProcessNewAdsBatch::dispatch([...])`.
    - `SELECT` локальные ID для деактивированных -> `ProcessDeactivatedAdsBatch::dispatch([...])`.

## Ключевые принципы

- **Типизация**: Обязательное использование индивидуальных DTO для каждого источника данных.
- **Эффективность**: Минимизация запросов к БД (1 `SELECT`, 1 `INSERT`, 1 `UPDATE`).
- **Надежность**: Использование транзакций для атомарности операций и `withoutOverlapping()` для предотвращения "гонки состояний".
- **Масштабируемость**: Использование пакетных заданий (`Batch Jobs`) для обработки большого количества изменений без перегрузки системы очередей.
- **Стандартизация**: Единый формат данных в БД независимо от источника через DTO трансформацию.

## Файловая структура реализации

На основе анализа существующей архитектуры проекта, предлагается следующая файловая структура для реализации Push.House парсера:

### 1. Console Commands (Точки входа)

```
app/Console/Commands/Parsers/
└── RunPushHouseParserCommand.php          # Единственная необходимая команда
```

**Назначение:**

- `RunPushHouseParserCommand.php` - основная команда `php artisan parsers:run-push-house`
- Поддержка опций `--queue`, `--force`, `--dry-run`, `--test`
- Интеграция с планировщиком Laravel (Scheduler)

**Почему убрали избыточные команды:**

- ❌ `TestPushHouseConnectionCommand` - функционал через `--test` флаг основной команды
- ❌ `ForceSyncPushHouseCommand` - функционал через `--force` флаг основной команды
- ❌ `PushHouseStatsCommand` - статистика через логи, не критично для MVP

### 2. DTOs (Типизация и трансформация данных)

```
app/Http/DTOs/Parsers/
└── PushHouseCreativeDTO.php               # Единственный необходимый DTO
```

**Назначение:**

- Типизация входящих данных от Push.House API
- Трансформация в формат БД через `fromApiResponse()` и `toDatabase()`
- Валидация данных через `isValid()`
- Использует существующие нормализаторы

**Почему убрали избыточные DTOs:**

- ❌ `PushHouseParsingResultDTO` - простой массив с результатами достаточен
- ❌ `PushHouseApiResponseDTO` - прямая обработка JSON ответа эффективнее
- ❌ `BaseParserDTO` - over-engineering для одного парсера
- ❌ `ParserResultInterface` - YAGNI принцип, пока не нужен

### 3. Services (Бизнес-логика парсинга)

```
app/Services/Parsers/PushHouse/
├── PushHouseParsingService.php            # Основной сервис парсинга
├── PushHouseApiClient.php                 # HTTP клиент для API
└── PushHouseSynchronizer.php              # Синхронизация с БД
```

**Назначение:**

- `PushHouseParsingService.php` - координатор всего процесса парсинга (главный оркестратор)
- `PushHouseApiClient.php` - работа с HTTP API, пагинация, retry логика, обработка ошибок API
- `PushHouseSynchronizer.php` - **КЛЮЧЕВОЙ КОМПОНЕНТ** для сложной логики синхронизации:
  - Сравнение списков ID (`array_diff($apiAdIds, $dbAdIds)`)
  - Определение новых и деактивированных объявлений
  - Координация DB транзакций
  - Пакетные операции INSERT/UPDATE
  - Получение внутренних ID для Jobs

**Почему убрали избыточные компоненты:**

- ❌ `PushHouseDataTransformer` - функционал покрыт DTO методами (`fromApiResponse()`, `toDatabase()`)
- ❌ `PushHouseDataValidator` - валидация уже в `PushHouseCreativeDTO::isValid()`
- ❌ `PushHouseBusinessRules` - простые правила интегрированы в DTO, сложных бизнес-правил нет

### 4. Jobs (Асинхронная обработка)

```
app/Jobs/Parsers/PushHouse/
└── ProcessPushHouseParsingJob.php         # Единственное необходимое задание
```

**Назначение:**

- Асинхронная обработка всего процесса парсинга
- Включает логику для новых и деактивированных объявлений
- Пакетные операции внутри одного Job
- Интеграция с системой очередей (RabbitMQ)

**Почему убрали избыточные Jobs:**

- ❌ `ProcessNewPushHouseAdsJob` - логика интегрирована в основной Job
- ❌ `ProcessDeactivatedPushHouseAdsJob` - логика интегрирована в основной Job
- ❌ `BatchProcessPushHouseAdsJob` - дублирует функционал основного Job
- ❌ `SendPushHouseParsingReportJob` - отчеты через логи и существующую систему уведомлений
- ❌ `NotifyPushHouseErrorsJob` - используем существующую систему уведомлений

### 5. Нормализаторы (Использование существующей системы)

```
app/Services/Parsers/PlatformNormalizers/
└── PushHousePlatformNormalizer.php        # ✅ Уже существует

app/Services/Parsers/
├── SourceNormalizer.php                   # ✅ Уже существует
└── CountryCodeNormalizer.php              # ✅ Уже существует
```

**Назначение:**

- Использование существующих нормализаторов - **БЕЗ ДОПОЛНИТЕЛЬНЫХ КЛАССОВ**
- `PushHousePlatformNormalizer` уже реализован и работает
- `SourceNormalizer` и `CountryCodeNormalizer` покрывают все нужды

**Почему убрали избыточные нормализаторы:**

- ❌ `PushHouseFieldNormalizer` - специфичных полей требующих нормализации нет
- ❌ `PushHouseStatusNormalizer` - простое преобразование `boolean -> enum` в DTO

### 6. Exceptions (Использование существующей системы)

```
app/Services/Parsers/Exceptions/
└── ParserException.php                    # ✅ Уже существует, используем его
```

**Назначение:**

- Используем существующий `ParserException` с контекстом
- Детальная информация через поле `context`
- Поддержка recovery и retry логики уже реализована

**Почему убрали избыточные исключения:**

- ❌ `PushHouseApiException` - используем `ParserException` с контекстом `['type' => 'api_error']`
- ❌ `PushHouseValidationException` - используем `ParserException` с контекстом `['type' => 'validation_error']`
- ❌ `PushHouseSyncException` - используем `ParserException` с контекстом `['type' => 'sync_error']`
- ❌ `PushHouseParsingException` - используем базовый `ParserException`

### 7. Configuration (Использование существующих конфигов)

```
config/
└── services.php                           # ✅ Добавить секцию push_house
```

**Пример добавления в config/services.php:**

```php
// В существующий config/services.php добавить:
'push_house' => [
    'base_url' => env('PUSH_HOUSE_API_URL', 'https://api.push.house/v1'),
    'timeout' => env('PUSH_HOUSE_TIMEOUT', 45),
    'rate_limit' => env('PUSH_HOUSE_RATE_LIMIT', 1000),
    'max_retries' => env('PUSH_HOUSE_MAX_RETRIES', 3),
    'retry_delay' => env('PUSH_HOUSE_RETRY_DELAY', 2),
    'batch_size' => env('PUSH_HOUSE_BATCH_SIZE', 100),
    'max_pages' => env('PUSH_HOUSE_MAX_PAGES', 100),
    'source_name' => 'push_house',
],
```

**Почему убрали отдельные конфиги:**

- ❌ `config/parsers/push_house.php` - избыточная структура для одного парсера
- ❌ `config/parsers/parsers.php` - используем существующие конфиги (`services.php`, `queue.php`, `logging.php`)

### 8. Tests (Оптимизированное тестирование)

```
tests/Feature/Parsers/PushHouse/
├── PushHouseParsingServiceTest.php        # Интеграционные тесты
├── PushHouseApiClientTest.php             # Тесты API клиента
├── PushHouseSynchronizerTest.php          # Тесты синхронизации
└── RunPushHouseParserCommandTest.php      # Тесты команды

tests/Unit/Parsers/PushHouse/
├── PushHouseCreativeDTOTest.php           # Тесты DTO
├── PushHouseSynchronizerTest.php          # Unit тесты синхронизации
└── ProcessPushHouseParsingJobTest.php     # Тесты Job
```

**Почему убрали избыточные тесты:**

- ❌ `PushHouseParsingResultDTOTest.php` - DTO не существует
- ❌ `BatchProcessPushHouseAdsJobTest.php` - Job не существует
- ✅ Упростили структуру директорий - убрали лишние вложения

### 9. Database (Использование существующей структуры)

```
database/migrations/
└── [timestamp]_add_push_house_support_to_creatives_table.php  # ✅ Только если нужны новые поля

database/seeders/
└── PushHouseTestDataSeeder.php            # ✅ Только для тестирования
```

**Обоснование:**

- Таблица `creatives` уже существует и поддерживает все необходимые поля
- Миграция нужна только если потребуются специфичные для Push.House поля
- Seeder полезен для создания тестовых данных

### 10. Scheduler Integration (Планировщик)

**В bootstrap/app.php:**

```php
$schedule->command('parsers:run-push-house')
         ->everyFifteenMinutes()
         ->withoutOverlapping()
         ->runInBackground()
         ->onFailure(function () {
             // Логирование ошибок
         });
```

### 11. Monitoring и Logging (Использование существующей системы)

```
storage/logs/
├── laravel.log                            # ✅ Используем существующий лог
└── parsers.log                            # ✅ Если есть отдельный канал для парсеров
```

**Обоснование:**

- Используем существующую систему логирования Laravel
- Контекст Push.House добавляется через поля в логах
- Избегаем создания множества отдельных файлов логов

### Принципы архитектуры

1. **Модульность**: Каждый компонент имеет четкую ответственность
2. **Расширяемость**: Легко добавить новые источники данных
3. **Тестируемость**: Каждый слой покрыт тестами
4. **Конфигурируемость**: Все параметры выносятся в конфиг
5. **Мониторинг**: Детальное логирование и метрики
6. **Производительность**: Асинхронная обработка и оптимизация БД
7. **Надежность**: Обработка ошибок и recovery механизмы

### Последовательность реализации

1. **Фаза 1**: DTOs и базовые сервисы
2. **Фаза 2**: API клиент
3. **Фаза 3**: Синхронизация с БД (Synchronizer)
4. **Фаза 4**: Jobs и асинхронная обработка
5. **Фаза 5**: Commands и интеграция с планировщиком
6. **Фаза 6**: Тестирование и оптимизация
7. **Фаза 7**: Мониторинг и алерты

### Итоговое обоснование оптимизированной структуры

## ✅ Оставили только необходимые компоненты:

### Основные файлы (5 компонентов):

1. **RunPushHouseParserCommand.php** - единая точка входа с флагами `--test`, `--force`, `--dry-run`
2. **PushHouseCreativeDTO.php** - типизация и трансформация данных (`fromApiResponse()`, `toDatabase()`, `isValid()`)
3. **PushHouseParsingService.php** - координация всего процесса парсинга
4. **PushHouseApiClient.php** - специфичная работа с API (HTTP запросы, retry логика, пагинация)
5. **PushHouseSynchronizer.php** - сложная логика сравнения БД (`array_diff`, транзакции, batch операции)
6. **ProcessPushHouseParsingJob.php** - асинхронная обработка (включает логику для новых и деактивированных объявлений)

### Используем существующие системы:

- **Нормализаторы** - `PushHousePlatformNormalizer`, `SourceNormalizer`, `CountryCodeNormalizer`
- **Исключения** - `ParserException` с контекстом
- **Конфигурация** - секция в `config/services.php`
- **Логирование** - существующие каналы Laravel

## ❌ Убрали избыточность (15+ компонентов):

### Commands:

- ❌ `TestPushHouseConnectionCommand` → флаг `--test`
- ❌ `ForceSyncPushHouseCommand` → флаг `--force`
- ❌ `PushHouseStatsCommand` → логи

### DTOs:

- ❌ `PushHouseParsingResultDTO` → простой массив
- ❌ `PushHouseApiResponseDTO` → прямая обработка JSON
- ❌ `BaseParserDTO` → YAGNI принцип
- ❌ `ParserResultInterface` → over-engineering

### Jobs:

- ❌ `ProcessNewPushHouseAdsJob` → интегрировано в основной Job
- ❌ `ProcessDeactivatedPushHouseAdsJob` → интегрировано в основной Job
- ❌ `BatchProcessPushHouseAdsJob` → дублирует основной Job
- ❌ `SendPushHouseParsingReportJob` → через логи
- ❌ `NotifyPushHouseErrorsJob` → существующая система уведомлений

### Остальное:

- ❌ 4 специализированных исключения → `ParserException` с контекстом
- ❌ 2 отдельных конфига → секция в `services.php`
- ❌ 3 отдельных лог-файла → существующая система логирования

## Результат оптимизации:

**Было:** ~25 файлов  
**Стало:** ~6 основных файлов + тесты  
**Экономия:** ~75% файлов при сохранении всего функционала

Эта **максимально оптимизированная** структура соответствует принципам KISS, DRY и YAGNI, при этом полностью покрывает описанный workflow и обеспечивает возможность масштабирования в будущем.

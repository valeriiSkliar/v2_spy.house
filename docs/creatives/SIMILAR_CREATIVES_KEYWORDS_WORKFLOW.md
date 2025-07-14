# Workflow: Система поиска похожих креативов по ключевым словам

## Обзор системы

Система анализирует заголовки креативов, извлекает ключевые слова и находит похожие креативы на основе совпадения минимум 3 ключевых слов + формат рекламы.

## Архитектура решения

### 1. Структура данных

#### Новая таблица creative_keywords

```sql
CREATE TABLE creative_keywords (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    creative_id BIGINT NOT NULL,
    keyword VARCHAR(50) NOT NULL COLLATE utf8mb4_unicode_ci,
    word_length TINYINT UNSIGNED NOT NULL,
    frequency TINYINT UNSIGNED DEFAULT 1,

    FOREIGN KEY (creative_id) REFERENCES creatives(id) ON DELETE CASCADE,

    -- Составные индексы для оптимизации
    INDEX idx_creative_keyword (creative_id, keyword),
    INDEX idx_keyword_length (keyword, word_length),
    INDEX idx_length_freq (word_length, frequency),

    -- Уникальность пары креатив-слово
    UNIQUE KEY unique_creative_keyword (creative_id, keyword)
);
```

#### Дополнение к таблице creatives

```sql
ALTER TABLE creatives ADD COLUMN keywords_hash VARCHAR(64) NULL
COMMENT 'MD5 хеш ключевых слов для быстрой проверки изменений';

ALTER TABLE creatives ADD INDEX idx_keywords_hash (keywords_hash);
```

### 2. Алгоритм извлечения ключевых слов

#### Правила парсинга заголовков:

1. **Минимальная длина слова**: 4 символа
2. **Максимальное количество ключевых слов**: 10 на креатив
3. **Приоритет**: самые длинные слова имеют приоритет
4. **Очистка**: удаление знаков препинания, приведение к нижнему регистру
5. **Стоп-слова**: исключение общих слов без смысловой нагрузки
6. **Обработка пустых заголовков**: пропуск креативов без title или с пустым title
7. **Fallback стратегия**: если ключевых слов < 3, сохранить хотя бы 1-2 для частичного поиска

#### Стоп-слова по языкам:

```php
$stopWords = [
    'en' => ['the', 'and', 'for', 'with', 'you', 'your', 'this', 'that', 'have', 'from', 'will', 'can', 'get', 'all', 'new', 'now', 'app', 'top', 'best', 'free'],
    'ru' => ['это', 'для', 'как', 'или', 'все', 'что', 'еще', 'уже', 'так', 'вас', 'вам', 'его', 'она', 'они', 'при', 'где', 'там', 'тут', 'без'],
    'common' => ['2024', '2025', 'app', 'apps', 'game', 'play', 'download', 'install', 'click', 'here', 'link']
];
```

### 3. Алгоритм поиска похожих креативов

#### Приоритеты поиска:

1. **Формат** (обязательное условие)
2. **Минимум 3 совпадающих ключевых слова** (HAVING условие)
3. **Количество совпадений** (чем больше, тем выше в результатах)
4. **Актуальность** (last_seen_at, external_created_at)

#### SQL запрос:

```sql
SELECT
    c.*,
    COUNT(DISTINCT ck.keyword) as keyword_matches,
    GROUP_CONCAT(DISTINCT ck.keyword ORDER BY ck.word_length DESC) as matched_keywords
FROM creatives c
INNER JOIN creative_keywords ck ON c.id = ck.creative_id
WHERE c.id != :original_id
  AND c.format = :format
  AND c.is_processed = 1
  AND c.is_valid = 1
  AND c.status = 'active'
  AND ck.keyword IN (:keyword1, :keyword2, :keyword3, ...)
GROUP BY c.id
HAVING keyword_matches >= 3
ORDER BY
    keyword_matches DESC,
    c.last_seen_at DESC
LIMIT :limit OFFSET :offset
```

## Компоненты системы

### 1. Модели

#### CreativeKeyword Model

```php
<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CreativeKeyword extends Model
{
    protected $fillable = ['creative_id', 'keyword', 'word_length', 'frequency'];

    public function creative(): BelongsTo
    {
        return $this->belongsTo(Creative::class);
    }

    public static function saveKeywordsForCreative(int $creativeId, array $keywords): void
    {
        // Логика сохранения ключевых слов
    }

    public static function getKeywordsForCreative(int $creativeId): array
    {
        // Получение ключевых слов креатива
    }
}
```

#### Обновление Creative Model

```php
// Добавить в модель Creative
public function keywords(): HasMany
{
    return $this->hasMany(CreativeKeyword::class);
}

public function updateKeywords(): void
{
    // Обновление ключевых слов при изменении заголовка
}

public function getKeywordsHash(): string
{
    // Генерация хеша ключевых слов
}
```

### 2. Сервисы

#### CreativeKeywordService

```php
<?php
namespace App\Services\Creatives;

class CreativeKeywordService
{
    public function extractKeywords(?string $title, int $minLength = 4, int $maxKeywords = 10): array
    {
        // Проверка на пустой заголовок
        if (empty($title) || trim($title) === '') {
            return [];
        }

        // Извлечение ключевых слов из заголовка
        // Возвращает пустой массив если не удалось извлечь достаточно слов
    }

    public function processCreativeKeywords(Creative $creative): bool
    {
        // Проверка наличия заголовка
        if (empty($creative->title)) {
            $this->logSkippedCreative($creative->id, 'empty_title');
            return false;
        }

        // Обработка ключевых слов для креатива
        // Возвращает true/false в зависимости от успеха обработки
    }

    public function findSimilarCreatives(Creative $creative, int $limit = 6, int $offset = 0): array
    {
        // Проверка обязательных полей
        if (!$creative->format) {
            return ['items' => [], 'total' => 0, 'hasMore' => false];
        }

        // Получение ключевых слов исходного креатива
        $keywords = $this->getCreativeKeywords($creative->id);
        if (empty($keywords)) {
            // Fallback: поиск только по формату без ключевых слов
            return $this->findByFormatOnly($creative, $limit, $offset);
        }

        // Поиск похожих креативов
    }

    private function getStopWords(): array
    {
        // Получение стоп-слов
    }

    private function cleanTitle(?string $title): string
    {
        // Защита от NULL и пустых строк
        if ($title === null || trim($title) === '') {
            return '';
        }

        // Очистка заголовка
    }

    private function logSkippedCreative(int $creativeId, string $reason): void
    {
        // Логирование пропущенных креативов для мониторинга
    }

    private function findByFormatOnly(Creative $creative, int $limit, int $offset): array
    {
        // Fallback поиск только по формату когда нет ключевых слов
    }
}
```

### 3. Команды Artisan

#### ProcessCreativeKeywordsCommand

```php
<?php
namespace App\Console\Commands;

class ProcessCreativeKeywordsCommand extends Command
{
    protected $signature = 'creatives:process-keywords
                           {--batch=1000 : Размер батча для обработки}
                           {--force : Принудительная перегенерация всех keywords}
                           {--creative= : ID конкретного креатива}';

    protected $description = 'Обработка ключевых слов для креативов';

    public function handle(): int
    {
        // Обработка ключевых слов для существующих креативов
    }
}
```

### 4. События и Observers

#### CreativeObserver

```php
<?php
namespace App\Observers;

class CreativeObserver
{
    public function created(Creative $creative): void
    {
        // Безопасная генерация ключевых слов при создании
        if (!empty($creative->title)) {
            try {
                app(CreativeKeywordService::class)->processCreativeKeywords($creative);
            } catch (\Exception $e) {
                \Log::error("Failed to process keywords for creative {$creative->id}: " . $e->getMessage());
            }
        }
    }

    public function updated(Creative $creative): void
    {
        // Обновление ключевых слов только если заголовок изменился
        if ($creative->isDirty('title')) {
            if (!empty($creative->title)) {
                try {
                    app(CreativeKeywordService::class)->processCreativeKeywords($creative);
                } catch (\Exception $e) {
                    \Log::error("Failed to update keywords for creative {$creative->id}: " . $e->getMessage());
                }
            } else {
                // Если заголовок стал пустым, удаляем все ключевые слова
                $creative->keywords()->delete();
            }
        }
    }

    public function deleted(Creative $creative): void
    {
        // Очистка ключевых слов при удалении (CASCADE в БД)
        // Дополнительная очистка если нужно
    }
}
```

## План внедрения

### Фаза 1: Структура данных

1. **Создать миграцию** для таблицы `creative_keywords`
2. **Обновить миграцию** `creatives` - добавить `keywords_hash`
3. **Создать индексы** для оптимизации поиска

### Фаза 2: Базовый функционал

1. **Создать модель** `CreativeKeyword`
2. **Обновить модель** `Creative` - добавить связи и методы
3. **Создать сервис** `CreativeKeywordService`
4. **Добавить Observer** для автоматической обработки

### Фаза 3: Обработка данных

1. **Создать команду** для обработки существующих креативов
2. **Запустить обработку** всех существующих заголовков
3. **Протестировать производительность** на реальных данных

### Фаза 4: Интеграция API

1. **Обновить метод** `getSimilarCreativesWithPagination`
2. **Добавить отладочную информацию** в API ответы
3. **Протестировать качество** поиска похожих креативов

### Фаза 5: Оптимизация

1. **Кеширование** популярных ключевых слов
2. **Очереди** для асинхронной обработки больших объемов
3. **Мониторинг производительности** и настройка индексов

## Оптимизации производительности

### 1. Кеширование

```php
// Кеш популярных ключевых слов
Cache::remember("popular_keywords", 3600, function() {
    return CreativeKeyword::select('keyword')
        ->groupBy('keyword')
        ->havingRaw('COUNT(*) > 100')
        ->orderByRaw('COUNT(*) DESC')
        ->limit(1000)
        ->pluck('keyword')
        ->toArray();
});
```

### 2. Батчевая обработка

```php
// Обработка по батчам для избежания timeout
Creative::chunk(1000, function($creatives) {
    foreach($creatives as $creative) {
        $this->processCreativeKeywords($creative);
    }
});
```

### 3. Индексы для оптимизации

```sql
-- Составные индексы для быстрого поиска
CREATE INDEX idx_format_status_processed ON creatives(format, status, is_processed);
CREATE INDEX idx_keyword_multicolumn ON creative_keywords(keyword, word_length, creative_id);
```

## Мониторинг и метрики

### 1. Производительность

- Время выполнения запросов поиска похожих креативов
- Количество обработанных ключевых слов в секунду
- Размер таблицы keywords и скорость роста

### 2. Качество результатов

- Процент успешных поисков (с результатами >= 3 совпадений)
- Средние оценки релевантности от пользователей
- Клики по похожим креативам

### 3. Системные метрики

- Использование памяти при обработке больших батчей
- Нагрузка на БД при поисковых запросах
- Размер индексов и их фрагментация

## Расширения в будущем

### 1. Улучшение алгоритма

- Поддержка весов для разных типов слов
- Анализ частотности слов (TF-IDF)
- Машинное обучение для улучшения релевантности

### 2. Дополнительные фильтры

- Поиск по синонимам (внешние словари)
- Учет эмоциональной окраски слов
- Анализ трендовых ключевых слов

### 3. API расширения

- Поиск по произвольным ключевым словам
- Рекомендации ключевых слов для креативов
- Аналитика популярности слов по времени

## Обработка Edge Cases

### 1. Пустые или невалидные заголовки

```php
// Проверки в extractKeywords
if (empty($title) || trim($title) === '') {
    return [];
}

// Проверка на нечитаемые символы
if (!preg_match('/[\p{L}\p{N}]/u', $title)) {
    \Log::warning("Creative {$creativeId} has unreadable title: " . $title);
    return [];
}
```

### 2. Отсутствующий формат

```php
// В findSimilarCreatives
if (!$creative->format) {
    \Log::info("Creative {$creative->id} has no format, skipping similar search");
    return ['items' => [], 'total' => 0, 'hasMore' => false];
}
```

### 3. Недостаток ключевых слов

```php
// Fallback стратегия если < 3 ключевых слов
if (count($keywords) < 3) {
    // Поиск с пониженным требованием (1-2 совпадения)
    return $this->findWithLowerThreshold($creative, $keywords, $limit, $offset);
}
```

### 4. Обработка ошибок парсинга

```php
try {
    $keywords = $this->extractKeywords($creative->title);
} catch (\Exception $e) {
    \Log::error("Keyword extraction failed for creative {$creative->id}: " . $e->getMessage());
    // Сохраняем креатив с пометкой об ошибке
    $this->markKeywordProcessingFailed($creative->id, $e->getMessage());
    return false;
}
```

### 5. Метрики надежности

- Процент пропущенных креативов (по причинам)
- Время восстановления после сбоев
- Корректность CASCADE удаления
- Процент креативов с пустыми заголовками
- Процент креативов без достаточного количества ключевых слов

## Заключение

Данная система обеспечивает точный и быстрый поиск похожих креативов на основе семантического анализа заголовков, сохраняя высокую производительность благодаря оптимизированной структуре данных и индексам. Система устойчива к отсутствующим данным и некорректным входным значениям благодаря комплексной обработке edge cases.

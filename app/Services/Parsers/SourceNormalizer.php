<?php

namespace App\Services\Parsers;

use App\Models\AdSource;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * Нормализатор источников для парсеров креативов
 * 
 * Преобразует названия источников в ID из таблицы ad_sources
 * с кешированием для производительности
 */
class SourceNormalizer
{
    private const CACHE_TTL = 86400; // 24 часа
    private const CACHE_KEY = 'source_normalizer.name_to_id_map';

    /**
     * Кешированная карта source_name -> ID
     */
    private static array $sourceMap = [];

    /**
     * Флаг инициализации карты
     */
    private static bool $mapInitialized = false;

    /**
     * Преобразует название источника в source_id
     * 
     * @param string $sourceName Название источника (например, "push_house", "tiktok")
     * @return int|null ID источника из таблицы ad_sources или null если не найден
     */
    public static function normalizeSourceName(string $sourceName): ?int
    {
        if (empty(trim($sourceName))) {
            return null;
        }

        $normalizedName = strtolower(trim($sourceName));

        // Инициализируем карту если еще не сделано
        if (!self::$mapInitialized) {
            self::loadSourceMap();
        }

        // Ищем в кешированной карте
        return self::$sourceMap[$normalizedName] ?? null;
    }

    /**
     * Пакетное преобразование названий источников в ID
     * 
     * @param array $sourceNames Массив названий источников
     * @return array Ассоциативный массив [название => ID]
     */
    public static function normalizeBatch(array $sourceNames): array
    {
        if (empty($sourceNames)) {
            return [];
        }

        if (!self::$mapInitialized) {
            self::loadSourceMap();
        }

        $result = [];
        foreach ($sourceNames as $name) {
            $normalizedName = strtolower(trim($name));
            $result[$name] = self::$sourceMap[$normalizedName] ?? null;
        }

        return $result;
    }

    /**
     * Загружает карту соответствий название -> ID из кеша или БД
     */
    private static function loadSourceMap(): void
    {
        self::$sourceMap = Cache::remember(
            self::CACHE_KEY,
            self::CACHE_TTL,
            function () {
                return self::buildSourceMapFromDatabase();
            }
        );

        self::$mapInitialized = true;
    }

    /**
     * Строит карту соответствий из базы данных
     * 
     * @return array Ассоциативный массив [source_name => ID]
     */
    private static function buildSourceMapFromDatabase(): array
    {
        $sources = AdSource::select(['id', 'source_name'])
            ->get();

        $map = [];

        foreach ($sources as $source) {
            // Нормализуем название для поиска
            $normalizedName = strtolower(trim($source->source_name));
            $map[$normalizedName] = $source->id;
        }

        Log::info('SourceNormalizer: Loaded source map', [
            'total_mappings' => count($map),
            'total_sources' => $sources->count()
        ]);

        return $map;
    }

    /**
     * Проверяет валидность названия источника
     * 
     * @param string $sourceName Название источника для проверки
     * @return bool true если источник валиден и существует в БД
     */
    public static function isValidSourceName(string $sourceName): bool
    {
        return self::normalizeSourceName($sourceName) !== null;
    }

    /**
     * Получает информацию об источнике по названию
     * 
     * @param string $sourceName Название источника
     * @return array|null Массив с данными источника или null
     */
    public static function getSourceInfo(string $sourceName): ?array
    {
        $sourceId = self::normalizeSourceName($sourceName);

        if (!$sourceId) {
            return null;
        }

        $source = AdSource::find($sourceId);

        if (!$source) {
            return null;
        }

        return [
            'id' => $source->id,
            'source_name' => $source->source_name,
            'source_display_name' => $source->source_display_name,
        ];
    }

    /**
     * Создает новый источник если он не существует
     * 
     * @param string $sourceName Системное название источника
     * @param string|null $displayName Отображаемое название (опционально)
     * @return int ID созданного или существующего источника
     */
    public static function createIfNotExists(string $sourceName, ?string $displayName = null): int
    {
        $existingId = self::normalizeSourceName($sourceName);

        if ($existingId) {
            return $existingId;
        }

        // Создаем новый источник
        $source = AdSource::create([
            'source_name' => strtolower(trim($sourceName)),
            'source_display_name' => $displayName ?? ucwords(str_replace('_', ' ', $sourceName)),
        ]);

        // Очищаем кеш для перезагрузки карты
        self::clearCache();

        Log::info('SourceNormalizer: Created new source', [
            'source_name' => $source->source_name,
            'source_display_name' => $source->source_display_name,
            'id' => $source->id
        ]);

        return $source->id;
    }

    /**
     * Принудительно очищает кеш и перезагружает карту
     */
    public static function clearCache(): void
    {
        Cache::forget(self::CACHE_KEY);
        self::$sourceMap = [];
        self::$mapInitialized = false;

        Log::info('SourceNormalizer: Cache cleared');
    }

    /**
     * Получает статистику по кешированной карте
     * 
     * @return array Статистика
     */
    public static function getStats(): array
    {
        if (!self::$mapInitialized) {
            self::loadSourceMap();
        }

        return [
            'total_mappings' => count(self::$sourceMap),
            'cache_initialized' => self::$mapInitialized,
            'available_sources' => array_keys(self::$sourceMap),
        ];
    }

    /**
     * Получает список всех доступных источников
     * 
     * @return array Массив названий источников
     */
    public static function getAvailableSources(): array
    {
        if (!self::$mapInitialized) {
            self::loadSourceMap();
        }

        return array_keys(self::$sourceMap);
    }

    /**
     * Fallback метод для обработки неизвестных источников
     * 
     * @param string $sourceName Неизвестное название источника
     * @param bool $autoCreate Автоматически создать источник если не найден
     * @return int|null ID источника или null
     */
    public static function handleUnknownSource(string $sourceName, bool $autoCreate = false): ?int
    {
        Log::warning('SourceNormalizer: Unknown source name encountered', [
            'source_name' => $sourceName,
            'normalized_name' => strtolower(trim($sourceName)),
            'auto_create' => $autoCreate
        ]);

        if ($autoCreate) {
            return self::createIfNotExists($sourceName);
        }

        return null;
    }
}

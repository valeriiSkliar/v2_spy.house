<?php

namespace App\Services\Parsers;

use App\Models\Browser;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * Нормализатор браузеров для парсеров креативов
 * 
 * Преобразует названия браузеров в ID из таблицы browsers
 * с кешированием для производительности и нормализацией названий
 * 
 * Использует поле 'browser' из модели Browser
 */
class BrowserNormalizer
{
    private const CACHE_TTL = 86400; // 24 часа
    private const CACHE_KEY = 'browser_normalizer.name_to_id_map';

    /**
     * Кешированная карта browser -> ID
     */
    private static array $browserMap = [];

    /**
     * Флаг инициализации карты
     */
    private static bool $mapInitialized = false;

    /**
     * Маппинг альтернативных названий браузеров к стандартным
     */
    private const BROWSER_ALIASES = [
        'chrome' => 'Chrome',
        'google chrome' => 'Chrome',
        'chromium' => 'Chrome',
        'firefox' => 'Firefox',
        'mozilla firefox' => 'Firefox',
        'mozilla' => 'Firefox',
        'safari' => 'Safari',
        'webkit' => 'Safari',
        'edge' => 'Edge',
        'microsoft edge' => 'Edge',
        'ie' => 'Internet Explorer',
        'internet explorer' => 'Internet Explorer',
        'opera' => 'Opera',
        'opera mini' => 'Opera',
        'yandex' => 'Yandex Browser',
        'yandex browser' => 'Yandex Browser',
        'samsung browser' => 'Samsung Browser',
        'samsung' => 'Samsung Browser',
        'uc browser' => 'UC Browser',
        'uc' => 'UC Browser',
        'vivaldi' => 'Vivaldi',
        'brave' => 'Brave',
        'tor' => 'Tor Browser',
        'tor browser' => 'Tor Browser',
    ];

    /**
     * Преобразует название браузера в browser_id
     * 
     * @param string $browserName Название браузера (например, "Chrome", "firefox")
     * @return int|null ID браузера из таблицы browsers или null если не найден
     */
    public static function normalizeBrowserName(string $browserName): ?int
    {
        if (empty(trim($browserName))) {
            return null;
        }

        $normalizedName = self::normalizeName($browserName);

        // Инициализируем карту если еще не сделано
        if (!self::$mapInitialized) {
            self::loadBrowserMap();
        }

        // Ищем в кешированной карте
        return self::$browserMap[$normalizedName] ?? null;
    }

    /**
     * Пакетное преобразование названий браузеров в ID
     * 
     * @param array $browserNames Массив названий браузеров
     * @return array Ассоциативный массив [название => ID]
     */
    public static function normalizeBatch(array $browserNames): array
    {
        if (empty($browserNames)) {
            return [];
        }

        if (!self::$mapInitialized) {
            self::loadBrowserMap();
        }

        $result = [];
        foreach ($browserNames as $name) {
            $normalizedName = self::normalizeName($name);
            $result[$name] = self::$browserMap[$normalizedName] ?? null;
        }

        return $result;
    }

    /**
     * Нормализует название браузера
     * 
     * @param string $browserName Исходное название
     * @return string Нормализованное название
     */
    private static function normalizeName(string $browserName): string
    {
        $normalized = strtolower(trim($browserName));

        // Удаляем версии и дополнительную информацию
        $normalized = preg_replace('/\s+\d+(\.\d+)*.*$/', '', $normalized);
        $normalized = preg_replace('/\s+v\d+.*$/', '', $normalized);

        // Применяем алиасы
        if (isset(self::BROWSER_ALIASES[$normalized])) {
            return self::BROWSER_ALIASES[$normalized];
        }

        // Возвращаем с заглавной буквы для консистентности
        return ucfirst($normalized);
    }

    /**
     * Загружает карту соответствий название -> ID из кеша или БД
     */
    private static function loadBrowserMap(): void
    {
        self::$browserMap = Cache::remember(
            self::CACHE_KEY,
            self::CACHE_TTL,
            function () {
                return self::buildBrowserMapFromDatabase();
            }
        );

        self::$mapInitialized = true;
    }

    /**
     * Строит карту соответствий из базы данных
     * 
     * @return array Ассоциативный массив [browser => ID]
     */
    private static function buildBrowserMapFromDatabase(): array
    {
        $browsers = Browser::select(['id', 'browser'])
            ->get();

        $map = [];

        foreach ($browsers as $browser) {
            if (!empty($browser->browser)) {
                // Прямое соответствие
                $map[$browser->browser] = $browser->id;

                // Добавляем нормализованную версию
                $normalized = self::normalizeName($browser->browser);
                if ($normalized !== $browser->browser) {
                    $map[$normalized] = $browser->id;
                }

                // Добавляем lowercase версию для поиска
                $map[strtolower($browser->browser)] = $browser->id;
            }
        }

        Log::info('BrowserNormalizer: Loaded browser map', [
            'total_mappings' => count($map),
            'total_browsers' => $browsers->count()
        ]);

        return $map;
    }

    /**
     * Проверяет валидность названия браузера
     * 
     * @param string $browserName Название браузера для проверки
     * @return bool true если браузер существует в БД
     */
    public static function isValidBrowserName(string $browserName): bool
    {
        return self::normalizeBrowserName($browserName) !== null;
    }

    /**
     * Получает информацию о браузере по названию
     * 
     * @param string $browserName Название браузера
     * @return array|null Массив с данными браузера или null
     */
    public static function getBrowserInfo(string $browserName): ?array
    {
        $browserId = self::normalizeBrowserName($browserName);

        if (!$browserId) {
            return null;
        }

        $browser = Browser::find($browserId);

        if (!$browser) {
            return null;
        }

        return [
            'id' => $browser->id,
            'browser' => $browser->browser,
            'created_at' => $browser->created_at,
            'updated_at' => $browser->updated_at,
        ];
    }

    /**
     * Создает новый браузер если он не существует
     * 
     * @param string $browserName Название браузера
     * @return int|null ID созданного или существующего браузера
     */
    public static function getOrCreateBrowser(string $browserName): ?int
    {
        if (empty(trim($browserName))) {
            return null;
        }

        // Сначала проверяем существующий
        $browserId = self::normalizeBrowserName($browserName);
        if ($browserId) {
            return $browserId;
        }

        // Создаем новый
        try {
            $normalizedName = self::normalizeName($browserName);

            $browser = Browser::firstOrCreate(
                ['browser' => $normalizedName]
            );

            // Очищаем кеш для перезагрузки
            self::clearCache();

            Log::info('BrowserNormalizer: Created new browser', [
                'original_name' => $browserName,
                'normalized_name' => $normalizedName,
                'browser_id' => $browser->id
            ]);

            return $browser->id;
        } catch (\Exception $e) {
            Log::error('BrowserNormalizer: Failed to create browser', [
                'browser' => $browserName,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * Принудительно очищает кеш и перезагружает карту
     */
    public static function clearCache(): void
    {
        Cache::forget(self::CACHE_KEY);
        self::$browserMap = [];
        self::$mapInitialized = false;

        Log::info('BrowserNormalizer: Cache cleared');
    }

    /**
     * Получает статистику по кешированной карте
     * 
     * @return array Статистика
     */
    public static function getStats(): array
    {
        if (!self::$mapInitialized) {
            self::loadBrowserMap();
        }

        return [
            'total_mappings' => count(self::$browserMap),
            'cache_initialized' => self::$mapInitialized,
            'available_aliases' => count(self::BROWSER_ALIASES),
        ];
    }

    /**
     * Получает список всех доступных названий браузеров
     * 
     * @return array Массив названий браузеров
     */
    public static function getAvailableNames(): array
    {
        if (!self::$mapInitialized) {
            self::loadBrowserMap();
        }

        return array_keys(self::$browserMap);
    }

    /**
     * Получает список поддерживаемых алиасов
     * 
     * @return array Массив алиасов браузеров
     */
    public static function getSupportedAliases(): array
    {
        return self::BROWSER_ALIASES;
    }

    /**
     * Fallback метод для обработки неизвестных браузеров
     * 
     * @param string $browserName Неизвестное название браузера
     * @return int|null ID браузера по умолчанию или null
     */
    public static function handleUnknownBrowser(string $browserName): ?int
    {
        Log::warning('BrowserNormalizer: Unknown browser encountered', [
            'browser' => $browserName,
            'normalized_name' => self::normalizeName($browserName)
        ]);

        // Можно вернуть ID браузера по умолчанию (например, "Unknown")
        // Или создать новый браузер автоматически
        return self::getOrCreateBrowser($browserName);
    }
}

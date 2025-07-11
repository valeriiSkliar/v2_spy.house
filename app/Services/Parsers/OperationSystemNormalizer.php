<?php

namespace App\Services\Parsers;

use App\Enums\Frontend\OperationSystem;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * Нормализатор операционных систем для парсеров креативов
 * 
 * Преобразует различные варианты названий ОС от API в стандартизированные
 * enum значения с кешированием для производительности
 */
class OperationSystemNormalizer
{
    private const CACHE_TTL = 86400; // 24 часа
    private const CACHE_KEY = 'os_normalizer.variant_to_enum_map';

    /**
     * Кешированная карта вариантов названий ОС -> enum значение
     */
    private static array $osMap = [];

    /**
     * Флаг инициализации карты
     */
    private static bool $mapInitialized = false;

    /**
     * Преобразует название операционной системы в стандартизированное enum значение
     * 
     * @param string $osName Название ОС от API (например, "Windows 10", "iOS 15", "mac os", "ubuntu")
     * @return string|null Стандартизированное значение enum или null если не найдено
     */
    public static function normalizeOperationSystem(string $osName): ?string
    {
        if (empty(trim($osName))) {
            return null;
        }

        $normalizedName = strtolower(trim($osName));

        // Инициализируем карту если еще не сделано
        if (!self::$mapInitialized) {
            self::loadOsMap();
        }

        // Ищем в кешированной карте
        return self::$osMap[$normalizedName] ?? null;
    }

    /**
     * Пакетное преобразование названий ОС в enum значения
     * 
     * @param array $osNames Массив названий ОС
     * @return array Ассоциативный массив [название => enum_значение]
     */
    public static function normalizeBatch(array $osNames): array
    {
        if (empty($osNames)) {
            return [];
        }

        if (!self::$mapInitialized) {
            self::loadOsMap();
        }

        $result = [];
        foreach ($osNames as $osName) {
            $normalizedName = strtolower(trim($osName));
            $result[$osName] = self::$osMap[$normalizedName] ?? null;
        }

        return $result;
    }

    /**
     * Загружает карту соответствий название ОС -> enum значение из кеша или строит заново
     */
    private static function loadOsMap(): void
    {
        self::$osMap = Cache::remember(
            self::CACHE_KEY,
            self::CACHE_TTL,
            function () {
                return self::buildOsMapFromEnum();
            }
        );

        self::$mapInitialized = true;
    }

    /**
     * Строит карту соответствий из enum OperationSystem
     * 
     * @return array Ассоциативный массив [вариант_названия => enum_значение]
     */
    private static function buildOsMapFromEnum(): array
    {
        $map = [];

        // Получаем все enum значения
        $operatingSystems = OperationSystem::cases();

        foreach ($operatingSystems as $os) {
            $enumValue = $os->value;

            // Добавляем основное enum значение
            $map[$enumValue] = $enumValue;

            // Добавляем варианты для каждой ОС
            match ($os) {
                OperationSystem::ANDROID => self::addAndroidVariants($map, $enumValue),
                OperationSystem::WINDOWS => self::addWindowsVariants($map, $enumValue),
                OperationSystem::MACOS => self::addMacOSVariants($map, $enumValue),
                OperationSystem::IOS => self::addIOSVariants($map, $enumValue),
                OperationSystem::LINUX => self::addLinuxVariants($map, $enumValue),
                OperationSystem::CHROMEOS => self::addChromeOSVariants($map, $enumValue),
                OperationSystem::KINDLE => self::addKindleVariants($map, $enumValue),
                OperationSystem::PLAYSTATION => self::addPlayStationVariants($map, $enumValue),
                OperationSystem::XBOX => self::addXboxVariants($map, $enumValue),
                OperationSystem::WEBOS => self::addWebOSVariants($map, $enumValue),
                OperationSystem::OTHER => self::addOtherVariants($map, $enumValue),
            };
        }

        Log::info('OperationSystemNormalizer: Loaded OS mapping', [
            'total_mappings' => count($map),
            'total_enum_values' => count($operatingSystems)
        ]);

        return $map;
    }

    /**
     * Добавляет варианты названий для Android
     */
    private static function addAndroidVariants(array &$map, string $enumValue): void
    {
        $variants = [
            'android',
            'android mobile',
            'android os',
            'google android',
            'android tablet',
            'android tv',
            'android wear',
            'android auto',
            'lineageos',
            'cyanogenmod',
        ];

        // Добавляем версии Android
        for ($version = 4; $version <= 15; $version++) {
            $variants[] = "android $version";
            $variants[] = "android $version.0";
            $variants[] = "android $version.1";
            $variants[] = "android os $version";
        }

        foreach ($variants as $variant) {
            $map[strtolower($variant)] = $enumValue;
        }
    }

    /**
     * Добавляет варианты названий для Windows
     */
    private static function addWindowsVariants(array &$map, string $enumValue): void
    {
        $variants = [
            'windows',
            'microsoft windows',
            'win',
            'windows nt',
            'windows xp',
            'windows vista',
            'windows 7',
            'windows 8',
            'windows 8.1',
            'windows 10',
            'windows 11',
            'windows phone',
            'windows mobile',
            'windows rt',
            'windows server',
            'win32',
            'win64',
        ];

        foreach ($variants as $variant) {
            $map[strtolower($variant)] = $enumValue;
        }
    }

    /**
     * Добавляет варианты названий для macOS
     */
    private static function addMacOSVariants(array &$map, string $enumValue): void
    {
        $variants = [
            'macos',
            'macosx',
            'mac os',
            'mac os x',
            'mac osx',
            'mac',
            'apple macos',
            'apple mac os',
            'osx',
            'os x',
            'darwin',
            'macintosh',
            'mac os monterey',
            'mac os big sur',
            'mac os catalina',
            'mac os mojave',
            'mac os high sierra',
            'mac os sierra',
        ];

        foreach ($variants as $variant) {
            $map[strtolower($variant)] = $enumValue;
        }
    }

    /**
     * Добавляет варианты названий для iOS
     */
    private static function addIOSVariants(array &$map, string $enumValue): void
    {
        $variants = [
            'ios',
            'apple ios',
            'iphone os',
            'iphone',
            'ipad',
            'ipados',
            'ipod',
            'ipad os',
        ];

        // Добавляем версии iOS
        for ($version = 7; $version <= 18; $version++) {
            $variants[] = "ios $version";
            $variants[] = "ios $version.0";
            $variants[] = "ios $version.1";
            $variants[] = "iphone os $version";
        }

        foreach ($variants as $variant) {
            $map[strtolower($variant)] = $enumValue;
        }
    }

    /**
     * Добавляет варианты названий для Linux
     */
    private static function addLinuxVariants(array &$map, string $enumValue): void
    {
        $variants = [
            'linux',
            'gnu/linux',
            'ubuntu',
            'debian',
            'fedora',
            'centos',
            'red hat',
            'redhat',
            'suse',
            'opensuse',
            'mint',
            'arch',
            'arch linux',
            'manjaro',
            'elementary',
            'kali',
            'kali linux',
            'alpine',
            'gentoo',
            'slackware',
            'puppy linux',
            'tux',
        ];

        foreach ($variants as $variant) {
            $map[strtolower($variant)] = $enumValue;
        }
    }

    /**
     * Добавляет варианты названий для Chrome OS
     */
    private static function addChromeOSVariants(array &$map, string $enumValue): void
    {
        $variants = [
            'chromeos',
            'chrome os',
            'google chrome os',
            'chromium os',
            'chromebook',
            'chromebox',
        ];

        foreach ($variants as $variant) {
            $map[strtolower($variant)] = $enumValue;
        }
    }

    /**
     * Добавляет варианты названий для Kindle
     */
    private static function addKindleVariants(array &$map, string $enumValue): void
    {
        $variants = [
            'kindle',
            'amazon kindle',
            'kindle fire',
            'fire os',
            'fireos',
        ];

        foreach ($variants as $variant) {
            $map[strtolower($variant)] = $enumValue;
        }
    }

    /**
     * Добавляет варианты названий для PlayStation
     */
    private static function addPlayStationVariants(array &$map, string $enumValue): void
    {
        $variants = [
            'playstation',
            'playstation 3',
            'playstation 4',
            'playstation 5',
            'ps3',
            'ps4',
            'ps5',
            'sony playstation',
            'playstation portable',
            'psp',
            'ps vita',
            'playstation vita',
        ];

        foreach ($variants as $variant) {
            $map[strtolower($variant)] = $enumValue;
        }
    }

    /**
     * Добавляет варианты названий для Xbox
     */
    private static function addXboxVariants(array &$map, string $enumValue): void
    {
        $variants = [
            'xbox',
            'xbox one',
            'xbox 360',
            'xbox series x',
            'xbox series s',
            'microsoft xbox',
        ];

        foreach ($variants as $variant) {
            $map[strtolower($variant)] = $enumValue;
        }
    }

    /**
     * Добавляет варианты названий для WebOS
     */
    private static function addWebOSVariants(array &$map, string $enumValue): void
    {
        $variants = [
            'webos',
            'web os',
            'palm webos',
            'hp webos',
            'lg webos',
            'palm os',
        ];

        foreach ($variants as $variant) {
            $map[strtolower($variant)] = $enumValue;
        }
    }

    /**
     * Добавляет варианты для неизвестных/прочих ОС
     */
    private static function addOtherVariants(array &$map, string $enumValue): void
    {
        $variants = [
            'other',
            'unknown',
            'unidentified',
            'not specified',
            'n/a',
            'none',
            'beos',
            'haiku',
            'freebsd',
            'openbsd',
            'netbsd',
            'solaris',
            'aix',
            'qnx',
            'symbian',
            'blackberry',
            'tizen',
            'sailfish',
            'kai os',
            'kaios',
        ];

        foreach ($variants as $variant) {
            $map[strtolower($variant)] = $enumValue;
        }
    }

    /**
     * Проверяет валидность названия операционной системы
     * 
     * @param string $osName Название ОС для проверки
     * @return bool true если название валидно и может быть нормализовано
     */
    public static function isValidOperationSystem(string $osName): bool
    {
        return self::normalizeOperationSystem($osName) !== null;
    }

    /**
     * Получает информацию об операционной системе по названию
     * 
     * @param string $osName Название ОС
     * @return array|null Массив с данными ОС или null
     */
    public static function getOperationSystemInfo(string $osName): ?array
    {
        $enumValue = self::normalizeOperationSystem($osName);

        if (!$enumValue) {
            return null;
        }

        $os = OperationSystem::from($enumValue);

        return [
            'enum_value' => $os->value,
            'label' => $os->label(),
            'translated_label' => $os->translatedLabel(),
            'original_input' => $osName,
        ];
    }

    /**
     * Принудительно очищает кеш и перезагружает карту
     */
    public static function clearCache(): void
    {
        Cache::forget(self::CACHE_KEY);
        self::$osMap = [];
        self::$mapInitialized = false;

        Log::info('OperationSystemNormalizer: Cache cleared');
    }

    /**
     * Получает статистику по кешированной карте
     * 
     * @return array Статистика
     */
    public static function getStats(): array
    {
        if (!self::$mapInitialized) {
            self::loadOsMap();
        }

        $enumStats = [];
        foreach (OperationSystem::cases() as $os) {
            $enumStats[$os->value] = 0;
        }

        foreach (self::$osMap as $variant => $enumValue) {
            if (isset($enumStats[$enumValue])) {
                $enumStats[$enumValue]++;
            }
        }

        return [
            'total_mappings' => count(self::$osMap),
            'enum_distribution' => $enumStats,
            'cache_initialized' => self::$mapInitialized,
        ];
    }

    /**
     * Получает список всех доступных вариантов названий ОС
     * 
     * @return array Массив вариантов названий
     */
    public static function getAvailableVariants(): array
    {
        if (!self::$mapInitialized) {
            self::loadOsMap();
        }

        return array_keys(self::$osMap);
    }

    /**
     * Fallback метод для обработки неизвестных названий ОС
     * 
     * @param string $osName Неизвестное название ОС
     * @return string|null Enum значение по умолчанию или null
     */
    public static function handleUnknownOperationSystem(string $osName): ?string
    {
        Log::warning('OperationSystemNormalizer: Unknown OS name encountered', [
            'os_name' => $osName,
            'normalized_name' => strtolower(trim($osName))
        ]);

        // Можно вернуть OperationSystem::OTHER->value для неизвестных ОС
        // Или null для обязательной обработки в вызывающем коде
        return OperationSystem::OTHER->value;
    }

    /**
     * Интеллектуальный поиск по частичному совпадению
     * Используется как fallback когда точное совпадение не найдено
     * 
     * @param string $osName Название ОС для поиска
     * @return string|null Найденное enum значение или null
     */
    public static function findByPartialMatch(string $osName): ?string
    {
        if (!self::$mapInitialized) {
            self::loadOsMap();
        }

        $searchName = strtolower(trim($osName));

        // Исключаем слишком общие варианты из поиска
        $excludeFromPartialSearch = ['other', 'unknown', 'unidentified', 'not specified', 'n/a', 'none'];

        // Ищем частичные совпадения, исключая слишком общие термины
        foreach (self::$osMap as $variant => $enumValue) {
            // Пропускаем слишком общие варианты
            if (in_array($variant, $excludeFromPartialSearch)) {
                continue;
            }

            // Проверяем частичные совпадения только для конкретных ОС
            // Вариант должен быть достаточно длинным (минимум 3 символа) и не общим
            if (strlen($variant) >= 3) {
                // Ищем вариант внутри входного названия
                if (str_contains($searchName, $variant)) {
                    Log::info('OperationSystemNormalizer: Found partial match', [
                        'input' => $osName,
                        'matched_variant' => $variant,
                        'enum_value' => $enumValue
                    ]);
                    return $enumValue;
                }
            }
        }

        return null;
    }

    /**
     * Улучшенная нормализация с fallback на частичный поиск
     * 
     * @param string $osName Название ОС
     * @param bool $usePartialMatch Использовать частичный поиск как fallback
     * @return string|null Нормализованное значение или null
     */
    public static function normalizeWithFallback(string $osName, bool $usePartialMatch = true): ?string
    {
        // Сначала пробуем точное совпадение
        $exactMatch = self::normalizeOperationSystem($osName);
        if ($exactMatch !== null) {
            return $exactMatch;
        }

        // Если точного совпадения нет и разрешен частичный поиск
        if ($usePartialMatch) {
            $partialMatch = self::findByPartialMatch($osName);
            if ($partialMatch !== null) {
                return $partialMatch;
            }
        }

        // Последний fallback - возвращаем OTHER для любых неизвестных значений
        return self::handleUnknownOperationSystem($osName);
    }
}

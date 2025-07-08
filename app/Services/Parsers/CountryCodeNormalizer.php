<?php

namespace App\Services\Parsers;

use App\Models\Frontend\IsoEntity;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * Нормализатор кодов стран для парсеров креативов
 * 
 * Преобразует ISO2/ISO3 коды стран в ID из таблицы iso_entities
 * с кешированием для производительности
 */
class CountryCodeNormalizer
{
    private const CACHE_TTL = 86400; // 24 часа
    private const CACHE_KEY = 'country_normalizer.iso_to_id_map';

    /**
     * Кешированная карта ISO2 -> ID
     */
    private static array $countryMap = [];

    /**
     * Флаг инициализации карты
     */
    private static bool $mapInitialized = false;

    /**
     * Преобразует код страны (ISO2 или ISO3) в country_id
     * 
     * @param string $countryCode Код страны (например, "BR", "USA")
     * @return int|null ID страны из таблицы iso_entities или null если не найдена
     */
    public static function normalizeCountryCode(string $countryCode): ?int
    {
        if (empty(trim($countryCode))) {
            return null;
        }

        $normalizedCode = strtoupper(trim($countryCode));

        // Инициализируем карту если еще не сделано
        if (!self::$mapInitialized) {
            self::loadCountryMap();
        }

        // Ищем в кешированной карте
        return self::$countryMap[$normalizedCode] ?? null;
    }

    /**
     * Пакетное преобразование кодов стран в ID
     * 
     * @param array $countryCodes Массив кодов стран
     * @return array Ассоциативный массив [код => ID]
     */
    public static function normalizeBatch(array $countryCodes): array
    {
        if (empty($countryCodes)) {
            return [];
        }

        if (!self::$mapInitialized) {
            self::loadCountryMap();
        }

        $result = [];
        foreach ($countryCodes as $code) {
            $normalizedCode = strtoupper(trim($code));
            $result[$code] = self::$countryMap[$normalizedCode] ?? null;
        }

        return $result;
    }

    /**
     * Загружает карту соответствий ISO код -> ID из кеша или БД
     */
    private static function loadCountryMap(): void
    {
        self::$countryMap = Cache::remember(
            self::CACHE_KEY,
            self::CACHE_TTL,
            function () {
                return self::buildCountryMapFromDatabase();
            }
        );

        self::$mapInitialized = true;
    }

    /**
     * Строит карту соответствий из базы данных
     * 
     * @return array Ассоциативный массив [ISO_код => ID]
     */
    private static function buildCountryMapFromDatabase(): array
    {
        $countries = IsoEntity::countries()
            ->active()
            ->select(['id', 'iso_code_2', 'iso_code_3'])
            ->get();

        $map = [];

        foreach ($countries as $country) {
            // Маппинг по ISO2 коду
            if (!empty($country->iso_code_2)) {
                $map[$country->iso_code_2] = $country->id;
            }

            // Маппинг по ISO3 коду (для совместимости)
            if (!empty($country->iso_code_3)) {
                $map[$country->iso_code_3] = $country->id;
            }
        }

        Log::info('CountryCodeNormalizer: Loaded country map', [
            'total_mappings' => count($map),
            'total_countries' => $countries->count()
        ]);

        return $map;
    }

    /**
     * Проверяет валидность кода страны
     * 
     * @param string $countryCode Код страны для проверки
     * @return bool true если код валиден и существует в БД
     */
    public static function isValidCountryCode(string $countryCode): bool
    {
        return self::normalizeCountryCode($countryCode) !== null;
    }

    /**
     * Получает информацию о стране по коду
     * 
     * @param string $countryCode Код страны
     * @return array|null Массив с данными страны или null
     */
    public static function getCountryInfo(string $countryCode): ?array
    {
        $countryId = self::normalizeCountryCode($countryCode);

        if (!$countryId) {
            return null;
        }

        $country = IsoEntity::find($countryId);

        if (!$country) {
            return null;
        }

        return [
            'id' => $country->id,
            'iso_code_2' => $country->iso_code_2,
            'iso_code_3' => $country->iso_code_3,
            'name' => $country->name,
            'numeric_code' => $country->numeric_code,
        ];
    }

    /**
     * Принудительно очищает кеш и перезагружает карту
     */
    public static function clearCache(): void
    {
        Cache::forget(self::CACHE_KEY);
        self::$countryMap = [];
        self::$mapInitialized = false;

        Log::info('CountryCodeNormalizer: Cache cleared');
    }

    /**
     * Получает статистику по кешированной карте
     * 
     * @return array Статистика
     */
    public static function getStats(): array
    {
        if (!self::$mapInitialized) {
            self::loadCountryMap();
        }

        $iso2Count = 0;
        $iso3Count = 0;

        foreach (array_keys(self::$countryMap) as $code) {
            if (strlen($code) === 2) {
                $iso2Count++;
            } elseif (strlen($code) === 3) {
                $iso3Count++;
            }
        }

        return [
            'total_mappings' => count(self::$countryMap),
            'iso2_codes' => $iso2Count,
            'iso3_codes' => $iso3Count,
            'cache_initialized' => self::$mapInitialized,
        ];
    }

    /**
     * Получает список всех доступных кодов стран
     * 
     * @return array Массив кодов стран
     */
    public static function getAvailableCodes(): array
    {
        if (!self::$mapInitialized) {
            self::loadCountryMap();
        }

        return array_keys(self::$countryMap);
    }

    /**
     * Fallback метод для обработки неизвестных кодов
     * 
     * @param string $countryCode Неизвестный код страны
     * @return int|null ID страны по умолчанию или null
     */
    public static function handleUnknownCode(string $countryCode): ?int
    {
        Log::warning('CountryCodeNormalizer: Unknown country code encountered', [
            'country_code' => $countryCode,
            'normalized_code' => strtoupper(trim($countryCode))
        ]);

        // Можно вернуть ID страны по умолчанию (например, "Unknown" или "XX")
        // Или null для обязательной обработки в вызывающем коде
        return null;
    }
}

<?php

namespace App\Http\DTOs;

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Contracts\Support\Jsonable;
use App\Models\Frontend\IsoEntity;
use Illuminate\Support\Facades\Cache;

/**
 * DTO для фильтров креативов
 * Обеспечивает валидацию, санитизацию и type safety для фильтров
 */
class CreativesFiltersDTO implements Arrayable, Jsonable
{
    public function __construct(
        // Основные фильтры
        public string $searchKeyword = '',
        public string $country = 'default',
        public string $dateCreation = 'default',
        public string $sortBy = 'default',
        public string $periodDisplay = 'default',
        public bool $onlyAdult = false,
        public bool $isDetailedVisible = false,

        // Пагинация
        public int $page = 1,
        public int $perPage = 12,

        // Активная вкладка
        public string $activeTab = 'push',

        // Мультиселект фильтры
        public array $advertisingNetworks = [],
        public array $languages = [],
        public array $operatingSystems = [],
        public array $browsers = [],
        public array $devices = [],
        public array $imageSizes = [],
        public array $savedSettings = [],
    ) {}

    /**
     * Создать DTO из массива данных с валидацией и санитизацией
     */
    public static function fromArray(array $data): self
    {
        // Валидируем данные
        $validationErrors = self::validate($data);
        if (!empty($validationErrors)) {
            throw new \InvalidArgumentException('Validation failed: ' . implode(', ', $validationErrors));
        }

        return new self(
            searchKeyword: self::sanitizeString($data['searchKeyword'] ?? ''),
            country: self::validateCountry($data['country'] ?? 'default'),
            dateCreation: self::validateDateOption($data['dateCreation'] ?? 'default'),
            sortBy: self::validateSortOption($data['sortBy'] ?? 'default'),
            periodDisplay: self::validatePeriodOption($data['periodDisplay'] ?? 'default'),
            onlyAdult: self::sanitizeBoolean($data['onlyAdult'] ?? false),
            isDetailedVisible: self::sanitizeBoolean($data['isDetailedVisible'] ?? false),
            page: self::validatePage($data['page'] ?? 1),
            perPage: self::validatePerPage($data['perPage'] ?? 12),
            activeTab: self::validateActiveTab($data['activeTab'] ?? 'push'),
            advertisingNetworks: self::sanitizeArray($data['advertisingNetworks'] ?? []),
            languages: self::sanitizeArray($data['languages'] ?? []),
            operatingSystems: self::sanitizeArray($data['operatingSystems'] ?? []),
            browsers: self::sanitizeArray($data['browsers'] ?? []),
            devices: self::sanitizeArray($data['devices'] ?? []),
            imageSizes: self::sanitizeArray($data['imageSizes'] ?? []),
            savedSettings: self::sanitizeArray($data['savedSettings'] ?? []),
        );
    }

    /**
     * Создать DTO из Request с автоматической санитизацией
     */
    public static function fromRequest($request): self
    {
        $data = $request->all();

        // Автоматическая санитизация без выброса исключений
        return self::fromArraySafe($data);
    }

    /**
     * Безопасное создание DTO без исключений при валидации
     */
    public static function fromArraySafe(array $data): self
    {
        return new self(
            searchKeyword: self::sanitizeString($data['searchKeyword'] ?? ''),
            country: self::validateCountry($data['country'] ?? 'default', true),
            dateCreation: self::validateDateOption($data['dateCreation'] ?? 'default', true),
            sortBy: self::validateSortOption($data['sortBy'] ?? 'default', true),
            periodDisplay: self::validatePeriodOption($data['periodDisplay'] ?? 'default', true),
            onlyAdult: self::sanitizeBoolean($data['onlyAdult'] ?? false),
            isDetailedVisible: self::sanitizeBoolean($data['isDetailedVisible'] ?? false),
            page: self::validatePage($data['page'] ?? 1, true),
            perPage: self::validatePerPage($data['perPage'] ?? 12, true),
            activeTab: self::validateActiveTab($data['activeTab'] ?? 'push', true),
            advertisingNetworks: self::sanitizeArray($data['advertisingNetworks'] ?? []),
            languages: self::sanitizeArray($data['languages'] ?? []),
            operatingSystems: self::sanitizeArray($data['operatingSystems'] ?? []),
            browsers: self::sanitizeArray($data['browsers'] ?? []),
            devices: self::sanitizeArray($data['devices'] ?? []),
            imageSizes: self::sanitizeArray($data['imageSizes'] ?? []),
            savedSettings: self::sanitizeArray($data['savedSettings'] ?? []),
        );
    }

    /**
     * Валидация всех полей
     */
    public static function validate(array $data): array
    {
        $errors = [];

        // Валидация строковых полей
        if (isset($data['searchKeyword']) && strlen($data['searchKeyword']) > 255) {
            $errors[] = 'searchKeyword must be less than 255 characters';
        }

        // Валидация country - используем новую логику
        if (isset($data['country']) && !self::isValidCountry($data['country'])) {
            $errors[] = "Invalid country: {$data['country']}";
        }

        // Валидация dateCreation
        if (isset($data['dateCreation']) && !self::isValidDateOption($data['dateCreation'])) {
            $errors[] = 'Invalid dateCreation option';
        }

        // Валидация sortBy
        if (isset($data['sortBy']) && !self::isValidSortOption($data['sortBy'])) {
            $errors[] = 'Invalid sortBy option';
        }

        // Валидация periodDisplay
        if (isset($data['periodDisplay']) && !self::isValidPeriodOption($data['periodDisplay'])) {
            $errors[] = 'Invalid periodDisplay option';
        }

        // Валидация boolean полей
        if (isset($data['onlyAdult']) && !is_bool($data['onlyAdult']) && !in_array($data['onlyAdult'], ['true', 'false', '1', '0', 1, 0])) {
            $errors[] = 'onlyAdult must be boolean';
        }

        if (isset($data['isDetailedVisible']) && !is_bool($data['isDetailedVisible']) && !in_array($data['isDetailedVisible'], ['true', 'false', '1', '0', 1, 0])) {
            $errors[] = 'isDetailedVisible must be boolean';
        }

        // Валидация пагинации
        if (isset($data['page']) && (!is_numeric($data['page']) || $data['page'] < 1 || $data['page'] > 10000)) {
            $errors[] = 'page must be between 1 and 10000';
        }

        if (isset($data['perPage']) && (!is_numeric($data['perPage']) || $data['perPage'] < 6 || $data['perPage'] > 100)) {
            $errors[] = 'perPage must be between 6 and 100';
        }

        // Валидация activeTab
        if (isset($data['activeTab']) && !self::isValidActiveTab($data['activeTab'])) {
            $errors[] = 'Invalid activeTab value';
        }

        // Валидация массивов
        $arrayFields = ['advertisingNetworks', 'languages', 'operatingSystems', 'browsers', 'devices', 'imageSizes', 'savedSettings'];
        foreach ($arrayFields as $field) {
            if (isset($data[$field]) && !is_array($data[$field])) {
                $errors[] = "{$field} must be an array";
            }
        }

        return $errors;
    }

    /**
     * Получить дефолтные значения фильтров
     */
    public static function getDefaults(): array
    {
        return [
            'searchKeyword' => '',
            'country' => 'default',
            'dateCreation' => 'default',
            'sortBy' => 'default',
            'periodDisplay' => 'default',
            'onlyAdult' => false,
            'isDetailedVisible' => false,
            'page' => 1,
            'perPage' => 12,
            'activeTab' => 'push',
            'advertisingNetworks' => [],
            'languages' => [],
            'operatingSystems' => [],
            'browsers' => [],
            'devices' => [],
            'imageSizes' => [],
            'savedSettings' => [],
        ];
    }

    /**
     * Проверить есть ли активные фильтры (отличные от дефолтных)
     */
    public function hasActiveFilters(): bool
    {
        $defaults = self::getDefaults();
        $current = $this->toArray();

        // Исключаем поля которые не считаются фильтрами
        $excludeFields = ['page', 'perPage', 'activeTab', 'isDetailedVisible'];

        foreach ($current as $key => $value) {
            if (in_array($key, $excludeFields)) {
                continue;
            }

            if ($defaults[$key] !== $value) {
                return true;
            }
        }

        return false;
    }

    /**
     * Получить количество активных фильтров
     */
    public function getActiveFiltersCount(): int
    {
        $defaults = self::getDefaults();
        $current = $this->toArray();
        $count = 0;

        // Исключаем поля которые не считаются фильтрами
        $excludeFields = ['page', 'perPage', 'activeTab', 'isDetailedVisible'];

        foreach ($current as $key => $value) {
            if (in_array($key, $excludeFields)) {
                continue;
            }

            if ($defaults[$key] !== $value) {
                $count++;
            }
        }

        return $count;
    }

    /**
     * Сбросить все фильтры к дефолтным значениям
     */
    public function reset(): self
    {
        $defaults = self::getDefaults();
        return self::fromArraySafe($defaults);
    }

    /**
     * Получить только активные фильтры (отличные от дефолтных)
     */
    public function getActiveFilters(): array
    {
        $defaults = self::getDefaults();
        $current = $this->toArray();
        $active = [];

        // Исключаем поля которые не считаются фильтрами
        $excludeFields = ['page', 'perPage', 'activeTab', 'isDetailedVisible'];

        foreach ($current as $key => $value) {
            if (in_array($key, $excludeFields)) {
                continue;
            }

            if ($defaults[$key] !== $value) {
                $active[$key] = $value;
            }
        }

        return $active;
    }

    // Методы валидации и санитизации

    private static function sanitizeString(?string $value): string
    {
        if (is_null($value)) return '';
        return trim(strip_tags((string)$value));
    }

    private static function sanitizeBoolean($value): bool
    {
        if (is_bool($value)) return $value;
        if (is_string($value)) {
            $lowercaseValue = strtolower(trim($value));
            return in_array($lowercaseValue, ['true', '1', 'on', 'yes']);
        }
        if (is_numeric($value)) {
            return (int)$value === 1;
        }
        return (bool)$value;
    }

    private static function sanitizeArray($value): array
    {
        if (!is_array($value)) return [];
        return array_values(array_filter($value, function ($item) {
            return !empty($item) && is_string($item);
        }));
    }

    private static function validateCountry(string $value, bool $safe = false): string
    {
        if (self::isValidCountry($value)) {
            return $value;
        }

        if ($safe) {
            return 'default';
        }

        throw new \InvalidArgumentException("Invalid country: {$value}");
    }

    private static function isValidCountry(string $value): bool
    {
        // Кешируем список валидных кодов стран на 1 час
        $validCountries = Cache::remember('creatives_filters.valid_countries', 3600, function () {
            // Специальные значения, которые всегда разрешены
            $specialValues = ['default'];

            // Получаем активные страны из базы данных
            $countriesFromDb = IsoEntity::countries()
                ->active()
                ->pluck('iso_code_2')
                ->map(function ($code) {
                    return strtoupper($code);
                })
                ->toArray();

            return array_merge($specialValues, $countriesFromDb);
        });

        // Приводим к верхнему регистру для сравнения, кроме специальных значений
        $normalizedValue = ($value === 'default') ? $value : strtoupper($value);

        return in_array($normalizedValue, $validCountries);
    }

    private static function validateDateOption(string $value, bool $safe = false): string
    {
        if (self::isValidDateOption($value)) {
            return $value;
        }

        if ($safe) {
            return 'default';
        }

        throw new \InvalidArgumentException("Invalid dateCreation: {$value}");
    }

    private static function isValidDateOption(string $value): bool
    {
        $validOptions = ['default', 'today', 'yesterday', 'last7', 'last30', 'last90', 'thisMonth', 'lastMonth', 'thisYear', 'lastYear'];
        return in_array($value, $validOptions);
    }

    private static function validateSortOption(string $value, bool $safe = false): string
    {
        if (self::isValidSortOption($value)) {
            return $value;
        }

        if ($safe) {
            return 'default';
        }

        throw new \InvalidArgumentException("Invalid sortBy: {$value}");
    }

    private static function isValidSortOption(string $value): bool
    {
        $validOptions = ['default', 'creation', 'activity', 'popularity', 'byCreationDate', 'byActivity', 'byPopularity'];
        return in_array($value, $validOptions);
    }

    private static function validatePeriodOption(string $value, bool $safe = false): string
    {
        if (self::isValidPeriodOption($value)) {
            return $value;
        }

        if ($safe) {
            return 'default';
        }

        throw new \InvalidArgumentException("Invalid periodDisplay: {$value}");
    }

    private static function isValidPeriodOption(string $value): bool
    {
        $validOptions = ['default', 'today', 'yesterday', 'last7', 'last30', 'last90', 'thisMonth', 'lastMonth', 'thisYear', 'lastYear'];
        return in_array($value, $validOptions);
    }

    private static function validatePage($value, bool $safe = false): int
    {
        $page = (int)$value;
        if ($page >= 1 && $page <= 10000) {
            return $page;
        }

        if ($safe) {
            return max(1, min(10000, $page));
        }

        throw new \InvalidArgumentException("Invalid page: {$value}");
    }

    private static function validatePerPage($value, bool $safe = false): int
    {
        $perPage = (int)$value;
        $allowedValues = [6, 12, 24, 48, 96];

        if (in_array($perPage, $allowedValues)) {
            return $perPage;
        }

        if ($safe) {
            // Найти ближайшее допустимое значение
            $closest = $allowedValues[0];
            foreach ($allowedValues as $allowed) {
                if (abs($perPage - $allowed) < abs($perPage - $closest)) {
                    $closest = $allowed;
                }
            }
            return $closest;
        }

        throw new \InvalidArgumentException("Invalid perPage: {$value}");
    }

    private static function validateActiveTab(string $value, bool $safe = false): string
    {
        if (self::isValidActiveTab($value)) {
            return $value;
        }

        if ($safe) {
            return 'push';
        }

        throw new \InvalidArgumentException("Invalid activeTab: {$value}");
    }

    private static function isValidActiveTab(string $value): bool
    {
        $validTabs = ['push', 'inpage', 'facebook', 'tiktok'];
        return in_array($value, $validTabs);
    }

    /**
     * Очистить кеш валидных стран
     * Вызывается при изменении данных в IsoEntity
     */
    public static function clearCountriesCache(): void
    {
        Cache::forget('creatives_filters.valid_countries');
    }

    /**
     * Создать кеш-ключ на основе фильтров
     */
    public function getCacheKey(): string
    {
        return md5(json_encode($this->toArray()));
    }

    /**
     * Проверить нужна ли пагинация
     */
    public function needsPagination(int $totalCount): bool
    {
        return $totalCount > $this->perPage;
    }

    /**
     * Получить смещение для пагинации
     */
    public function getOffset(): int
    {
        return ($this->page - 1) * $this->perPage;
    }

    /**
     * Получить номер первого элемента на странице
     */
    public function getFromNumber(int $totalCount): int
    {
        if ($totalCount === 0) return 0;
        return $this->getOffset() + 1;
    }

    /**
     * Получить номер последнего элемента на странице
     */
    public function getToNumber(int $totalCount): int
    {
        if ($totalCount === 0) return 0;
        return min($this->page * $this->perPage, $totalCount);
    }

    /**
     * Получить общее количество страниц
     */
    public function getLastPage(int $totalCount): int
    {
        if ($totalCount === 0) return 1;
        return (int)ceil($totalCount / $this->perPage);
    }

    /**
     * Имплементация Arrayable
     */
    public function toArray(): array
    {
        return [
            'searchKeyword' => $this->searchKeyword,
            'country' => $this->country,
            'dateCreation' => $this->dateCreation,
            'sortBy' => $this->sortBy,
            'periodDisplay' => $this->periodDisplay,
            'onlyAdult' => $this->onlyAdult,
            'isDetailedVisible' => $this->isDetailedVisible,
            'page' => $this->page,
            'perPage' => $this->perPage,
            'activeTab' => $this->activeTab,
            'advertisingNetworks' => $this->advertisingNetworks,
            'languages' => $this->languages,
            'operatingSystems' => $this->operatingSystems,
            'browsers' => $this->browsers,
            'devices' => $this->devices,
            'imageSizes' => $this->imageSizes,
            'savedSettings' => $this->savedSettings,
        ];
    }

    /**
     * Имплементация Jsonable
     */
    public function toJson($options = 0): string
    {
        return json_encode($this->toArray(), $options);
    }

    /**
     * Получить компактную версию для API
     */
    public function toCompactArray(): array
    {
        $array = $this->toArray();

        // Убираем пустые массивы для экономии трафика
        foreach ($array as $key => $value) {
            if (is_array($value) && empty($value)) {
                unset($array[$key]);
            }
        }

        return $array;
    }
}

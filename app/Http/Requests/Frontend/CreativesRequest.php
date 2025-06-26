<?php

namespace App\Http\Requests\Frontend;

use App\Http\Requests\BaseRequest;
use App\Models\AdvertismentNetwork;
use App\Models\Browser;
use App\Enums\Frontend\DeviceType;
use App\Enums\Frontend\OperationSystem;
use App\Helpers\IsoCodesHelper;
use App\Models\Frontend\IsoEntity;
use Illuminate\Support\Facades\Cache;
use Illuminate\Validation\Rule;

class CreativesRequest extends BaseRequest
{
    /**
     * Кэш для валидационных данных
     */
    private static $validationCache = [];

    /**
     * TTL кэша валидации в секундах (5 минут)
     */
    private const VALIDATION_CACHE_TTL = 300;

    /**
     * Максимальные значения для массивов
     */
    private const MAX_ARRAY_ITEMS = [
        'advertisingNetworks' => 50,
        'languages' => 100,
        'operatingSystems' => 20,
        'browsers' => 50,
        'devices' => 10,
        'imageSizes' => 1,
    ];

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            // Основные фильтры
            'searchKeyword' => ['sometimes', 'nullable', 'string', 'max:255'],
            'country' => ['sometimes', 'nullable', 'string', $this->getCountryValidationRule()],
            'dateCreation' => ['sometimes', 'nullable', 'string', function ($attribute, $value, $fail) {
                $this->validateDateRange($attribute, $value, $fail);
            }],
            'sortBy' => ['sometimes', 'nullable', 'string', Rule::in($this->getValidSortOptions())],
            'periodDisplay' => ['sometimes', 'nullable', 'string', function ($attribute, $value, $fail) {
                $this->validateDateRange($attribute, $value, $fail);
            }],
            'onlyAdult' => ['sometimes', 'nullable', 'boolean'],

            // Массивы фильтров с оптимизированной валидацией
            'advertisingNetworks' => ['sometimes', 'nullable', 'array', 'max:' . self::MAX_ARRAY_ITEMS['advertisingNetworks'], Rule::in(AdvertismentNetwork::forCreativeFilters()->pluck('value')->toArray())],
            'advertisingNetworks.*' => ['string', 'max:50', $this->getAdvertisingNetworkValidationRule()],

            'languages' => ['sometimes', 'nullable', 'array', 'max:' . self::MAX_ARRAY_ITEMS['languages']],
            'languages.*' => ['string', 'max:3', $this->getLanguageValidationRule()],

            'operatingSystems' => ['sometimes', 'nullable', 'array', 'max:' . self::MAX_ARRAY_ITEMS['operatingSystems']],
            'operatingSystems.*' => ['string', 'max:50', $this->getOperatingSystemValidationRule()],

            'browsers' => ['sometimes', 'nullable', 'array', 'max:' . self::MAX_ARRAY_ITEMS['browsers']],
            'browsers.*' => ['string', 'max:100', $this->getBrowserValidationRule()],

            'devices' => ['sometimes', 'nullable', 'array', 'max:' . self::MAX_ARRAY_ITEMS['devices']],
            'devices.*' => ['string', 'max:50', $this->getDeviceValidationRule()],

            // 'imageSizes' => ['sometimes', 'nullable', 'array', 'max:' . self::MAX_ARRAY_ITEMS['imageSizes']],
            // 'imageSizes.*' => ['string', 'max:20', Rule::in($this->getValidImageSizes())],

            // Пагинация
            'page' => ['sometimes', 'nullable', 'integer', 'min:1', 'max:10000'],
            'perPage' => ['sometimes', 'nullable', 'integer', 'min:6', 'max:100'],
            'activeTab' => ['sometimes', 'nullable', 'string', Rule::in(['push', 'inpage', 'facebook', 'tiktok'])],

            // URL sync параметры (с кэшированием валидации)
            ...$this->getUrlSyncRules(),
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        $input = $this->all();
        $sanitized = [];

        // Используем batch санитизацию для улучшения производительности
        $stringFields = [
            'searchKeyword',
            'country',
            'dateCreation',
            'sortBy',
            'periodDisplay',
            'activeTab',
            'cr_searchKeyword',
            'cr_country',
            'cr_dateCreation',
            'cr_sortBy',
            'cr_periodDisplay',
            'cr_activeTab'
        ];

        foreach ($stringFields as $field) {
            if (isset($input[$field])) {
                $sanitized[$field] = $this->sanitizeInput($input[$field]);
            }
        }

        // Санитизация булевых значений (только для обычных параметров, не URL)
        if (isset($input['onlyAdult'])) {
            $sanitized['onlyAdult'] = $this->sanitizeBooleanInput($input['onlyAdult']);
        }

        // URL булевые параметры обрабатываем как строки для валидации
        if (isset($input['cr_onlyAdult'])) {
            $sanitized['cr_onlyAdult'] = $this->sanitizeInput($input['cr_onlyAdult']);
        }

        // Batch санитизация массивов
        $arrayFields = ['advertisingNetworks', 'languages', 'operatingSystems', 'browsers', 'devices', 'imageSizes'];
        foreach ($arrayFields as $field) {
            if (isset($input[$field]) && is_array($input[$field])) {
                $sanitized[$field] = $this->sanitizeArrayField($input[$field]);
            }
        }

        // Санитизация URL массивов (comma-separated)
        $urlArrayFields = [
            'cr_advertisingNetworks',
            'cr_languages',
            'cr_operatingSystems',
            'cr_browsers',
            'cr_devices',
            'cr_imageSizes'
        ];
        foreach ($urlArrayFields as $field) {
            if (isset($input[$field]) && is_string($input[$field])) {
                $sanitized[$field] = $this->sanitizeInput($input[$field]);
            }
        }

        // Санитизация числовых полей
        foreach (['page', 'perPage'] as $field) {
            if (isset($input[$field])) {
                $sanitized[$field] = $this->sanitizeNumericInput($input[$field]);
            }
        }

        $this->merge($sanitized);
    }

    /**
     * Get validated and filtered data for creatives API.
     */
    public function getCreativesFilters(): array
    {
        // Используем кэширование для повторных запросов с одинаковыми параметрами
        $cacheKey = 'creatives_filters_' . md5(serialize($this->all()));

        return Cache::remember($cacheKey, 60, function () {
            return $this->processValidatedFilters();
        });
    }

    /**
     * Обрабатывает валидированные фильтры
     */
    private function processValidatedFilters(): array
    {
        $validated = $this->validated();
        $filters = [];

        // Маппинг URL параметров в обычные фильтры (приоритет URL параметрам)
        $urlToFilterMapping = [
            'cr_searchKeyword' => 'searchKeyword',
            'cr_country' => 'country',
            'cr_dateCreation' => 'dateCreation',
            'cr_sortBy' => 'sortBy',
            'cr_periodDisplay' => 'periodDisplay',
            'cr_onlyAdult' => 'onlyAdult',
            'cr_activeTab' => 'activeTab',
        ];

        // Обработка простых полей
        foreach ($urlToFilterMapping as $urlParam => $filterParam) {
            $filters[$filterParam] = $validated[$urlParam] ?? $validated[$filterParam] ?? null;
        }

        // Обработка массивов с оптимизацией
        $urlArrayFields = [
            'cr_advertisingNetworks' => 'advertisingNetworks',
            'cr_languages' => 'languages',
            'cr_operatingSystems' => 'operatingSystems',
            'cr_browsers' => 'browsers',
            'cr_devices' => 'devices',
            'cr_imageSizes' => 'imageSizes',
        ];

        foreach ($urlArrayFields as $urlParam => $filterParam) {
            if (isset($validated[$urlParam])) {
                $filters[$filterParam] = $this->parseCommaSeparatedString($validated[$urlParam]);
            } elseif (isset($validated[$filterParam]) && is_array($validated[$filterParam])) {
                $filters[$filterParam] = $validated[$filterParam];
            } else {
                $filters[$filterParam] = [];
            }
        }

        // Пагинация с валидацией
        $filters['page'] = max(1, min(10000, (int)($validated['page'] ?? 1)));
        $filters['perPage'] = max(6, min(100, (int)($validated['perPage'] ?? 12)));

        // Batch валидация всех значений
        return $this->batchValidateFilters($filters);
    }

    /**
     * Batch валидация фильтров для улучшения производительности
     */
    private function batchValidateFilters(array $filters): array
    {
        $validatedFilters = [];

        // Простые поля
        if (!empty($filters['searchKeyword'])) {
            $validatedFilters['searchKeyword'] = trim($filters['searchKeyword']);
        }

        // Валидация страны с кэшированием
        if (isset($filters['country']) && $filters['country'] !== 'default') {
            if ($this->isValidCountryCodeCached($filters['country'])) {
                $validatedFilters['country'] = strtoupper($filters['country']);
            }
        }

        // Валидация enum значений (кроме дат, которые обрабатываются отдельно)
        $enumFields = [
            'sortBy' => ['creation', 'activity', 'popularity', 'byCreationDate', 'byActivity', 'byPopularity'],
            'activeTab' => ['push', 'inpage', 'facebook', 'tiktok'],
        ];

        foreach ($enumFields as $field => $validValues) {
            if (isset($filters[$field]) && $filters[$field] !== 'default' && in_array($filters[$field], $validValues)) {
                $validatedFilters[$field] = $filters[$field];
            }
        }

        // Отдельная валидация дат с поддержкой только диапазонов
        foreach (['dateCreation', 'periodDisplay'] as $dateField) {
            if (isset($filters[$dateField]) && $filters[$dateField] !== 'default') {
                $dateValue = $filters[$dateField];

                // Проверяем предустановленные значения
                if (in_array($dateValue, $this->getValidDateRanges())) {
                    $validatedFilters[$dateField] = $dateValue;
                }
                // Проверяем только custom диапазоны (не одиночные даты)
                elseif (preg_match('/^custom_\d{4}-\d{2}-\d{2}_to_\d{4}-\d{2}-\d{2}$/', $dateValue)) {
                    // Дополнительная валидация уже выполнена в validateDateRange
                    $validatedFilters[$dateField] = $dateValue;
                }
            }
        }

        // Булевое значение
        if (isset($filters['onlyAdult'])) {
            $validatedFilters['onlyAdult'] = $this->sanitizeBooleanInput($filters['onlyAdult']);
        }

        // Batch валидация массивов
        $validatedFilters = array_merge($validatedFilters, $this->batchValidateArrayFilters($filters));

        // Пагинация
        $validatedFilters['page'] = $filters['page'];
        $validatedFilters['perPage'] = $filters['perPage'];

        return $validatedFilters;
    }

    /**
     * Batch валидация массивов фильтров
     */
    private function batchValidateArrayFilters(array $filters): array
    {
        $validatedArrays = [];

        // Получаем все валидные значения одним запросом
        $validValues = $this->getAllValidValues();

        $arrayFieldsMapping = [
            'advertisingNetworks' => $validValues['advertisingNetworks'],
            'languages' => $validValues['languages'],
            'operatingSystems' => $validValues['operatingSystems'],
            'browsers' => $validValues['browsers'],
            'devices' => $validValues['devices'],
            'imageSizes' => $validValues['imageSizes'],
        ];

        foreach ($arrayFieldsMapping as $field => $validOptions) {
            if (isset($filters[$field]) && is_array($filters[$field]) && !empty($filters[$field])) {
                $validatedArrays[$field] = array_values(array_intersect($filters[$field], $validOptions));
            }
        }

        return array_filter($validatedArrays, function ($value) {
            return !empty($value);
        });
    }

    /**
     * Получает все валидные значения одним запросом (с кэшированием)
     */
    private function getAllValidValues(): array
    {
        return Cache::remember('all_valid_filter_values', self::VALIDATION_CACHE_TTL, function () {
            return [
                'advertisingNetworks' => AdvertismentNetwork::forCreativeFilters()->pluck('value')->toArray(),
                'languages' => IsoEntity::languages()->active()->pluck('iso_code_2')->toArray(),
                'operatingSystems' => array_column(OperationSystem::getForSelect(), 'value'),
                'browsers' => array_column(Browser::getBrowsersForSelect(), 'value'),
                'devices' => array_column(DeviceType::getForSelect(), 'value'),
                'imageSizes' => ['1x1', '16x9', '9x16', '3x2', '2x3', '4x3', '3x4', '21x9'],
            ];
        });
    }

    /**
     * Генерирует правила валидации для URL sync параметров
     */
    private function getUrlSyncRules(): array
    {
        $baseRules = [
            'cr_searchKeyword' => ['sometimes', 'nullable', 'string', 'max:255'],
            'cr_country' => ['sometimes', 'nullable', 'string', $this->getCountryValidationRule()],
            'cr_dateCreation' => ['sometimes', 'nullable', 'string', function ($attribute, $value, $fail) {
                $this->validateDateRange($attribute, $value, $fail);
            }],
            'cr_sortBy' => ['sometimes', 'nullable', 'string', Rule::in($this->getValidSortOptions())],
            'cr_periodDisplay' => ['sometimes', 'nullable', 'string', function ($attribute, $value, $fail) {
                $this->validateDateRange($attribute, $value, $fail);
            }],
            'cr_onlyAdult' => ['sometimes', 'nullable', 'string', Rule::in(['0', '1', 'true', 'false'])],
            'cr_activeTab' => ['sometimes', 'nullable', 'string', Rule::in(['push', 'inpage', 'facebook', 'tiktok'])],
        ];

        // Добавляем правила для URL массивов с валидацией содержимого
        $baseRules['cr_advertisingNetworks'] = ['sometimes', 'nullable', 'string', 'max:500', $this->getCommaSeparatedAdvertisingNetworksValidationRule()];
        $baseRules['cr_languages'] = ['sometimes', 'nullable', 'string', 'max:500', $this->getCommaSeparatedLanguagesValidationRule()];
        $baseRules['cr_operatingSystems'] = ['sometimes', 'nullable', 'string', 'max:500', $this->getCommaSeparatedOperatingSystemsValidationRule()];
        $baseRules['cr_browsers'] = ['sometimes', 'nullable', 'string', 'max:1000', $this->getCommaSeparatedBrowsersValidationRule()];
        $baseRules['cr_devices'] = ['sometimes', 'nullable', 'string', 'max:500', $this->getCommaSeparatedDevicesValidationRule()];
        $baseRules['cr_imageSizes'] = ['sometimes', 'nullable', 'string', 'max:500', $this->getCommaSeparatedImageSizesValidationRule()];

        return $baseRules;
    }

    /**
     * Кэшированные методы валидации
     */
    private function isValidCountryCodeCached(string $code): bool
    {
        $cacheKey = "valid_country_{$code}";

        return Cache::remember($cacheKey, self::VALIDATION_CACHE_TTL, function () use ($code) {
            return in_array($code, ['default', 'all']) || IsoEntity::isValidCountryCode($code);
        });
    }

    private function getCountryValidationRule()
    {
        return function ($attribute, $value, $fail) {
            if (!$this->isValidCountryCodeCached($value)) {
                $fail('Указанный код страны не доступен.');
            }
        };
    }

    private function getLanguageValidationRule()
    {
        return function ($attribute, $value, $fail) {
            $validLanguages = Cache::remember('valid_languages', self::VALIDATION_CACHE_TTL, function () {
                return IsoEntity::languages()->active()->pluck('iso_code_2')->toArray();
            });

            if (!in_array($value, $validLanguages)) {
                $fail('Указанный код языка не доступен.');
            }
        };
    }

    private function getAdvertisingNetworkValidationRule()
    {
        return function ($attribute, $value, $fail) {
            $validNetworks = Cache::remember('valid_advertising_networks', self::VALIDATION_CACHE_TTL, function () {
                return AdvertismentNetwork::forCreativeFilters()->pluck('value')->toArray();
            });

            if (!in_array($value, $validNetworks)) {
                $fail('Указанная рекламная сеть не доступна.');
            }
        };
    }

    private function getOperatingSystemValidationRule()
    {
        return function ($attribute, $value, $fail) {
            $validOS = Cache::remember('valid_operating_systems', self::VALIDATION_CACHE_TTL, function () {
                return array_column(OperationSystem::getForSelect(), 'value');
            });

            if (!in_array($value, $validOS)) {
                $fail('Указанная операционная система не доступна.');
            }
        };
    }

    private function getBrowserValidationRule()
    {
        return function ($attribute, $value, $fail) {
            $validBrowsers = Cache::remember('valid_browsers', self::VALIDATION_CACHE_TTL, function () {
                return array_column(Browser::getBrowsersForSelect(), 'value');
            });

            if (!in_array($value, $validBrowsers)) {
                $fail('Указанный браузер не доступен.');
            }
        };
    }

    private function getDeviceValidationRule()
    {
        return function ($attribute, $value, $fail) {
            $validDevices = Cache::remember('valid_devices', self::VALIDATION_CACHE_TTL, function () {
                return array_column(DeviceType::getForSelect(), 'value');
            });

            if (!in_array($value, $validDevices)) {
                $fail('Указанное устройство не доступно.');
            }
        };
    }

    /**
     * Правила валидации для comma-separated URL параметров
     */
    private function getCommaSeparatedAdvertisingNetworksValidationRule(): callable
    {
        return function ($attribute, $value, $fail) {
            if (empty($value)) return;

            $validNetworks = Cache::remember('valid_advertising_networks', self::VALIDATION_CACHE_TTL, function () {
                return AdvertismentNetwork::forCreativeFilters()->pluck('value')->toArray();
            });

            $items = $this->parseCommaSeparatedString($value);
            foreach ($items as $item) {
                if (!in_array($item, $validNetworks)) {
                    $fail("Указанная рекламная сеть не доступна: {$item}");
                    return;
                }
            }
        };
    }

    private function getCommaSeparatedLanguagesValidationRule(): callable
    {
        return function ($attribute, $value, $fail) {
            if (empty($value)) return;

            $validLanguages = Cache::remember('valid_languages', self::VALIDATION_CACHE_TTL, function () {
                return IsoEntity::languages()->active()->pluck('iso_code_2')->toArray();
            });

            $items = $this->parseCommaSeparatedString($value);
            foreach ($items as $item) {
                if (!in_array($item, $validLanguages)) {
                    $fail("Указанный код языка не доступен: {$item}");
                    return;
                }
            }
        };
    }

    private function getCommaSeparatedOperatingSystemsValidationRule(): callable
    {
        return function ($attribute, $value, $fail) {
            if (empty($value)) return;

            $validOS = Cache::remember('valid_operating_systems', self::VALIDATION_CACHE_TTL, function () {
                return array_column(OperationSystem::getForSelect(), 'value');
            });

            $items = $this->parseCommaSeparatedString($value);
            foreach ($items as $item) {
                if (!in_array($item, $validOS)) {
                    $fail("Указанная операционная система не доступна: {$item}");
                    return;
                }
            }
        };
    }

    private function getCommaSeparatedBrowsersValidationRule(): callable
    {
        return function ($attribute, $value, $fail) {
            if (empty($value)) return;

            $validBrowsers = Cache::remember('valid_browsers', self::VALIDATION_CACHE_TTL, function () {
                return array_column(Browser::getBrowsersForSelect(), 'value');
            });

            $items = $this->parseCommaSeparatedString($value);
            foreach ($items as $item) {
                if (!in_array($item, $validBrowsers)) {
                    $fail("Указанный браузер не доступен: {$item}");
                    return;
                }
            }
        };
    }

    private function getCommaSeparatedDevicesValidationRule(): callable
    {
        return function ($attribute, $value, $fail) {
            if (empty($value)) return;

            $validDevices = Cache::remember('valid_devices', self::VALIDATION_CACHE_TTL, function () {
                return array_column(DeviceType::getForSelect(), 'value');
            });

            $items = $this->parseCommaSeparatedString($value);
            foreach ($items as $item) {
                if (!in_array($item, $validDevices)) {
                    $fail("Указанное устройство не доступно: {$item}");
                    return;
                }
            }
        };
    }

    private function getCommaSeparatedImageSizesValidationRule(): callable
    {
        return function ($attribute, $value, $fail) {
            if (empty($value)) return;

            $validSizes = $this->getValidImageSizes();
            $items = $this->parseCommaSeparatedString($value);
            foreach ($items as $item) {
                if (!in_array($item, $validSizes)) {
                    $fail("Недопустимый размер изображения: {$item}");
                    return;
                }
            }
        };
    }

    /**
     * Получает валидные значения с кэшированием
     */
    private function getValidDateRanges(): array
    {
        return ['today', 'yesterday', 'last7', 'last30', 'last90', 'thisMonth', 'lastMonth', 'thisYear', 'lastYear', 'default'];
    }

    /**
     * Валидирует диапазон дат (только диапазоны, одиночные даты не поддерживаются)
     */
    private function validateDateRange($attribute, $value, $fail): void
    {
        // Проверяем предустановленные диапазоны
        if (in_array($value, $this->getValidDateRanges())) {
            return;
        }

        // Проверяем custom диапазоны в формате: custom_YYYY-MM-DD_to_YYYY-MM-DD
        if (preg_match('/^custom_(\d{4}-\d{2}-\d{2})_to_(\d{4}-\d{2}-\d{2})$/', $value, $matches)) {
            $startDate = $matches[1];
            $endDate = $matches[2];

            // Валидируем формат дат
            if (!$this->isValidDate($startDate) || !$this->isValidDate($endDate)) {
                $fail('Неверный формат даты в диапазоне.');
                return;
            }

            // Проверяем что начальная дата не больше конечной
            if (strtotime($startDate) > strtotime($endDate)) {
                $fail('Начальная дата не может быть больше конечной.');
                return;
            }

            // Проверяем разумные ограничения (не более 2 лет назад)
            $twoYearsAgo = date('Y-m-d', strtotime('-2 years'));
            if ($startDate < $twoYearsAgo) {
                $fail('Дата не может быть более чем 2 года назад.');
                return;
            }

            // Проверяем максимальный диапазон (например, не более 1 года)
            $maxDays = 365;
            $daysDiff = (strtotime($endDate) - strtotime($startDate)) / (60 * 60 * 24);
            if ($daysDiff > $maxDays) {
                $fail('Диапазон дат не может превышать 1 год.');
                return;
            }

            return;
        }

        $fail('Недопустимый формат диапазона дат. Поддерживаются только предустановленные диапазоны или custom диапазоны в формате custom_YYYY-MM-DD_to_YYYY-MM-DD');
    }

    /**
     * Проверяет валидность даты
     */
    private function isValidDate(string $date): bool
    {
        $d = \DateTime::createFromFormat('Y-m-d', $date);
        return $d && $d->format('Y-m-d') === $date;
    }

    private function getValidSortOptions(): array
    {
        return ['creation', 'activity', 'popularity', 'byCreationDate', 'byActivity', 'byPopularity', 'default'];
    }

    private function getValidImageSizes(): array
    {
        return ['1x1', '16x9', '9x16', '3x2', '2x3', '4x3', '3x4', '21x9'];
    }

    /**
     * Оптимизированная санитизация массива
     */
    private function sanitizeArrayField(array $array): array
    {
        return array_values(array_filter(
            array_map([$this, 'sanitizeInput'], $array),
            function ($item) {
                return !empty($item);
            }
        ));
    }

    /**
     * Парсит comma-separated строку с валидацией
     */
    protected function parseCommaSeparatedString(string $value): array
    {
        if (empty($value)) {
            return [];
        }

        $items = explode(',', $value);
        $items = array_map('trim', $items);
        $items = array_filter($items, function ($item) {
            return !empty($item) && strlen($item) <= 100; // Дополнительная защита
        });

        return array_values($items);
    }

    /**
     * Оптимизированная санитизация числового ввода
     */
    protected function sanitizeNumericInput($input): int
    {
        return (int) filter_var($input, FILTER_SANITIZE_NUMBER_INT);
    }

    /**
     * Оптимизированная санитизация булевого ввода
     */
    protected function sanitizeBooleanInput($input): bool
    {
        if (is_bool($input)) {
            return $input;
        }

        if (is_string($input)) {
            $input = strtolower(trim($input));
            return in_array($input, ['1', 'true', 'yes', 'on'], true);
        }

        return (bool) $input;
    }

    /**
     * Улучшенные сообщения об ошибках
     */
    public function messages(): array
    {
        return [
            'searchKeyword.string' => 'Поисковое слово должно быть строкой',
            'searchKeyword.max' => 'Поисковое слово не должно превышать 255 символов',
            'country.string' => 'Код страны должен быть строкой',
            'sortBy.in' => 'Недопустимое значение сортировки',
            'activeTab.in' => 'Недопустимое значение вкладки',
            'onlyAdult.boolean' => 'Фильтр для взрослых должен быть булевым значением',
            'dateCreation.string' => 'Дата создания должна быть строкой',
            'periodDisplay.string' => 'Период отображения должен быть строкой',
            'cr_dateCreation.string' => 'Дата создания должна быть строкой',
            'cr_periodDisplay.string' => 'Период отображения должен быть строкой',
            '*.array' => 'Поле должно быть массивом',
            '*.max' => 'Превышено максимальное количество элементов',
            'page.integer' => 'Номер страницы должен быть числом',
            'page.min' => 'Номер страницы не может быть меньше 1',
            'page.max' => 'Номер страницы не может быть больше 10000',
            'perPage.integer' => 'Количество элементов на странице должно быть числом',
            'perPage.min' => 'Минимум 6 элементов на странице',
            'perPage.max' => 'Максимум 100 элементов на странице',
        ];
    }

    /**
     * Получает статистику валидации для мониторинга
     */
    public function getValidationStats(): array
    {
        return [
            'cache_hits' => Cache::get('validation_cache_hits', 0),
            'cache_misses' => Cache::get('validation_cache_misses', 0),
            'validation_time' => microtime(true) - (request()->server('REQUEST_TIME_FLOAT') ?? microtime(true)),
        ];
    }
}

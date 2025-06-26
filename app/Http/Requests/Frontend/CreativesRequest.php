<?php

namespace App\Http\Requests\Frontend;

use App\Http\Requests\BaseRequest;
use App\Models\AdvertismentNetwork;
use App\Models\Browser;
use App\Enums\Frontend\DeviceType;
use App\Enums\Frontend\OperationSystem;
use App\Helpers\IsoCodesHelper;
use App\Models\Frontend\IsoEntity;
use Illuminate\Validation\Rule;

class CreativesRequest extends BaseRequest
{
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
            // Поиск и основные фильтры
            'searchKeyword' => ['nullable', 'string', 'max:255'],
            'country' => [
                'nullable',
                'string',
                'max:10',
                function ($attribute, $value, $fail) {
                    if (!empty($value) && !IsoEntity::isValidCountryCode($value)) {
                        $fail('Указанный код страны не доступен.');
                    }
                }
            ],
            'dateCreation' => ['nullable', 'string', 'max:50'],
            'sortBy' => ['nullable', 'string', 'in:creation,activity,popularity,byCreationDate,byActivity,byPopularity,default'],
            'periodDisplay' => ['nullable', 'string', 'max:50'],
            'onlyAdult' => ['nullable', 'boolean'],

            // Массивы фильтров
            'advertisingNetworks' => ['nullable', 'array', 'max:50'],
            'advertisingNetworks.*' => ['string', 'max:50'],
            'languages' => ['nullable', 'array', 'max:100'],
            'languages.*' => [
                'string',
                'size:2',
                function ($attribute, $value, $fail) {
                    if (!empty($value) && !IsoEntity::isValidLanguageCode($value)) {
                        $fail('Указанный код языка не доступен.');
                    }
                }
            ],
            'operatingSystems' => ['nullable', 'array', 'max:20'],
            'operatingSystems.*' => ['string', 'max:50'],
            'browsers' => ['nullable', 'array', 'max:50'],
            'browsers.*' => ['string', 'max:100'],
            'devices' => ['nullable', 'array', 'max:10'],
            'devices.*' => ['string', 'max:50'],
            'imageSizes' => ['nullable', 'array', 'max:20'],
            'imageSizes.*' => ['string', 'max:20'],

            // Пагинация
            'page' => ['nullable', 'integer', 'min:1', 'max:10000'],
            'perPage' => ['nullable', 'integer', 'min:6', 'max:100'],

            // Активная вкладка
            'activeTab' => ['nullable', 'string', 'in:push,inpage,facebook,tiktok'],

            // URL sync параметры с префиксом cr_
            'cr_searchKeyword' => ['nullable', 'string', 'max:255'],
            'cr_country' => [
                'nullable',
                'string',
                'max:10',
                function ($attribute, $value, $fail) {
                    if (!empty($value) && !IsoEntity::isValidCountryCode($value)) {
                        $fail('Указанный код страны не доступен.');
                    }
                }
            ],
            'cr_dateCreation' => ['nullable', 'string', 'max:50'],
            'cr_sortBy' => ['nullable', 'string', 'in:creation,activity,popularity,byCreationDate,byActivity,byPopularity,default'],
            'cr_periodDisplay' => ['nullable', 'string', 'max:50'],
            'cr_onlyAdult' => ['nullable', 'string', 'in:0,1,true,false'],
            'cr_advertisingNetworks' => ['nullable', 'string', 'max:1000'],
            'cr_languages' => [
                'nullable',
                'string',
                'max:500',
                function ($attribute, $value, $fail) {
                    if (!empty($value)) {
                        // Парсим comma-separated string и валидируем каждый код языка
                        $languageCodes = array_map('trim', explode(',', $value));
                        foreach ($languageCodes as $code) {
                            if (!empty($code) && !IsoEntity::isValidLanguageCode($code)) {
                                $fail("Код языка '{$code}' не доступен.");
                                break;
                            }
                        }
                    }
                }
            ],
            'cr_operatingSystems' => ['nullable', 'string', 'max:500'],
            'cr_browsers' => ['nullable', 'string', 'max:1000'],
            'cr_devices' => ['nullable', 'string', 'max:200'],
            'cr_imageSizes' => ['nullable', 'string', 'max:300'],
            'cr_activeTab' => ['nullable', 'string', 'in:push,inpage,facebook,tiktok'],
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        $input = $this->all();

        $sanitized = [];

        // Санитизация строковых полей
        foreach (['searchKeyword', 'country', 'dateCreation', 'sortBy', 'periodDisplay', 'activeTab'] as $field) {
            if (isset($input[$field])) {
                $sanitized[$field] = $this->sanitizeInput($input[$field]);
            }
        }

        // Санитизация URL sync параметров
        foreach (['cr_searchKeyword', 'cr_country', 'cr_dateCreation', 'cr_sortBy', 'cr_periodDisplay', 'cr_activeTab'] as $field) {
            if (isset($input[$field])) {
                $sanitized[$field] = $this->sanitizeInput($input[$field]);
            }
        }

        // Обработка булевых значений
        if (isset($input['onlyAdult'])) {
            $sanitized['onlyAdult'] = $this->sanitizeBooleanInput($input['onlyAdult']);
        }

        if (isset($input['cr_onlyAdult'])) {
            $sanitized['cr_onlyAdult'] = $this->sanitizeInput($input['cr_onlyAdult']);
        }

        // Санитизация массивов из URL (comma-separated strings)
        foreach (['cr_advertisingNetworks', 'cr_languages', 'cr_operatingSystems', 'cr_browsers', 'cr_devices', 'cr_imageSizes'] as $field) {
            if (isset($input[$field]) && is_string($input[$field])) {
                $sanitized[$field] = $this->sanitizeInput($input[$field]);
            }
        }

        // Санитизация массивов
        foreach (['advertisingNetworks', 'languages', 'operatingSystems', 'browsers', 'devices', 'imageSizes'] as $field) {
            if (isset($input[$field]) && is_array($input[$field])) {
                $sanitized[$field] = array_map([$this, 'sanitizeInput'], $input[$field]);
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
        $validated = $this->validated();
        $filters = [];

        // Преобразование URL параметров в обычные фильтры (приоритет URL параметрам)
        $urlToFilter = [
            'cr_searchKeyword' => 'searchKeyword',
            'cr_country' => 'country',
            'cr_dateCreation' => 'dateCreation',
            'cr_sortBy' => 'sortBy',
            'cr_periodDisplay' => 'periodDisplay',
            'cr_onlyAdult' => 'onlyAdult',
            'cr_activeTab' => 'activeTab',
        ];

        foreach ($urlToFilter as $urlParam => $filterParam) {
            if (isset($validated[$urlParam]) && $validated[$urlParam] !== null) {
                $filters[$filterParam] = $validated[$urlParam];
            } elseif (isset($validated[$filterParam]) && $validated[$filterParam] !== null) {
                $filters[$filterParam] = $validated[$filterParam];
            }
        }

        // Обработка массивов из URL параметров
        $urlArrayFields = [
            'cr_advertisingNetworks' => 'advertisingNetworks',
            'cr_languages' => 'languages',
            'cr_operatingSystems' => 'operatingSystems',
            'cr_browsers' => 'browsers',
            'cr_devices' => 'devices',
            'cr_imageSizes' => 'imageSizes',
        ];

        foreach ($urlArrayFields as $urlParam => $filterParam) {
            if (isset($validated[$urlParam]) && $validated[$urlParam] !== null) {
                // Преобразуем comma-separated string в массив
                $arrayValue = $this->parseCommaSeparatedString($validated[$urlParam]);
                if (!empty($arrayValue)) {
                    $filters[$filterParam] = $arrayValue;
                }
            } elseif (isset($validated[$filterParam]) && is_array($validated[$filterParam]) && !empty($validated[$filterParam])) {
                $filters[$filterParam] = $validated[$filterParam];
            }
        }

        // Пагинация
        $filters['page'] = $validated['page'] ?? 1;
        $filters['perPage'] = $validated['perPage'] ?? 12;

        // Валидация и фильтрация значений
        return $this->validateAndFilterValues($filters);
    }

    /**
     * Валидирует и фильтрует значения согласно доступным опциям.
     */
    protected function validateAndFilterValues(array $filters): array
    {
        $validatedFilters = [];

        // Валидация простых полей
        if (isset($filters['searchKeyword']) && !empty(trim($filters['searchKeyword']))) {
            $validatedFilters['searchKeyword'] = trim($filters['searchKeyword']);
        }

        // Валидация страны
        if (isset($filters['country']) && $filters['country'] !== 'default') {
            // Используем новую валидацию через IsoEntity
            if (IsoEntity::isValidCountryCode($filters['country'])) {
                $validatedFilters['country'] = strtoupper($filters['country']);
            }
        }

        // Валидация сортировки
        if (isset($filters['sortBy']) && $filters['sortBy'] !== 'default') {
            $validSortOptions = ['creation', 'activity', 'popularity', 'byCreationDate', 'byActivity', 'byPopularity'];
            if (in_array($filters['sortBy'], $validSortOptions)) {
                $validatedFilters['sortBy'] = $filters['sortBy'];
            }
        }

        // Валидация даты создания
        if (isset($filters['dateCreation']) && $filters['dateCreation'] !== 'default') {
            $validDateOptions = ['today', 'yesterday', 'last7', 'last30', 'last90', 'thisMonth', 'lastMonth', 'thisYear', 'lastYear'];
            if (in_array($filters['dateCreation'], $validDateOptions)) {
                $validatedFilters['dateCreation'] = $filters['dateCreation'];
            }
        }

        // Валидация периода отображения
        if (isset($filters['periodDisplay']) && $filters['periodDisplay'] !== 'default') {
            $validPeriodOptions = ['today', 'yesterday', 'last7', 'last30', 'last90', 'thisMonth', 'lastMonth', 'thisYear', 'lastYear'];
            if (in_array($filters['periodDisplay'], $validPeriodOptions)) {
                $validatedFilters['periodDisplay'] = $filters['periodDisplay'];
            }
        }

        // Валидация булевого значения onlyAdult
        if (isset($filters['onlyAdult'])) {
            $validatedFilters['onlyAdult'] = $this->sanitizeBooleanInput($filters['onlyAdult']);
        }

        // Валидация массивов
        $validatedFilters = array_merge($validatedFilters, $this->validateArrayFilters($filters));

        // Валидация активной вкладки
        if (isset($filters['activeTab'])) {
            $validTabs = ['push', 'inpage', 'facebook', 'tiktok'];
            if (in_array($filters['activeTab'], $validTabs)) {
                $validatedFilters['activeTab'] = $filters['activeTab'];
            }
        }

        // Пагинация
        $validatedFilters['page'] = max(1, min(10000, (int)($filters['page'] ?? 1)));
        $validatedFilters['perPage'] = max(6, min(100, (int)($filters['perPage'] ?? 12)));

        return $validatedFilters;
    }

    /**
     * Валидирует массивы фильтров.
     */
    protected function validateArrayFilters(array $filters): array
    {
        $validatedArrays = [];

        // Валидация рекламных сетей
        if (isset($filters['advertisingNetworks']) && is_array($filters['advertisingNetworks'])) {
            $validNetworks = array_keys(AdvertismentNetwork::forCreativeFilters());
            $validatedArrays['advertisingNetworks'] = array_intersect($filters['advertisingNetworks'], $validNetworks);
        }

        // Валидация языков
        if (isset($filters['languages']) && is_array($filters['languages'])) {
            $validLanguages = [];
            foreach ($filters['languages'] as $languageCode) {
                if (IsoEntity::isValidLanguageCode($languageCode)) {
                    $validLanguages[] = strtolower($languageCode);
                }
            }
            if (!empty($validLanguages)) {
                $validatedArrays['languages'] = $validLanguages;
            }
        }

        // Валидация операционных систем
        if (isset($filters['operatingSystems']) && is_array($filters['operatingSystems'])) {
            $validOS = array_column(OperationSystem::getForSelect(), 'value');
            $validatedArrays['operatingSystems'] = array_intersect($filters['operatingSystems'], $validOS);
        }

        // Валидация браузеров
        if (isset($filters['browsers']) && is_array($filters['browsers'])) {
            $validBrowsers = array_column(Browser::getBrowsersForSelect(), 'value');
            $validatedArrays['browsers'] = array_intersect($filters['browsers'], $validBrowsers);
        }

        // Валидация устройств
        if (isset($filters['devices']) && is_array($filters['devices'])) {
            $validDevices = array_column(DeviceType::getForSelect(), 'value');
            $validatedArrays['devices'] = array_intersect($filters['devices'], $validDevices);
        }

        // Валидация размеров изображений
        if (isset($filters['imageSizes']) && is_array($filters['imageSizes'])) {
            $validSizes = ['1x1', '16x9', '9x16', '3x2', '2x3', '4x3', '3x4', '21x9'];
            $validatedArrays['imageSizes'] = array_intersect($filters['imageSizes'], $validSizes);
        }

        // Удаляем пустые массивы
        return array_filter($validatedArrays, function ($value) {
            return !empty($value);
        });
    }

    /**
     * Парсит comma-separated строку в массив.
     */
    protected function parseCommaSeparatedString(string $value): array
    {
        if (empty($value)) {
            return [];
        }

        $items = explode(',', $value);
        $items = array_map('trim', $items);
        $items = array_filter($items, function ($item) {
            return !empty($item);
        });

        return array_values($items);
    }

    /**
     * Санитизирует числовой ввод.
     */
    protected function sanitizeNumericInput($input): int
    {
        return (int) filter_var($input, FILTER_SANITIZE_NUMBER_INT);
    }

    /**
     * Санитизирует булевый ввод.
     */
    protected function sanitizeBooleanInput($input): bool
    {
        if (is_bool($input)) {
            return $input;
        }

        if (is_string($input)) {
            $input = strtolower(trim($input));
            return in_array($input, ['1', 'true', 'yes', 'on']);
        }

        return (bool) $input;
    }

    /**
     * Get custom validation messages.
     */
    public function messages(): array
    {
        return [
            'searchKeyword.string' => 'Поисковое слово должно быть строкой',
            'searchKeyword.max' => 'Поисковое слово не должно превышать 255 символов',
            'country.string' => 'Код страны должен быть строкой',
            'country.max' => 'Код страны не должен превышать 10 символов',
            'cr_country.string' => 'Код страны должен быть строкой',
            'cr_country.max' => 'Код страны не должен превышать 10 символов',
            'sortBy.in' => 'Недопустимое значение сортировки',
            'activeTab.in' => 'Недопустимое значение вкладки',
            'onlyAdult.boolean' => 'Фильтр для взрослых должен быть булевым значением',
            'advertisingNetworks.array' => 'Рекламные сети должны быть массивом',
            'advertisingNetworks.max' => 'Слишком много рекламных сетей (максимум 50)',
            'languages.array' => 'Языки должны быть массивом',
            'languages.max' => 'Слишком много языков (максимум 100)',
            'languages.*.size' => 'Код языка должен содержать 2 символа',
            'cr_languages.string' => 'Коды языков должны быть строкой',
            'cr_languages.max' => 'Строка кодов языков не должна превышать 500 символов',
            'page.integer' => 'Номер страницы должен быть числом',
            'page.min' => 'Номер страницы не может быть меньше 1',
            'page.max' => 'Номер страницы не может быть больше 10000',
            'perPage.integer' => 'Количество элементов на странице должно быть числом',
            'perPage.min' => 'Минимум 6 элементов на странице',
            'perPage.max' => 'Максимум 100 элементов на странице',
        ];
    }

    /**
     * Get custom attribute names for validation errors.
     */
    public function attributes(): array
    {
        return [
            'searchKeyword' => 'поисковое слово',
            'country' => 'страна',
            'dateCreation' => 'дата создания',
            'sortBy' => 'сортировка',
            'periodDisplay' => 'период отображения',
            'onlyAdult' => 'только для взрослых',
            'advertisingNetworks' => 'рекламные сети',
            'languages' => 'языки',
            'operatingSystems' => 'операционные системы',
            'browsers' => 'браузеры',
            'devices' => 'устройства',
            'imageSizes' => 'размеры изображений',
            'page' => 'страница',
            'perPage' => 'элементов на странице',
            'activeTab' => 'активная вкладка',
        ];
    }
}

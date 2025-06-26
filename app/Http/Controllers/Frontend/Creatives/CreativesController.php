<?php

namespace App\Http\Controllers\Frontend\Creatives;

use App\Http\Controllers\FrontendController;
use App\Http\Requests\Frontend\CreativesRequest;
use App\Helpers\IsoCodesHelper;
use App\Models\AdvertismentNetwork;
use App\Models\Browser;
use App\Enums\Frontend\DeviceType;
use App\Enums\Frontend\OperationSystem;
use Illuminate\Http\Request;

class CreativesController extends FrontendController
{
    public function index(CreativesRequest $request)
    {
        // Дефолтные значения фильтров (состояние) - что выбрано по умолчанию
        $defaultFilters = [
            'country' => 'default',
            'dateCreation' => 'default',
            'sortBy' => 'default',
            'periodDisplay' => 'default',
            'searchKeyword' => '',
            'onlyAdult' => false,
            'isDetailedVisible' => false,
            // Выбранные значения - пустые массивы
            'advertisingNetworks' => [],
            'languages' => [],
            'operatingSystems' => [],
            'browsers' => [],
            'devices' => [],
            'imageSizes' => [],
            'savedSettings' => []
        ];

        // Получаем валидированные фильтры из Request
        $validatedFilters = $request->getCreativesFilters();

        // Получаем activeTab из валидированных данных или дефолтное значение  
        $activeTabFromUrl = $validatedFilters['activeTab'] ?? 'push';

        // Дефолтные значения для вкладок (без activeTab - он передается через tabOptions)
        $defaultTabs = [
            'availableTabs' => ['push', 'inpage', 'facebook', 'tiktok'],
            'tabCounts' => [
                'push' => 1700000,
                'inpage' => 965100,
                'facebook' => 65100,
                'tiktok' => 9852000,
                'total' => 10000000
            ]
        ];

        $translations = [
            'country' => 'Страна',
            'dateCreation' => 'Дата создания',
            'sortBy' => 'Сортировка',
            'periodDisplay' => 'Период отображения',
            'searchKeyword' => 'Поиск',
            'onlyAdult' => 'Только для взрослых',
            'isDetailedVisible' => 'Подробный фильтр',
            'advertisingNetworks' => 'Рекламные сети',
            'languages' => 'Языки',
            'operatingSystems' => 'Операционные системы',
            'browsers' => 'Браузеры',
            'devices' => 'Устройства',
            'imageSizes' => 'Размеры изображений',
            'savedSettings' => 'Сохраненные настройки',
            'onPage' => 'На странице',
            'favorites' => 'Избранное',
            'filter' => 'Фильтр',
            'filterBy' => 'Фильтр по',
            'customDateLabel' => 'Выбрать дату',
            'filterByCountry' => 'Фильтр по стране',
            'filterByDateCreation' => 'Фильтр по дате создания',
            'savePresetButton' => 'Сохранить настройки',
            'resetButton' => 'Сбросить',
            // Переводы для вкладок
            'tabs.push' => 'Push',
            'tabs.inpage' => 'In Page',
            'tabs.facebook' => 'Facebook',
            'tabs.tiktok' => 'TikTok',
        ];

        // Минимальные переводы только для Vue компонентов (оптимизация памяти)
        $listTranslations = [
            'loading' => __('creatives.loading'),
            'error' => __('creatives.error'),
            'retry' => __('creatives.retry'),
            'noData' => __('creatives.no-data'),
            'previousPage' => __('creatives.previous-page'),
            'nextPage' => __('creatives.next-page'),
            'page' => __('creatives.page'),
            'of' => __('creatives.of'),
        ];
        // $vueTranslations = [];

        // Минимальные переводы для фильтров (только необходимые)
        $filtersTranslations = [
            'filter' => __('creatives.filter'),
            'reset' => __('creatives.reset'),
            'country' => __('creatives.country'),
            'search' => __('creatives.search'),
        ];

        // Минимальные переводы для вкладок
        $tabsTranslations = [
            'push' => __('creatives.tabs.push'),
            'inpage' => __('creatives.tabs.inpage'),
            'facebook' => __('creatives.tabs.facebook'),
            'tiktok' => __('creatives.tabs.tiktok'),
        ];

        $selectOptions = $this->getSelectOptions();
        $tabOptions = $this->getTabOptions($activeTabFromUrl);

        // Добавляем perPage для placeholder'ов
        $selectOptions['perPage'] = 12;

        return view('pages.creatives.index', [
            'activeTab' => $activeTabFromUrl,
            'filters' => $defaultFilters,
            'tabs' => $defaultTabs,
            'selectOptions' => $selectOptions,
            'tabOptions' => $tabOptions,
            'translations' => $translations,
            'listTranslations' => $listTranslations,  // Отдельный массив для Vue
            'filtersTranslations' => $filtersTranslations,  // Переводы для фильтров
            'tabsTranslations' => $tabsTranslations,  // Переводы для вкладок
        ]);
    }

    public function apiIndex(CreativesRequest $request)
    {
        // Получаем валидированные и санитизированные фильтры
        $filters = $request->getCreativesFilters();

        // Заглушка данных для тестирования
        $mockCreatives = [];
        for ($i = 1; $i <= 12; $i++) {
            $mockCreatives[] = [
                'id' => $i,
                'name' => "Creative {$i}",
                'category' => 'Test Category',
                'country' => 'US',
                'file_url' => "https://example.com/creative{$i}.jpg",
                'preview_url' => "https://example.com/preview{$i}.jpg",
                'created_at' => now()->subDays(rand(1, 30))->toISOString(),
                'activity_date' => now()->subDays(rand(1, 7))->toISOString(),
                'advertising_networks' => ['facebook', 'google'],
                'languages' => ['en', 'ru'],
                'operating_systems' => ['windows', 'android'],
                'browsers' => ['chrome', 'firefox'],
                'devices' => ['desktop', 'mobile'],
                'image_sizes' => ['16x9', '1x1'],
                'is_adult' => false,
            ];
        }

        return response()->json([
            'status' => 'success',
            'data' => [
                'items' => $mockCreatives,
                'pagination' => [
                    'total' => 120,
                    'perPage' => $filters['perPage'],
                    'currentPage' => $filters['page'],
                    'lastPage' => 10,
                    'from' => (($filters['page'] - 1) * $filters['perPage']) + 1,
                    'to' => min($filters['page'] * $filters['perPage'], 120)
                ],
                'meta' => [
                    'hasSearch' => !empty($filters['searchKeyword']),
                    'activeFiltersCount' => count(array_filter($filters, function ($value, $key) {
                        return !in_array($key, ['page', 'perPage', 'activeTab']) && !empty($value);
                    }, ARRAY_FILTER_USE_BOTH)),
                    'cacheKey' => md5(json_encode($filters)),
                    'appliedFilters' => $filters
                ]
            ]
        ]);
    }

    /**
     * Получить валидированные фильтры
     * 
     * @OA\Get(
     *     path="/api/creatives/filters/validate",
     *     operationId="validateCreativesFilters",
     *     tags={"Креативы - Фильтры"},
     *     summary="Валидировать и санитизировать фильтры креативов",
     *     description="Возвращает валидированные и санитизированные фильтры, отсекая недопустимые значения",
     *     @OA\Parameter(
     *         name="searchKeyword",
     *         in="query",
     *         description="Поисковое слово",
     *         required=false,
     *         @OA\Schema(type="string", maxLength=255)
     *     ),
     *     @OA\Parameter(
     *         name="country",
     *         in="query", 
     *         description="Код страны",
     *         required=false,
     *         @OA\Schema(type="string", maxLength=10)
     *     ),
     *     @OA\Parameter(
     *         name="sortBy",
     *         in="query",
     *         description="Тип сортировки",
     *         required=false,
     *         @OA\Schema(type="string", enum={"creation", "activity", "popularity", "byCreationDate", "byActivity", "byPopularity"})
     *     ),
     *     @OA\Parameter(
     *         name="onlyAdult",
     *         in="query",
     *         description="Только контент для взрослых",
     *         required=false,
     *         @OA\Schema(type="boolean")
     *     ),
     *     @OA\Parameter(
     *         name="page",
     *         in="query",
     *         description="Номер страницы",
     *         required=false,
     *         @OA\Schema(type="integer", minimum=1, maximum=10000)
     *     ),
     *     @OA\Parameter(
     *         name="perPage",
     *         in="query",
     *         description="Количество элементов на странице",
     *         required=false,
     *         @OA\Schema(type="integer", minimum=6, maximum=100)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Валидированные фильтры успешно получены",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(property="filters", type="object",
     *                 @OA\Property(property="searchKeyword", type="string", example="test"),
     *                 @OA\Property(property="country", type="string", example="US"),
     *                 @OA\Property(property="sortBy", type="string", example="creation"),
     *                 @OA\Property(property="onlyAdult", type="boolean", example=false),
     *                 @OA\Property(property="page", type="integer", example=1),
     *                 @OA\Property(property="perPage", type="integer", example=12),
     *                 @OA\Property(property="advertisingNetworks", type="array", @OA\Items(type="string")),
     *                 @OA\Property(property="languages", type="array", @OA\Items(type="string")),
     *                 @OA\Property(property="activeTab", type="string", example="push")
     *             ),
     *             @OA\Property(property="validation", type="object",
     *                 @OA\Property(property="rejectedValues", type="array", @OA\Items(type="string")),
     *                 @OA\Property(property="sanitizedCount", type="integer", example=3)
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Ошибка валидации",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="error"),
     *             @OA\Property(property="message", type="string", example="Ошибка валидации"),
     *             @OA\Property(property="errors", type="object")
     *         )
     *     )
     * )
     */
    public function validateFilters(CreativesRequest $request)
    {
        // Получаем исходные данные для анализа
        $originalInput = $request->all();

        // Получаем валидированные фильтры
        $validatedFilters = $request->getCreativesFilters();

        // Анализируем что было отклонено/санитизировано
        $rejectedValues = [];
        $sanitizedCount = 0;

        // Сравниваем исходные и валидированные значения
        foreach ($originalInput as $key => $value) {
            if (!isset($validatedFilters[$key]) || $validatedFilters[$key] !== $value) {
                $valueString = is_array($value) ? json_encode($value) : (string)$value;
                $rejectedValues[] = "{$key}: {$valueString}";
                $sanitizedCount++;
            }
        }

        return response()->json([
            'status' => 'success',
            'filters' => $validatedFilters,
            'validation' => [
                'rejectedValues' => $rejectedValues,
                'sanitizedCount' => $sanitizedCount,
                'originalCount' => count($originalInput),
                'validatedCount' => count($validatedFilters)
            ]
        ]);
    }

    public function getSelectOptions()
    {
        return [
            'advertisingNetworks' => AdvertismentNetwork::forCreativeFilters(),
            'countries' => IsoCodesHelper::getAllCountries(app()->getLocale()),
            'sortOptions' => [
                ['value' => 'byCreationDate', 'label' => 'По дате создания'],
                ['value' => 'byActivity', 'label' => 'По дням активности'],
                ['value' => 'byPopularity', 'label' => 'По популярности'],
            ],
            'dateRanges' => [
                ['value' => 'today', 'label' => 'Сегодня'],
                ['value' => 'yesterday', 'label' => 'Вчера'],
                ['value' => 'last7', 'label' => 'За последние 7 дней'],
                ['value' => 'last30', 'label' => 'За последние 30 дней'],
                ['value' => 'last90', 'label' => 'За последние 90 дней'],
                ['value' => 'thisMonth', 'label' => 'За текущий месяц'],
                ['value' => 'lastMonth', 'label' => 'За прошлый месяц'],
                ['value' => 'thisYear', 'label' => 'За текущий год'],
                ['value' => 'lastYear', 'label' => 'За прошлый год'],
            ],
            'languages' => IsoCodesHelper::getAllLanguages(app()->getLocale()),
            'operatingSystems' => OperationSystem::getForSelect(),
            'browsers' => Browser::getBrowsersForSelect(),
            'devices' => DeviceType::getForSelect(),
            'imageSizes' => [
                ['value' => '1x1', 'label' => '1x1 (Square)'],
                ['value' => '16x9', 'label' => '16x9 (Landscape)'],
                ['value' => '9x16', 'label' => '9x16 (Portrait)'],
                ['value' => '3x2', 'label' => '3x2 (Classic)'],
                ['value' => '2x3', 'label' => '2x3 (Portrait)'],
                ['value' => '4x3', 'label' => '4x3 (Standard)'],
                ['value' => '3x4', 'label' => '3x4 (Portrait)'],
                ['value' => '21x9', 'label' => '21x9 (Ultra-wide)'],
            ],
        ];
    }

    public function getTabOptions($activeTab = 'push')
    {
        return [
            'availableTabs' => ['push', 'inpage', 'facebook', 'tiktok'],
            'tabCounts' => [
                'push' => 170000,
                'inpage' => 965100,
                'facebook' => 65100,
                'tiktok' => 9852000,
                'total' => 10000000
            ],
            'activeTab' => $activeTab
        ];
    }
}

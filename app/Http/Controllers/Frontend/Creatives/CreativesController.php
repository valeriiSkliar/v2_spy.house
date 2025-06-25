<?php

namespace App\Http\Controllers\Frontend\Creatives;

use App\Http\Controllers\FrontendController;
use App\Helpers\IsoCodesHelper;
use App\Models\AdvertismentNetwork;
use App\Models\Browser;
use App\Enums\Frontend\DeviceType;
use App\Enums\Frontend\OperationSystem;
use Illuminate\Http\Request;

class CreativesController extends FrontendController
{
    public function index(Request $request)
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

        // Дефолтные значения для вкладок
        $defaultTabs = [
            'activeTab' => $request->get('tab', 'push'),
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

        $selectOptions = $this->getSelectOptions();
        $tabOptions = $this->getTabOptions();

        return view('pages.creatives.index', [
            'activeTab' => $defaultTabs['activeTab'],
            'filters' => $defaultFilters,
            'tabs' => $defaultTabs,
            'selectOptions' => $selectOptions,
            'tabOptions' => $tabOptions,
            'translations' => $translations,
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

    public function getTabOptions()
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
            'activeTab' => 'push'
        ];
    }

    /**
     * Получить все страны
     * 
     * @OA\Get(
     *     path="/api/creatives/countries/all",
     *     operationId="getAllCountries",
     *     tags={"Креативы - Страны"},
     *     summary="Получить список всех стран",
     *     description="Возвращает полный список стран с переводами",
     *     @OA\Parameter(
     *         name="lang",
     *         in="query",
     *         description="Код языка для локализации",
     *         required=false,
     *         @OA\Schema(type="string", example="ru")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Список стран успешно получен",
     *         @OA\JsonContent(
     *             @OA\Property(property="countries", type="array", 
     *                 @OA\Items(
     *                     @OA\Property(property="code", type="string", example="US"),
     *                     @OA\Property(property="name", type="string", example="Соединенные Штаты"),
     *                     @OA\Property(property="flag", type="string", example="🇺🇸")
     *                 )
     *             )
     *         )
     *     )
     * )
     */
    public function getAllCountries(Request $request)
    {
        $languageCode = $request->get('lang', app()->getLocale());
        return response()->json([
            'countries' => IsoCodesHelper::getAllCountries($languageCode)
        ]);
    }

    /**
     * Получить все языки
     * 
     * @OA\Get(
     *     path="/api/creatives/languages/all",
     *     operationId="getAllLanguages",
     *     tags={"Креативы - Языки"},
     *     summary="Получить список всех языков",
     *     description="Возвращает полный список языков с переводами",
     *     @OA\Parameter(
     *         name="lang",
     *         in="query",
     *         description="Код языка для локализации",
     *         required=false,
     *         @OA\Schema(type="string", example="ru")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Список языков успешно получен",
     *         @OA\JsonContent(
     *             @OA\Property(property="languages", type="array", 
     *                 @OA\Items(
     *                     @OA\Property(property="code", type="string", example="en"),
     *                     @OA\Property(property="name", type="string", example="English"),
     *                     @OA\Property(property="nativeName", type="string", example="English")
     *                 )
     *             )
     *         )
     *     )
     * )
     */
    public function getAllLanguages(Request $request)
    {
        $languageCode = $request->get('lang', app()->getLocale());
        return response()->json([
            'languages' => IsoCodesHelper::getAllLanguages($languageCode)
        ]);
    }

    /**
     * Получить популярные страны
     * 
     * @OA\Get(
     *     path="/api/creatives/countries/popular",
     *     operationId="getPopularCountries",
     *     tags={"Креативы - Страны"},
     *     summary="Получить список популярных стран",
     *     description="Возвращает список наиболее популярных стран для рекламы",
     *     @OA\Parameter(
     *         name="lang",
     *         in="query",
     *         description="Код языка для локализации",
     *         required=false,
     *         @OA\Schema(type="string", example="ru")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Список популярных стран успешно получен",
     *         @OA\JsonContent(
     *             @OA\Property(property="countries", type="array", 
     *                 @OA\Items(
     *                     @OA\Property(property="code", type="string", example="US"),
     *                     @OA\Property(property="name", type="string", example="Соединенные Штаты"),
     *                     @OA\Property(property="flag", type="string", example="🇺🇸"),
     *                     @OA\Property(property="popularity", type="integer", example=95)
     *                 )
     *             )
     *         )
     *     )
     * )
     */
    public function getPopularCountries(Request $request)
    {
        $languageCode = $request->get('lang', app()->getLocale());
        return response()->json([
            'countries' => IsoCodesHelper::getPopularCountries($languageCode)
        ]);
    }

    /**
     * Получить все браузеры
     * 
     * @OA\Get(
     *     path="/api/creatives/browsers/all",
     *     operationId="getAllBrowsers",
     *     tags={"Креативы - Браузеры"},
     *     summary="Получить список всех браузеров",
     *     description="Возвращает полный список браузеров для фильтрации креативов",
     *     @OA\Response(
     *         response=200,
     *         description="Список браузеров успешно получен",
     *         @OA\JsonContent(
     *             @OA\Property(property="browsers", type="array", 
     *                 @OA\Items(
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="name", type="string", example="Chrome"),
     *                     @OA\Property(property="version", type="string", example="120.0"),
     *                     @OA\Property(property="device_type", type="string", example="desktop")
     *                 )
     *             )
     *         )
     *     )
     * )
     */
    public function getAllBrowsers(Request $request)
    {
        return response()->json([
            'browsers' => Browser::getBrowsersForSelect()
        ]);
    }

    /**
     * Получить популярные браузеры
     * 
     * @OA\Get(
     *     path="/api/creatives/browsers/popular",
     *     operationId="getPopularBrowsers",
     *     tags={"Креативы - Браузеры"},
     *     summary="Получить список популярных браузеров",
     *     description="Возвращает список наиболее популярных браузеров с возможностью ограничения количества",
     *     @OA\Parameter(
     *         name="limit",
     *         in="query",
     *         description="Максимальное количество браузеров в ответе",
     *         required=false,
     *         @OA\Schema(type="integer", example=10, minimum=1, maximum=50)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Список популярных браузеров успешно получен",
     *         @OA\JsonContent(
     *             @OA\Property(property="browsers", type="array", 
     *                 @OA\Items(
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="name", type="string", example="Chrome"),
     *                     @OA\Property(property="usage_percentage", type="number", format="float", example=65.2),
     *                     @OA\Property(property="device_type", type="string", example="desktop")
     *                 )
     *             )
     *         )
     *     )
     * )
     */
    public function getPopularBrowsers(Request $request)
    {
        $limit = $request->get('limit', 10);
        return response()->json([
            'browsers' => Browser::getPopularBrowsersForSelect($limit)
        ]);
    }

    /**
     * Получить мобильные браузеры
     * 
     * @OA\Get(
     *     path="/api/creatives/browsers/mobile",
     *     operationId="getMobileBrowsers",
     *     tags={"Креативы - Браузеры"},
     *     summary="Получить список мобильных браузеров",
     *     description="Возвращает список браузеров для мобильных устройств",
     *     @OA\Response(
     *         response=200,
     *         description="Список мобильных браузеров успешно получен",
     *         @OA\JsonContent(
     *             @OA\Property(property="browsers", type="array", 
     *                 @OA\Items(
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="name", type="string", example="Chrome Mobile"),
     *                     @OA\Property(property="version", type="string", example="120.0"),
     *                     @OA\Property(property="device_type", type="string", example="mobile")
     *                 )
     *             )
     *         )
     *     )
     * )
     */
    public function getMobileBrowsers(Request $request)
    {
        return response()->json([
            'browsers' => Browser::getMobileBrowsersForSelect()
        ]);
    }

    /**
     * Получить десктопные браузеры
     * 
     * @OA\Get(
     *     path="/api/creatives/browsers/desktop",
     *     operationId="getDesktopBrowsers",
     *     tags={"Креативы - Браузеры"},
     *     summary="Получить список десктопных браузеров",
     *     description="Возвращает список браузеров для настольных компьютеров",
     *     @OA\Response(
     *         response=200,
     *         description="Список десктопных браузеров успешно получен",
     *         @OA\JsonContent(
     *             @OA\Property(property="browsers", type="array", 
     *                 @OA\Items(
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="name", type="string", example="Chrome"),
     *                     @OA\Property(property="version", type="string", example="120.0"),
     *                     @OA\Property(property="device_type", type="string", example="desktop")
     *                 )
     *             )
     *         )
     *     )
     * )
     */
    public function getDesktopBrowsers(Request $request)
    {
        return response()->json([
            'browsers' => Browser::getDesktopBrowsersForSelect()
        ]);
    }

    /**
     * Получить браузеры с группировкой по устройствам
     * 
     * @OA\Get(
     *     path="/api/creatives/browsers/grouped",
     *     operationId="getBrowsersGrouped",
     *     tags={"Креативы - Браузеры"},
     *     summary="Получить браузеры сгруппированные по типам устройств",
     *     description="Возвращает браузеры разделенные на группы: desktop, mobile, tablet",
     *     @OA\Response(
     *         response=200,
     *         description="Сгруппированные браузеры успешно получены",
     *         @OA\JsonContent(
     *             @OA\Property(property="browsers", type="object",
     *                 @OA\Property(property="desktop", type="array",
     *                     @OA\Items(
     *                         @OA\Property(property="id", type="integer", example=1),
     *                         @OA\Property(property="name", type="string", example="Chrome"),
     *                         @OA\Property(property="version", type="string", example="120.0")
     *                     )
     *                 ),
     *                 @OA\Property(property="mobile", type="array",
     *                     @OA\Items(
     *                         @OA\Property(property="id", type="integer", example=2),
     *                         @OA\Property(property="name", type="string", example="Chrome Mobile"),
     *                         @OA\Property(property="version", type="string", example="120.0")
     *                     )
     *                 ),
     *                 @OA\Property(property="tablet", type="array",
     *                     @OA\Items(
     *                         @OA\Property(property="id", type="integer", example=3),
     *                         @OA\Property(property="name", type="string", example="Safari"),
     *                         @OA\Property(property="version", type="string", example="17.0")
     *                     )
     *                 )
     *             )
     *         )
     *     )
     * )
     */
    public function getBrowsersGrouped(Request $request)
    {
        return response()->json([
            'browsers' => Browser::getBrowsersGroupedByDevice()
        ]);
    }

    /**
     * Поиск браузеров
     * 
     * @OA\Get(
     *     path="/api/creatives/browsers/search",
     *     operationId="searchBrowsers",
     *     tags={"Креативы - Браузеры"},
     *     summary="Поиск браузеров по названию",
     *     description="Выполняет поиск браузеров по частичному совпадению названия",
     *     @OA\Parameter(
     *         name="q",
     *         in="query",
     *         description="Поисковый запрос (минимум 2 символа)",
     *         required=true,
     *         @OA\Schema(type="string", example="chro", minLength=2)
     *     ),
     *     @OA\Parameter(
     *         name="limit",
     *         in="query",
     *         description="Максимальное количество результатов",
     *         required=false,
     *         @OA\Schema(type="integer", example=20, minimum=1, maximum=100)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Результаты поиска браузеров",
     *         @OA\JsonContent(
     *             @OA\Property(property="browsers", type="array", 
     *                 @OA\Items(
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="name", type="string", example="Chrome"),
     *                     @OA\Property(property="version", type="string", example="120.0"),
     *                     @OA\Property(property="device_type", type="string", example="desktop"),
     *                     @OA\Property(property="relevance", type="number", format="float", example=0.95)
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Некорректный поисковый запрос",
     *         @OA\JsonContent(
     *             @OA\Property(property="browsers", type="array", @OA\Items())
     *         )
     *     )
     * )
     */
    public function searchBrowsers(Request $request)
    {
        $query = $request->get('q', '');
        $limit = $request->get('limit', 20);

        if (strlen($query) < 2) {
            return response()->json([
                'browsers' => []
            ]);
        }

        return response()->json([
            'browsers' => Browser::searchBrowsers($query, $limit)
        ]);
    }

    /**
     * Получить статистику браузеров
     * 
     * @OA\Get(
     *     path="/api/creatives/browsers/stats",
     *     operationId="getBrowserStats",
     *     tags={"Креативы - Браузеры"},
     *     summary="Получить статистику использования браузеров",
     *     description="Возвращает статистику популярности и использования браузеров",
     *     @OA\Response(
     *         response=200,
     *         description="Статистика браузеров успешно получена",
     *         @OA\JsonContent(
     *             @OA\Property(property="stats", type="object",
     *                 @OA\Property(property="total_browsers", type="integer", example=156),
     *                 @OA\Property(property="desktop_count", type="integer", example=89),
     *                 @OA\Property(property="mobile_count", type="integer", example=52),
     *                 @OA\Property(property="tablet_count", type="integer", example=15),
     *                 @OA\Property(property="top_browsers", type="array",
     *                     @OA\Items(
     *                         @OA\Property(property="name", type="string", example="Chrome"),
     *                         @OA\Property(property="usage_percentage", type="number", format="float", example=65.2),
     *                         @OA\Property(property="device_type", type="string", example="desktop")
     *                     )
     *                 ),
     *                 @OA\Property(property="usage_by_device", type="object",
     *                     @OA\Property(property="desktop", type="number", format="float", example=45.3),
     *                     @OA\Property(property="mobile", type="number", format="float", example=38.7),
     *                     @OA\Property(property="tablet", type="number", format="float", example=16.0)
     *                 )
     *             )
     *         )
     *     )
     * )
     */
    public function getBrowserStats(Request $request)
    {
        return response()->json([
            'stats' => Browser::getBrowserUsageStats()
        ]);
    }

    /**
     * Очистить кэш браузеров
     * 
     * @OA\Delete(
     *     path="/api/creatives/browsers/cache",
     *     operationId="clearBrowsersCache",
     *     tags={"Креативы - Браузеры"},
     *     summary="Очистить кэш браузеров",
     *     description="Очищает кэш данных о браузерах (требует права администратора)",
     *     security={{"bearerAuth": {}}},
     *     @OA\Response(
     *         response=200,
     *         description="Кэш браузеров успешно очищен",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Browser cache cleared successfully")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Неавторизованный доступ",
     *         @OA\JsonContent(
     *             @OA\Property(property="error", type="string", example="Unauthorized")
     *         )
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Недостаточно прав доступа",
     *         @OA\JsonContent(
     *             @OA\Property(property="error", type="string", example="Forbidden")
     *         )
     *     )
     * )
     */
    public function clearBrowsersCache(Request $request)
    {
        // Добавить проверку прав доступа при необходимости
        Browser::clearBrowsersCache();

        return response()->json([
            'message' => 'Browser cache cleared successfully'
        ]);
    }
}

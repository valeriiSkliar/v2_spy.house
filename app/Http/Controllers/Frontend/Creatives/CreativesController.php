<?php

namespace App\Http\Controllers\Frontend\Creatives;

use App\Http\Controllers\FrontendController;
use App\Helpers\IsoCodesHelper;
use App\Models\AdvertismentNetwork;
use App\Models\Browser;
use App\Enums\Frontend\DeviceType;
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
            'operatingSystems' => [
                ['value' => 'Windows', 'label' => 'Windows'],
                ['value' => 'MacOS', 'label' => 'MacOS'],
                ['value' => 'Linux', 'label' => 'Linux'],
                ['value' => 'Android', 'label' => 'Android'],
                ['value' => 'iOS', 'label' => 'iOS'],
                ['value' => 'Chrome OS', 'label' => 'Chrome OS'],
            ],
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
     * API метод для получения всех стран (AJAX)
     */
    public function getAllCountries(Request $request)
    {
        $languageCode = $request->get('lang', app()->getLocale());
        return response()->json([
            'countries' => IsoCodesHelper::getAllCountries($languageCode)
        ]);
    }

    /**
     * API метод для получения всех языков (AJAX)
     */
    public function getAllLanguages(Request $request)
    {
        $languageCode = $request->get('lang', app()->getLocale());
        return response()->json([
            'languages' => IsoCodesHelper::getAllLanguages($languageCode)
        ]);
    }

    /**
     * API метод для получения популярных стран (AJAX)
     */
    public function getPopularCountries(Request $request)
    {
        $languageCode = $request->get('lang', app()->getLocale());
        return response()->json([
            'countries' => IsoCodesHelper::getPopularCountries($languageCode)
        ]);
    }

    /**
     * API метод для получения всех браузеров (AJAX)
     */
    public function getAllBrowsers(Request $request)
    {
        return response()->json([
            'browsers' => Browser::getBrowsersForSelect()
        ]);
    }

    /**
     * API метод для получения популярных браузеров (AJAX)
     */
    public function getPopularBrowsers(Request $request)
    {
        $limit = $request->get('limit', 10);
        return response()->json([
            'browsers' => Browser::getPopularBrowsersForSelect($limit)
        ]);
    }

    /**
     * API метод для получения мобильных браузеров (AJAX)
     */
    public function getMobileBrowsers(Request $request)
    {
        return response()->json([
            'browsers' => Browser::getMobileBrowsersForSelect()
        ]);
    }

    /**
     * API метод для получения десктопных браузеров (AJAX)
     */
    public function getDesktopBrowsers(Request $request)
    {
        return response()->json([
            'browsers' => Browser::getDesktopBrowsersForSelect()
        ]);
    }

    /**
     * API метод для получения браузеров с группировкой по устройствам (AJAX)
     */
    public function getBrowsersGrouped(Request $request)
    {
        return response()->json([
            'browsers' => Browser::getBrowsersGroupedByDevice()
        ]);
    }

    /**
     * API метод для поиска браузеров (AJAX)
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
     * API метод для получения статистики браузеров (AJAX)
     */
    public function getBrowserStats(Request $request)
    {
        return response()->json([
            'stats' => Browser::getBrowserUsageStats()
        ]);
    }

    /**
     * API метод для очистки кэша браузеров (только для админов)
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

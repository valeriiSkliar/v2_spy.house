<?php

namespace App\Http\Controllers\Frontend\Creatives;

use App\Http\Controllers\FrontendController;
use App\Helpers\IsoCodesHelper;
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
            'advertisingNetworks' => [
                ['value' => 'GOOGLE', 'label' => 'Google'],
                ['value' => 'META', 'label' => 'Meta'],
                ['value' => 'AMAZON', 'label' => 'Amazon'],
                ['value' => 'MICROSOFT', 'label' => 'Microsoft'],
                ['value' => 'APPLE', 'label' => 'Apple'],
                ['value' => 'TWITTER', 'label' => 'Twitter'],
                ['value' => 'TIKTOK', 'label' => 'TikTok'],
            ],
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
            'browsers' => [
                ['value' => 'Chrome', 'label' => 'Chrome'],
                ['value' => 'Firefox', 'label' => 'Firefox'],
                ['value' => 'Safari', 'label' => 'Safari'],
                ['value' => 'Edge', 'label' => 'Edge'],
                ['value' => 'Opera', 'label' => 'Opera'],
                ['value' => 'Samsung Internet', 'label' => 'Samsung Internet'],
                ['value' => 'UC Browser', 'label' => 'UC Browser'],
            ],
            'devices' => [
                ['value' => 'Desktop', 'label' => 'Desktop'],
                ['value' => 'Mobile', 'label' => 'Mobile'],
                ['value' => 'Tablet', 'label' => 'Tablet'],
                ['value' => 'Smart TV', 'label' => 'Smart TV'],
                ['value' => 'Smart Watch', 'label' => 'Smart Watch'],
                ['value' => 'Smart Speaker', 'label' => 'Smart Speaker'],
                ['value' => 'Smart Home', 'label' => 'Smart Home'],
                ['value' => 'Gaming Console', 'label' => 'Gaming Console'],
            ],
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
}

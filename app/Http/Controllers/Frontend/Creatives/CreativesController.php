<?php

namespace App\Http\Controllers\Frontend\Creatives;

use App\Http\Controllers\FrontendController;
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
            'filterByCountry' => 'Фильтр по стране',
            'filterByDateCreation' => 'Фильтр по дате создания',
            'savePresetButton' => 'Сохранить настройки',
            'resetButton' => 'Сбросить',
        ];


        $selectOptions = $this->getSelectOptions();

        return view('pages.creatives.index', [
            'activeTab' => 'push',
            'filters' => $defaultFilters,
            'selectOptions' => $selectOptions,
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
            'countries' => [
                [
                    'value' => 'USA',
                    'label' => 'USA',
                ],
                [
                    'value' => 'Canada',
                    'label' => 'Canada',
                ],
                [
                    'value' => 'UK',
                    'label' => 'UK',
                ],
                [
                    'value' => 'Australia',
                    'label' => 'Australia',
                ],
                [
                    'value' => 'Germany',
                    'label' => 'Germany',
                ],
                [
                    'value' => 'France',
                    'label' => 'France',
                ],
                [
                    'value' => 'Italy',
                    'label' => 'Italy',
                ],
                [
                    'value' => 'Spain',
                    'label' => 'Spain',
                ],
                [
                    'value' => 'Portugal',
                    'label' => 'Portugal',
                ],
                [
                    'value' => 'Brazil',
                    'label' => 'Brazil',
                ],
            ],
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
            'languages' => [
                ['value' => 'en', 'label' => 'English'],
                ['value' => 'ru', 'label' => 'Russian'],
                ['value' => 'de', 'label' => 'German'],
                ['value' => 'fr', 'label' => 'French'],
                ['value' => 'es', 'label' => 'Spanish'],
                ['value' => 'it', 'label' => 'Italian'],
                ['value' => 'pt', 'label' => 'Portuguese'],
                ['value' => 'zh', 'label' => 'Chinese'],
                ['value' => 'ja', 'label' => 'Japanese'],
                ['value' => 'ko', 'label' => 'Korean'],
            ],
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
}

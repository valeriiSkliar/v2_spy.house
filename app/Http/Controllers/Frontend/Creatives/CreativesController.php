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
            'country' => 'All Countries',
            'dateCreation' => 'Date of creation',
            'sortBy' => 'By creation date',
            'periodDisplay' => 'Period of display',
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


        $selectOptions = $this->getSelectOptions();

        return view('pages.creatives.index', [
            'activeTab' => 'push',
            'filters' => $defaultFilters,
            'selectOptions' => $selectOptions,
        ]);
    }

    public function getSelectOptions()
    {
        return [
            'advertisingNetworks' => [
                ['value' => 'Google', 'label' => 'Google'],
                ['value' => 'Meta', 'label' => 'Meta'],
                ['value' => 'Amazon', 'label' => 'Amazon'],
                ['value' => 'Microsoft', 'label' => 'Microsoft'],
                ['value' => 'Apple', 'label' => 'Apple'],
                ['value' => 'Twitter', 'label' => 'Twitter'],
                ['value' => 'TikTok', 'label' => 'TikTok'],
            ],
            'countries' => [
                [
                    'value' => 'All Countries',
                    'label' => 'All Countries',
                ],
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
            'languages' => [
                ['value' => 'en', 'label' => 'English'],
                ['value' => 'ru', 'label' => 'Russian'],
                ['value' => 'de', 'label' => 'German'],
                ['value' => 'fr', 'label' => 'French'],
                ['value' => 'es', 'label' => 'Spanish'],
                ['value' => 'it', 'label' => 'Italian'],
                ['value' => 'pt', 'label' => 'Portuguese'],
            ],
            'operatingSystems' => [
                ['value' => 'Windows', 'label' => 'Windows'],
                ['value' => 'MacOS', 'label' => 'MacOS'],
                ['value' => 'Linux', 'label' => 'Linux'],
                ['value' => 'Android', 'label' => 'Android'],
                ['value' => 'iOS', 'label' => 'iOS'],
            ],
            'browsers' => [
                ['value' => 'Chrome', 'label' => 'Chrome'],
                ['value' => 'Firefox', 'label' => 'Firefox'],
                ['value' => 'Safari', 'label' => 'Safari'],
                ['value' => 'Edge', 'label' => 'Edge'],
                ['value' => 'Opera', 'label' => 'Opera'],
            ],
            'devices' => [
                ['value' => 'Desktop', 'label' => 'Desktop'],
                ['value' => 'Mobile', 'label' => 'Mobile'],
                ['value' => 'Tablet', 'label' => 'Tablet'],
                ['value' => 'Smart TV', 'label' => 'Smart TV'],
                ['value' => 'Smart Watch', 'label' => 'Smart Watch'],
                ['value' => 'Smart Speaker', 'label' => 'Smart Speaker'],
                ['value' => 'Smart Home', 'label' => 'Smart Home'],
            ],
            'imageSizes' => [
                ['value' => '1x1', 'label' => '1x1'],
                ['value' => '16x9', 'label' => '16x9'],
                ['value' => '9x16', 'label' => '9x16'],
                ['value' => '3x2', 'label' => '3x2'],
                ['value' => '2x3', 'label' => '2x3'],
            ],
        ];
    }
}

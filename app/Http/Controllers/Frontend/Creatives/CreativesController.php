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
                'Google' => 'Google',
                'Meta' => 'Meta',
                'Amazon' => 'Amazon',
                'Microsoft' => 'Microsoft',
                'Apple' => 'Apple',
                'Twitter' => 'Twitter',
                'TikTok' => 'TikTok',
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
                'en' => 'English',
                'ru' => 'Russian',
                'de' => 'German',
                'fr' => 'French',
                'es' => 'Spanish',
                'it' => 'Italian',
                'pt' => 'Portuguese',
            ],
            'operatingSystems' => [
                'Windows' => 'Windows',
                'MacOS' => 'MacOS',
                'Linux' => 'Linux',
                'Android' => 'Android',
                'iOS' => 'iOS',
            ],
            'browsers' => [
                'Chrome' => 'Chrome',
                'Firefox' => 'Firefox',
                'Safari' => 'Safari',
                'Edge' => 'Edge',
                'Opera' => 'Opera',
            ],
            'devices' => [
                'Desktop' => 'Desktop',
                'Mobile' => 'Mobile',
                'Tablet' => 'Tablet',
                'Smart TV' => 'Smart TV',
                'Smart Watch' => 'Smart Watch',
                'Smart Speaker' => 'Smart Speaker',
                'Smart Home' => 'Smart Home',
            ],
            'imageSizes' => [
                '1x1' => '1x1',
                '16x9' => '16x9',
                '9x16' => '9x16',
                '3x2' => '3x2',
                '2x3' => '2x3',
            ],
        ];
    }
}

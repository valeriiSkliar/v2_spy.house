<?php

namespace App\Http\Controllers\Frontend\Creatives;

use App\Http\Requests\Frontend\CreativesRequest;

class CreativesController extends BaseCreativesController
{
    public function index(CreativesRequest $request)
    {
        // Дефолтные значения фильтров (состояние) - что выбрано по умолчанию
        $defaultFilters = $this->getDefaultFilters();

        // Получаем валидированные фильтры из Request
        $validatedFilters = $request->getCreativesFilters();

        // Получаем activeTab из валидированных данных или дефолтное значение  
        $activeTabFromUrl = $validatedFilters['activeTab'] ?? 'push';

        // Обновляем defaultFilters значениями из URL/Request
        $defaultFilters = $this->updateDefaultFilters($validatedFilters);
        // $defaultFilters['perPage'] = $validatedFilters['perPage'] ?? 12;
        // $defaultFilters['searchKeyword'] = $validatedFilters['searchKeyword'] ?? '';
        // $defaultFilters['country'] = $validatedFilters['country'] ?? 'default';
        // $defaultFilters['dateCreation'] = $validatedFilters['dateCreation'] ?? 'default';
        // $defaultFilters['sortBy'] = $validatedFilters['sortBy'] ?? 'default';
        // $defaultFilters['periodDisplay'] = $validatedFilters['periodDisplay'] ?? 'default';
        // $defaultFilters['onlyAdult'] = $validatedFilters['onlyAdult'] ?? false;
        // $defaultFilters['advertisingNetworks'] = $validatedFilters['advertisingNetworks'] ?? [];
        // $defaultFilters['languages'] = $validatedFilters['languages'] ?? [];
        // $defaultFilters['operatingSystems'] = $validatedFilters['operatingSystems'] ?? [];
        // $defaultFilters['browsers'] = $validatedFilters['browsers'] ?? [];
        // $defaultFilters['devices'] = $validatedFilters['devices'] ?? [];
        // $defaultFilters['imageSizes'] = $validatedFilters['imageSizes'] ?? [];
        // Дефолтные значения для вкладок (без activeTab - он передается через tabOptions)
        $defaultTabs = $this->getDefaultTabs();

        $translations = $this->getListTranslations();

        // Минимальные переводы только для Vue компонентов (оптимизация памяти)
        $listTranslations = $this->getListTranslations();
        // $vueTranslations = [];

        // Минимальные переводы для фильтров (только необходимые)
        $filtersTranslations = $this->getFiltersTranslations();

        // Минимальные переводы для вкладок
        $tabsTranslations = $this->getTabsTranslations();

        $selectOptions = $this->getSelectOptions();
        $tabOptions = $this->getTabOptions($activeTabFromUrl);
        $perPageOptions = $this->getPerPageOptions($defaultFilters['perPage']);



        return view('pages.creatives.index', [
            'activeTab' => $activeTabFromUrl,
            'perPage' => $perPageOptions,
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
}

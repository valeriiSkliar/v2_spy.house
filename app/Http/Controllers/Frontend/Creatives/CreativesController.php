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

        $defaultTabs = $this->getDefaultTabs();

        // Новая система переводов - единый источник для всех компонентов
        $allTranslations = $this->getAllTranslationsForFrontend();

        // Обратная совместимость - отдельные массивы переводов (deprecated)
        $translations = $this->getListTranslations();
        $listTranslations = $this->getListTranslations();
        $filtersTranslations = $this->getFiltersTranslations();
        $tabsTranslations = $this->getTabsTranslations();
        $detailsTranslations = $this->getDetailsTranslations();

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

            // Новая система переводов - единый источник
            'allTranslations' => $allTranslations,  // Все переводы в плоском формате

            // Обратная совместимость (deprecated, но поддерживается)
            'translations' => $translations,
            'listTranslations' => $listTranslations,
            'filtersTranslations' => $filtersTranslations,
            'tabsTranslations' => $tabsTranslations,
            'detailsTranslations' => $detailsTranslations,
        ]);
    }
}

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
        $cardTranslations = $this->getCardTranslations();

        $selectOptions = $this->getSelectOptions();
        $tabOptions = $this->getTabOptions($activeTabFromUrl);
        $perPageOptions = $this->getPerPageOptions($defaultFilters['perPage']);

        $searchCount = $this->getSearchCount($validatedFilters);

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
            'cardTranslations' => $cardTranslations,
            'searchCount' => $searchCount,
        ]);
    }

    protected function getSearchCount($filters = [])
    {
        // Базовое количество креативов
        $baseCount = 50000;

        // Уменьшаем количество при наличии поискового запроса
        if (!empty($filters['searchKeyword'])) {
            $searchLength = strlen($filters['searchKeyword']);
            $baseCount = max(100, $baseCount - ($searchLength * 2000));
        }

        // Уменьшаем при выборе конкретной страны (не "default")
        if (!empty($filters['country']) && $filters['country'] !== 'default') {
            $baseCount = (int)($baseCount * 0.3); // 30% от общего количества
        }

        // Уменьшаем при фильтре только для взрослых
        if (!empty($filters['onlyAdult'])) {
            $baseCount = (int)($baseCount * 0.15); // 15% - контент для взрослых
        }

        // Уменьшаем при наличии мультиселект фильтров
        $multiSelectFilters = ['advertisingNetworks', 'languages', 'operatingSystems', 'browsers', 'devices', 'imageSizes'];
        $activeMultiFilters = 0;

        foreach ($multiSelectFilters as $filterKey) {
            if (!empty($filters[$filterKey]) && is_array($filters[$filterKey]) && count($filters[$filterKey]) > 0) {
                $activeMultiFilters++;
            }
        }

        // Каждый активный мультиселект фильтр уменьшает результат на 20%
        for ($i = 0; $i < $activeMultiFilters; $i++) {
            $baseCount = (int)($baseCount * 0.8);
        }

        // Влияние активной вкладки на количество
        $tabMultipliers = [
            'push' => 1.0,      // 100% - самая популярная
            'inpage' => 0.6,    // 60%
            'facebook' => 0.4,  // 40%  
            'tiktok' => 0.8,    // 80%
        ];

        $activeTab = $filters['activeTab'] ?? 'push';
        if (isset($tabMultipliers[$activeTab])) {
            $baseCount = (int)($baseCount * $tabMultipliers[$activeTab]);
        }

        // Влияние сортировки (некоторые могут показывать меньше результатов)
        if (!empty($filters['sortBy'])) {
            switch ($filters['sortBy']) {
                case 'byPopularity':
                    $baseCount = (int)($baseCount * 0.7); // Популярные - меньше
                    break;
                case 'byActivity':
                    $baseCount = (int)($baseCount * 0.9); // Недавно активные
                    break;
                    // byCreationDate - без изменений
            }
        }

        // Влияние периода отображения
        if (!empty($filters['periodDisplay']) && $filters['periodDisplay'] !== 'default') {
            switch ($filters['periodDisplay']) {
                case 'today':
                    $baseCount = (int)($baseCount * 0.05); // 5% за сегодня
                    break;
                case 'yesterday':
                    $baseCount = (int)($baseCount * 0.03); // 3% за вчера
                    break;
                case 'last7':
                    $baseCount = (int)($baseCount * 0.2); // 20% за неделю
                    break;
                case 'last30':
                    $baseCount = (int)($baseCount * 0.6); // 60% за месяц
                    break;
                case 'last90':
                    $baseCount = (int)($baseCount * 0.9); // 90% за квартал
                    break;
            }
        }

        // Минимальное значение
        $baseCount = max(0, $baseCount);

        // Добавляем небольшую случайность для реалистичности (±10%)
        $randomFactor = rand(90, 110) / 100;
        $finalCount = (int)($baseCount * $randomFactor);

        return max(0, $finalCount);
    }

    /**
     * Публичный API метод для получения количества креативов
     * Используется в AJAX запросах
     */
    public function getSearchCountApi(CreativesRequest $request)
    {
        $filters = $request->getCreativesFilters();
        $count = $this->getSearchCount($filters);

        return response()->json([
            'status' => 'success',
            'data' => [
                'count' => $count,
                'filters' => $filters,
                'timestamp' => now()->toISOString()
            ]
        ]);
    }
}

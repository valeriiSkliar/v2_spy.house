<?php

namespace App\Http\Controllers\Frontend\Creatives;

use App\Http\Requests\Frontend\CreativesRequest;
use App\Http\DTOs\CreativesFiltersDTO;
use App\Http\DTOs\CreativesResponseDTO;
use App\Models\Creative;

class CreativesController extends BaseCreativesController
{
    public function index(CreativesRequest $request)
    {
        // Создаем DTO для фильтров с автоматической валидацией и санитизацией
        $filtersDTO = CreativesFiltersDTO::fromRequest($request);

        // Дефолтные значения фильтров (состояние) - что выбрано по умолчанию
        $defaultFilters = $this->getDefaultFilters();

        // Получаем валидированные фильтры из DTO
        $validatedFilters = $filtersDTO->toArray();

        // Получаем activeTab из DTO
        $activeTabFromUrl = $filtersDTO->activeTab;

        // Обновляем defaultFilters значениями из DTO
        $defaultFilters = $this->updateDefaultFilters($validatedFilters);

        // Получаем реальные данные о вкладках из БД
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

        $selectOptions = $this->getSelectOptions($filtersDTO);
        $tabOptions = $this->getTabOptions($activeTabFromUrl);
        $perPageOptions = $this->getPerPageOptions($defaultFilters['perPage']);

        // Получаем реальное количество из БД
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

    /**
     * Публичный API метод для получения количества креативов
     * Использует реальные данные из БД
     */
    public function getSearchCountApi(CreativesRequest $request)
    {
        try {
            // Создаем DTO для фильтров с автоматической валидацией и санитизацией
            $filtersDTO = CreativesFiltersDTO::fromRequest($request);
            $filters = $filtersDTO->toArray();

            // Получаем реальное количество из БД
            $count = $this->getSearchCount($filters);

            return response()->json([
                'status' => 'success',
                'data' => [
                    'count' => $count,
                    'filters' => $filters,
                    'filtersInfo' => [
                        'hasActiveFilters' => $filtersDTO->hasActiveFilters(),
                        'activeFiltersCount' => $filtersDTO->getActiveFiltersCount(),
                        'cacheKey' => $filtersDTO->getCacheKey()
                    ],
                    'timestamp' => now()->toISOString()
                ]
            ]);
        } catch (\InvalidArgumentException $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Invalid filters: ' . $e->getMessage(),
                'data' => [
                    'count' => 0,
                    'filters' => $request->all(),
                    'timestamp' => now()->toISOString()
                ]
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'An error occurred while calculating count: ' . $e->getMessage(),
                'data' => [
                    'count' => 0,
                    'filters' => $request->all(),
                    'timestamp' => now()->toISOString()
                ]
            ], 500);
        }
    }
}

<?php

namespace App\Http\Controllers\Frontend\Creatives;

use App\Http\Controllers\FrontendController;
use App\Http\Requests\Frontend\CreativesRequest;
use App\Helpers\IsoCodesHelper;
use App\Models\AdvertismentNetwork;
use App\Models\Browser;
use App\Enums\Frontend\DeviceType;
use App\Enums\Frontend\OperationSystem;
use Illuminate\Http\Request;

class BaseCreativesController extends FrontendController
{
    public function apiIndex(CreativesRequest $request)
    {
        // Получаем валидированные и санитизированные фильтры
        $filters = $request->getCreativesFilters();

        // Заглушка данных для тестирования
        $mockCreatives = [];
        for ($i = 1; $i <= $filters['perPage']; $i++) {
            $mockCreatives[] = [
                'id' => $i,
                'title' => "Creative {$i}",
                'description' => "Creative {$i} description",
                'country' => 'US',
                'file_url' => "https://picsum.photos/300/200",
                'preview_url' => "https://picsum.photos/300/200",
                'main_image_url' => "https://picsum.photos/300/200",
                'video_url' => "https://dev.vitaliimaksymchuk.com.ua/spy/img/video-3.mp4",
                'has_video' => false,
                'created_at' => now()->subDays(rand(1, 30))->format('Y-m-d'),
                'activity_date' => now()->subDays(rand(1, 7))->format('d'),
                'advertising_networks' => ['facebook', 'google'],
                'languages' => ['en', 'ru'],
                'operating_systems' => ['windows', 'android'],
                'browsers' => ['chrome', 'firefox'],
                'devices' => ['desktop', 'mobile'],
                'image_sizes' => ['16x9', '1x1'],
                'is_adult' => false,
                'is_active' => rand(0, 1) === 1,
            ];
        }

        return response()->json([
            'status' => 'success',
            'data' => [
                'items' => $mockCreatives,
                'pagination' => [
                    'total' => 120,
                    'perPage' => $filters['perPage'],
                    'currentPage' => $filters['page'],
                    'lastPage' => 10,
                    'from' => (($filters['page'] - 1) * $filters['perPage']) + 1,
                    'to' => min($filters['page'] * $filters['perPage'], 120)
                ],
                'meta' => [
                    'hasSearch' => !empty($filters['searchKeyword']),
                    'activeFiltersCount' => count(array_filter($filters, function ($value, $key) {
                        return !in_array($key, ['page', 'perPage', 'activeTab']) && !empty($value);
                    }, ARRAY_FILTER_USE_BOTH)),
                    'cacheKey' => md5(json_encode($filters)),
                    'appliedFilters' => $filters
                ]
            ]
        ]);
    }

    /**
     * Получить валидированные фильтры
     * 
     * @OA\Get(
     *     path="/api/creatives/filters/validate",
     *     operationId="validateCreativesFilters",
     *     tags={"Креативы - Фильтры"},
     *     summary="Валидировать и санитизировать фильтры креативов",
     *     description="Возвращает валидированные и санитизированные фильтры, отсекая недопустимые значения",
     *     @OA\Parameter(
     *         name="searchKeyword",
     *         in="query",
     *         description="Поисковое слово",
     *         required=false,
     *         @OA\Schema(type="string", maxLength=255)
     *     ),
     *     @OA\Parameter(
     *         name="country",
     *         in="query", 
     *         description="Код страны",
     *         required=false,
     *         @OA\Schema(type="string", maxLength=10)
     *     ),
     *     @OA\Parameter(
     *         name="sortBy",
     *         in="query",
     *         description="Тип сортировки",
     *         required=false,
     *         @OA\Schema(type="string", enum={"creation", "activity", "popularity", "byCreationDate", "byActivity", "byPopularity"})
     *     ),
     *     @OA\Parameter(
     *         name="onlyAdult",
     *         in="query",
     *         description="Только контент для взрослых",
     *         required=false,
     *         @OA\Schema(type="boolean")
     *     ),
     *     @OA\Parameter(
     *         name="page",
     *         in="query",
     *         description="Номер страницы",
     *         required=false,
     *         @OA\Schema(type="integer", minimum=1, maximum=10000)
     *     ),
     *     @OA\Parameter(
     *         name="perPage",
     *         in="query",
     *         description="Количество элементов на странице",
     *         required=false,
     *         @OA\Schema(type="integer", minimum=6, maximum=100)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Валидированные фильтры успешно получены",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(property="filters", type="object",
     *                 @OA\Property(property="searchKeyword", type="string", example="test"),
     *                 @OA\Property(property="country", type="string", example="US"),
     *                 @OA\Property(property="sortBy", type="string", example="creation"),
     *                 @OA\Property(property="onlyAdult", type="boolean", example=false),
     *                 @OA\Property(property="page", type="integer", example=1),
     *                 @OA\Property(property="perPage", type="integer", example=12),
     *                 @OA\Property(property="advertisingNetworks", type="array", @OA\Items(type="string")),
     *                 @OA\Property(property="languages", type="array", @OA\Items(type="string")),
     *                 @OA\Property(property="activeTab", type="string", example="push")
     *             ),
     *             @OA\Property(property="validation", type="object",
     *                 @OA\Property(property="rejectedValues", type="array", @OA\Items(type="string")),
     *                 @OA\Property(property="sanitizedCount", type="integer", example=3)
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Ошибка валидации",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="error"),
     *             @OA\Property(property="message", type="string", example="Ошибка валидации"),
     *             @OA\Property(property="errors", type="object")
     *         )
     *     )
     * )
     */
    public function validateFilters(CreativesRequest $request)
    {
        // Получаем исходные данные для анализа
        $originalInput = $request->all();

        // Получаем валидированные фильтры
        $validatedFilters = $request->getCreativesFilters();

        // Анализируем что было отклонено/санитизировано
        $rejectedValues = [];
        $sanitizedCount = 0;

        // Сравниваем исходные и валидированные значения
        foreach ($originalInput as $key => $value) {
            if (!isset($validatedFilters[$key]) || $validatedFilters[$key] !== $value) {
                $valueString = is_array($value) ? json_encode($value) : (string)$value;
                $rejectedValues[] = "{$key}: {$valueString}";
                $sanitizedCount++;
            }
        }

        return response()->json([
            'status' => 'success',
            'filters' => $validatedFilters,
            'validation' => [
                'rejectedValues' => $rejectedValues,
                'sanitizedCount' => $sanitizedCount,
                'originalCount' => count($originalInput),
                'validatedCount' => count($validatedFilters)
            ]
        ]);
    }

    protected function getTabsTranslations(): array
    {
        return [
            'push' => __('creatives.tabs.push'),
            'inpage' => __('creatives.tabs.inpage'),
            'facebook' => __('creatives.tabs.facebook'),
            'tiktok' => __('creatives.tabs.tiktok'),
        ];
    }

    protected function getFiltersTranslations(): array
    {
        return [
            'filter' => __('creatives.filter'),
            'reset' => __('creatives.reset'),
            'country' => __('creatives.country'),
            'search' => __('creatives.search'),
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
            'savePresetButton' => 'Сохранить настройки',
            'resetButton' => 'Сбросить',
        ];
    }

    protected function getListTranslations(): array
    {
        return [
            'loading' => __('creatives.loading'),
            'error' => __('creatives.error'),
            'retry' => __('creatives.retry'),
            'noData' => __('creatives.no-data'),
            'previousPage' => __('creatives.previous-page'),
            'nextPage' => __('creatives.next-page'),
            'page' => __('creatives.page'),
            'of' => __('creatives.of'),
            'perPage' => __('creatives.perPage'),
            'onPage' => 'На странице',
        ];
    }
    protected function getDefaultFilters(): array
    {
        return [
            'country' => 'default',
            'dateCreation' => 'default',
            'sortBy' => 'default',
            'periodDisplay' => 'default',
            'searchKeyword' => '',
            'onlyAdult' => false,
            'isDetailedVisible' => false,
            'perPage' => 12,
            // Выбранные значения - пустые массивы
            'advertisingNetworks' => [],
            'languages' => [],
            'operatingSystems' => [],
            'browsers' => [],
            'devices' => [],
            'imageSizes' => [],
            'savedSettings' => []
        ];
    }

    // Обновляем defaultFilters значениями из URL/Request
    protected function updateDefaultFilters(array $filters): array
    {
        return array_merge($this->getDefaultFilters(), $filters);
    }

    protected function getDefaultTabs(): array
    {
        return [
            'availableTabs' => ['push', 'inpage', 'facebook', 'tiktok'],
            'tabCounts' => [
                'push' => 1700000,
                'inpage' => 965100,
                'facebook' => 65100,
                'tiktok' => 9852000,
                'total' => 10000000
            ]
        ];
    }


    public function getSelectOptions()
    {
        return [
            'perPage' => [
                ['value' => 12, 'label' => '12'],
                ['value' => 24, 'label' => '24'],
                ['value' => 48, 'label' => '48'],
                ['value' => 96, 'label' => '96'],
            ],
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

    public function getPerPageOptions($perPage = 12)
    {
        return [
            'perPageOptions' => [
                ['value' => 12, 'label' => '12'],
                ['value' => 24, 'label' => '24'],
                ['value' => 48, 'label' => '48'],
                ['value' => 96, 'label' => '96'],
            ],
            'activePerPage' => $perPage,
        ];
    }

    public function getTabOptions($activeTab = 'push')
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
            'activeTab' => $activeTab
        ];
    }



    /**
     * Получить количество избранных креативов для текущего пользователя
     * 
     * @OA\Get(
     *     path="/api/creatives/favorites/count",
     *     operationId="getFavoritesCount",
     *     tags={"Креативы - Избранное"},
     *     summary="Получить количество избранных креативов",
     *     description="Возвращает текущее количество креативов в избранном для аутентифицированного пользователя",
     *     security={{"bearerAuth": {}}},
     *     @OA\Response(
     *         response=200,
     *         description="Количество избранного успешно получено",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="count", type="integer", example=42),
     *                 @OA\Property(property="lastUpdated", type="string", format="date-time", example="2024-01-15T10:30:00Z")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Пользователь не аутентифицирован",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="error"),
     *             @OA\Property(property="message", type="string", example="Unauthorized")
     *         )
     *     )
     * )
     */
    public function getFavoritesCount(Request $request)
    {
        // TODO: Реализовать получение реального количества избранного
        // $user = $request->user();
        // $count = $user->favoriteCreatives()->count();

        // Мок данные для тестирования
        $mockCount = rand(20, 100);

        return response()->json([
            'status' => 'success',
            'data' => [
                'count' => $mockCount,
                'lastUpdated' => now()->toISOString()
            ]
        ]);
    }

    /**
     * Добавить креатив в избранное
     * 
     * @OA\Post(
     *     path="/api/creatives/{id}/favorite",
     *     operationId="addToFavorites",
     *     tags={"Креативы - Избранное"},
     *     summary="Добавить креатив в избранное",
     *     description="Добавляет указанный креатив в список избранного пользователя",
     *     security={{"bearerAuth": {}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="ID креатива",
     *         required=true,
     *         @OA\Schema(type="integer", minimum=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Креатив успешно добавлен в избранное",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="creativeId", type="integer", example=123),
     *                 @OA\Property(property="isFavorite", type="boolean", example=true),
     *                 @OA\Property(property="totalFavorites", type="integer", example=43)
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Креатив не найден",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="error"),
     *             @OA\Property(property="message", type="string", example="Creative not found")
     *         )
     *     ),
     *     @OA\Response(
     *         response=409,
     *         description="Креатив уже в избранном",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="error"),
     *             @OA\Property(property="message", type="string", example="Creative already in favorites")
     *         )
     *     )
     * )
     */
    public function addToFavorites(Request $request, $id)
    {
        // TODO: Реализовать добавление в избранное
        // $user = $request->user();
        // $creative = Creative::findOrFail($id);
        // $user->favoriteCreatives()->attach($creative->id);

        // Мок данные для тестирования
        $mockTotalCount = rand(40, 100);

        return response()->json([
            'status' => 'success',
            'data' => [
                'creativeId' => (int)$id,
                'isFavorite' => true,
                'totalFavorites' => $mockTotalCount,
                'addedAt' => now()->toISOString()
            ]
        ]);
    }

    /**
     * Удалить креатив из избранного
     * 
     * @OA\Delete(
     *     path="/api/creatives/{id}/favorite",
     *     operationId="removeFromFavorites",
     *     tags={"Креативы - Избранное"},
     *     summary="Удалить креатив из избранного",
     *     description="Удаляет указанный креатив из списка избранного пользователя",
     *     security={{"bearerAuth": {}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="ID креатива",
     *         required=true,
     *         @OA\Schema(type="integer", minimum=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Креатив успешно удален из избранного",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="creativeId", type="integer", example=123),
     *                 @OA\Property(property="isFavorite", type="boolean", example=false),
     *                 @OA\Property(property="totalFavorites", type="integer", example=41)
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Креатив не найден в избранном",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="error"),
     *             @OA\Property(property="message", type="string", example="Creative not found in favorites")
     *         )
     *     )
     * )
     */
    public function removeFromFavorites(Request $request, $id)
    {
        // TODO: Реализовать удаление из избранного
        // $user = $request->user();
        // $user->favoriteCreatives()->detach($id);

        // Мок данные для тестирования
        $mockTotalCount = rand(20, 80);

        return response()->json([
            'status' => 'success',
            'data' => [
                'creativeId' => (int)$id,
                'isFavorite' => false,
                'totalFavorites' => $mockTotalCount,
                'removedAt' => now()->toISOString()
            ]
        ]);
    }
}

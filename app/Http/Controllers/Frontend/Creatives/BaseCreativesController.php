<?php

namespace App\Http\Controllers\Frontend\Creatives;

use App\Http\Controllers\FrontendController;
use App\Http\Requests\Frontend\CreativesRequest;
use App\Http\DTOs\CreativeDTO;
use App\Http\DTOs\CreativesFiltersDTO;
use App\Helpers\IsoCodesHelper;
use App\Models\AdvertismentNetwork;
use App\Models\Browser;
use App\Enums\Frontend\DeviceType;
use App\Enums\Frontend\OperationSystem;
use Illuminate\Http\Request;

abstract class BaseCreativesController extends FrontendController
{
    /**
     * Получить количество креативов на основе фильтров
     * Должен быть переопределен в дочерних классах
     */
    abstract protected function getSearchCount($filters = []);

    /**
     * Генерация мок данных для тестирования DTO
     */
    protected function generateMockCreativesData(int $count): array
    {
        $mockCreatives = [];
        $categories = ['push', 'inpage', 'banner', 'video', 'native'];
        $countries = ['US', 'GB', 'DE', 'FR', 'CA', 'AU', 'RU', 'UA'];
        $imageSizes = ['16x9', '1x1', '9x16', '3x2', '2x3', '4x3'];

        for ($i = 1; $i <= $count; $i++) {
            $hasVideo = rand(0, 3) === 0; // 25% шанс на видео
            $category = $categories[array_rand($categories)];
            $country = $countries[array_rand($countries)];
            $imageSize = $imageSizes[array_rand($imageSizes)];

            $mockCreatives[] = [
                'id' => $i,
                'name' => "Creative {$i} Name",
                'title' => "Creative {$i} Title",
                'description' => "Detailed description for creative {$i} with marketing content",
                'category' => $category,
                'country' => $country,
                'file_size' => rand(100, 5000) . 'KB',
                'icon_url' => "https://picsum.photos/64/64?random={$i}",
                'landing_page_url' => "https://example-landing-{$i}.com",
                'main_image_url' => "https://picsum.photos/400/300?random={$i}",
                'video_url' => $hasVideo ? "https://dev.vitaliimaksymchuk.com.ua/spy/img/video-3.mp4" : null,
                'has_video' => $hasVideo,
                'created_at' => now()->subDays(rand(1, 30))->format('Y-m-d'),
                'activity_date' => now()->subDays(rand(1, 7))->format('Y-m-d'),
                'advertising_networks' => rand(0, 1) ? ['facebook', 'google'] : ['tiktok'],
                'languages' => rand(0, 1) ? ['en', 'ru'] : ['de'],
                'operating_systems' => rand(0, 1) ? ['windows', 'android'] : ['ios'],
                'browsers' => rand(0, 1) ? ['chrome', 'firefox'] : ['safari'],
                'devices' => rand(0, 1) ? ['desktop', 'mobile'] : ['tablet'],
                'image_sizes' => [$imageSize],
                'main_image_size' => $imageSize,
                'is_adult' => rand(0, 10) === 0, // 10% шанс adult контента
                // Социальные поля
                'social_likes' => rand(100, 50000),
                'social_comments' => rand(10, 5000),
                'social_shares' => rand(5, 1000),
                'duration' => $hasVideo ? rand(15, 120) . 's' : null,
            ];
        }

        return $mockCreatives;
    }
    public function apiIndex(CreativesRequest $request)
    {
        // Создаем DTO для фильтров с автоматической валидацией и санитизацией
        $filtersDTO = CreativesFiltersDTO::fromRequest($request);

        // Генерируем мок данные и преобразуем через DTO для type safety
        $mockCreativesData = $this->generateMockCreativesData($filtersDTO->perPage);

        // Используем DTO для обеспечения type safety между frontend и backend
        // Получаем компактную версию для списков (оптимизация размера ответа)
        $creativesCollection = array_map(
            fn($item) => CreativeDTO::fromArrayWithComputed($item, $request->user()?->id)->toCompactArray(),
            $mockCreativesData
        );

        // Вызываем getSearchCount для консистентности
        $totalCount = $this->getSearchCount($filtersDTO->toArray());

        return response()->json([
            'status' => 'success',
            'data' => [
                'items' => $creativesCollection,
                'pagination' => [
                    'total' => $totalCount,
                    'perPage' => $filtersDTO->perPage,
                    'currentPage' => $filtersDTO->page,
                    'lastPage' => $filtersDTO->getLastPage($totalCount),
                    'from' => $filtersDTO->getFromNumber($totalCount),
                    'to' => $filtersDTO->getToNumber($totalCount)
                ],
                'meta' => [
                    'hasSearch' => !empty($filtersDTO->searchKeyword),
                    'activeFiltersCount' => $filtersDTO->getActiveFiltersCount(),
                    'hasActiveFilters' => $filtersDTO->hasActiveFilters(),
                    'cacheKey' => $filtersDTO->getCacheKey(),
                    'appliedFilters' => $filtersDTO->toArray(),
                    'activeFilters' => $filtersDTO->getActiveFilters()
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

        // Создаем DTO с валидацией
        $filtersDTO = CreativesFiltersDTO::fromRequest($request);
        $validatedFilters = $filtersDTO->toArray();

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
                'validatedCount' => count($validatedFilters),
                'hasActiveFilters' => $filtersDTO->hasActiveFilters(),
                'activeFiltersCount' => $filtersDTO->getActiveFiltersCount(),
                'cacheKey' => $filtersDTO->getCacheKey()
            ]
        ]);
    }

    /**
     * Получить переводы для вкладок в формате для фронтенда
     */
    protected function getTabsTranslations(): array
    {
        return [
            'push' => __('creatives.tabs.push'),
            'inpage' => __('creatives.tabs.inpage'),
            'facebook' => __('creatives.tabs.facebook'),
            'tiktok' => __('creatives.tabs.tiktok'),
        ];
    }

    /**
     * Получить переводы для фильтров в плоском формате (совместимость с фронтенд системой переводов)
     */
    protected function getFiltersTranslations(): array
    {
        return [
            // Основные переводы  
            'title' => __('creatives.filter.title'),
            'reset' => __('creatives.reset'),
            'country' => __('creatives.country'),
            'search' => __('creatives.search'),

            // Плоские ключи для фронтенд совместимости
            'searchKeyword' => __('creatives.searchKeyword'),
            'dateCreation' => __('creatives.dateCreation'),
            'sortBy' => __('creatives.sortBy'),
            'periodDisplay' => __('creatives.periodDisplay'),
            'onlyAdult' => __('creatives.onlyAdult'),
            'isDetailedVisible' => __('creatives.isDetailedVisible'),
            'advertisingNetworks' => __('creatives.advertisingNetworks'),
            'languages' => __('creatives.languages'),
            'operatingSystems' => __('creatives.operatingSystems'),
            'browsers' => __('creatives.browsers'),
            'devices' => __('creatives.devices'),
            'imageSizes' => __('creatives.imageSizes'),
            'savedSettings' => __('creatives.savedSettings'),
            'savePresetButton' => __('creatives.savePresetButton'),
            'resetButton' => __('creatives.resetButton'),
            'customDateLabel' => __('creatives.customDateLabel'),

            // Дополнительные
            'onPage' => __('creatives.filter.on-page'),
        ];
    }

    /**
     * Получить переводы для деталей креативов в формате для фронтенда
     */
    protected function getDetailsTranslations(): array
    {
        return [
            'title' => __('creatives.details.title'),
            'addToFavorites' => __('creatives.details.add-to-favorites'),
            'removeFromFavorites' => __('creatives.details.remove-from-favorites'),
            'copy' => __('creatives.details.copy'),
            'copied' => __('creatives.details.copied'),
            'download' => __('creatives.details.download'),
            'openTab' => __('creatives.details.open-tab'),
            'icon' => __('creatives.details.icon'),
            'image' => __('creatives.details.image'),
            'text' => __('creatives.details.text'),
            'titleField' => __('creatives.details.creative-title'),
            'description' => __('creatives.details.description'),
            'translateText' => __('creatives.details.translate'),
            'redirectsDetails' => __('creatives.details.redirects-details'),
            'advertisingNetworks' => __('creatives.details.advertising-networks'),
            'country' => __('creatives.details.country'),
            'language' => __('creatives.details.language'),
            'firstDisplayDate' => __('creatives.details.first-display-date'),
            'lastDisplayDate' => __('creatives.details.last-display-date'),
            'status' => __('creatives.details.status'),
            'active' => __('creatives.details.active'),
            'inactive' => __('creatives.details.inactive'),
            'share' => __('creatives.details.share'),
            'preview' => __('creatives.details.preview'),
            'information' => __('creatives.details.information'),
            'stats' => __('creatives.details.stats'),
            'close' => __('creatives.details.close'),
        ];
    }

    /**
     * Получить переводы для списков креативов
     */
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
            'onPage' => __('creatives.filter.on-page'),
        ];
    }

    /**
     * Получить переводы состояний
     */
    protected function getStatesTranslations(): array
    {
        return [
            'loading' => __('creatives.states.loading'),
            'error' => __('creatives.states.error'),
            'empty' => __('creatives.states.empty'),
            'success' => __('creatives.states.success'),
            'processing' => __('creatives.states.processing'),
        ];
    }

    /**
     * Получить переводы действий
     */
    protected function getActionsTranslations(): array
    {
        return [
            'retry' => __('creatives.actions.retry'),
            'refresh' => __('creatives.actions.refresh'),
            'loadMore' => __('creatives.actions.load_more'),
            'save' => __('creatives.actions.save'),
            'cancel' => __('creatives.actions.cancel'),
            'edit' => __('creatives.actions.edit'),
            'delete' => __('creatives.actions.delete'),
            'add' => __('creatives.actions.add'),
            'remove' => __('creatives.actions.remove'),
        ];
    }

    /**
     * Получить переводы для карточек креативов
     */
    protected function getCardTranslations(): array
    {
        return [
            'copyButton' => __('creatives.copyButton'),
            'likes' => __('creatives.likes'),
            'comments' => __('creatives.comments'),
            'shared' => __('creatives.shared'),
            'active' => __('creatives.active'),
        ];
    }

    /**
     * Получить все переводы для фронтенда в едином плоском формате
     * (как указано в документации новой системы переводов)
     */
    protected function getAllTranslationsForFrontend(): array
    {
        return array_merge(
            $this->getFiltersTranslations(),
            $this->getTabsTranslations(),
            $this->getDetailsTranslations(),
            $this->getListTranslations(),
            $this->getStatesTranslations(),
            $this->getActionsTranslations(),
            $this->getCardTranslations()
        );
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

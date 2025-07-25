<?php

namespace App\Http\Controllers\Frontend\Creatives;

use App\Http\Controllers\FrontendController;
use App\Http\Requests\Frontend\CreativesRequest;
use App\Http\DTOs\CreativeDTO;
use App\Http\DTOs\CreativesFiltersDTO;
use App\Http\DTOs\CreativesResponseDTO;
use App\Http\DTOs\FilterOptionDTO;
use App\Helpers\IsoCodesHelper;
use App\Models\AdvertismentNetwork;
use App\Models\Browser;
use App\Models\Creative;
use App\Models\FilterPreset;
use App\Enums\Frontend\DeviceType;
use App\Enums\Frontend\OperationSystem;
use App\Enums\Frontend\AdvertisingFormat;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Validation\Rule;

abstract class BaseCreativesController extends FrontendController
{
    /**
     * Получить количество креативов на основе фильтров
     * Теперь использует реальные данные из БД
     */
    protected function getSearchCount($filters = [])
    {
        return Creative::getFilteredCount($filters);
    }

    /**
     * Получить креативы из базы данных с фильтрами
     */
    protected function getCreativesFromDatabase(array $filters, int $perPage = 12): array
    {
        $paginatedCreatives = Creative::getFilteredCreatives($filters, $perPage);

        $creativesData = [];
        foreach ($paginatedCreatives->items() as $creative) {
            $creativesData[] = $creative->toCreativeArray();
        }

        return $creativesData;
    }

    /**
     * Генерация мок данных для тестирования DTO
     * @deprecated Используется только для тестирования, в продакшене использовать getCreativesFromDatabase
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
                'countries' => [$country],
                'file_size' => rand(100, 5000) . 'KB',
                'icon_url' => "https://picsum.photos/64/64?random={$i}",
                'landing_url' => "https://example-landing-{$i}.com",
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
        try {
            // Создаем DTO для фильтров с автоматической валидацией и санитизацией
            // ИСПРАВЛЕНИЕ: Используем обработанные фильтры с URL приоритетами вместо сырых данных
            $processedFilters = $request->getCreativesFilters();
            $filtersDTO = CreativesFiltersDTO::fromArraySafe($processedFilters);

            // Получаем реальные данные из БД вместо мок-данных
            $creativesData = $this->getCreativesFromDatabase($filtersDTO->toArray(), $filtersDTO->perPage);

            // Используем DTO для обеспечения type safety между frontend и backend
            // Получаем компактную версию для списков (оптимизация размера ответа)
            $creativesCollection = array_map(
                fn($item) => CreativeDTO::fromArrayWithComputed($item, $request->user()?->id)->toCompactArray(),
                $creativesData
            );

            // Получаем общее количество результатов из БД
            $totalCount = $this->getSearchCount($filtersDTO->toArray());

            // Создаем стандартизированный ответ через DTO
            $responseDTO = CreativesResponseDTO::success($creativesCollection, $filtersDTO, $totalCount);

            return response()->json($responseDTO->toApiResponse());
        } catch (\InvalidArgumentException $e) {
            // Ошибки валидации фильтров
            $responseDTO = CreativesResponseDTO::error(
                'Invalid filters: ' . $e->getMessage(),
                $request->all()
            );
            return response()->json($responseDTO->toApiResponse(), 422);
        } catch (\Exception $e) {
            // Общие ошибки
            $responseDTO = CreativesResponseDTO::error(
                'An error occurred while fetching creatives: ' . $e->getMessage(),
                $request->all()
            );
            return response()->json($responseDTO->toApiResponse(), 500);
        }
    }

    /**
     * Получить опции фильтров для API
     * 
     * @OA\Get(
     *     path="/api/creatives/filter-options",
     *     operationId="getFilterOptions",
     *     tags={"Креативы - Фильтры"},
     *     summary="Получить опции для всех селектов фильтров",
     *     description="Возвращает все доступные опции для фильтров с учетом текущих выбранных значений",
     *     @OA\Parameter(
     *         name="country",
     *         in="query",
     *         description="Выбранная страна",
     *         required=false,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="advertisingNetworks",
     *         in="query",
     *         description="Выбранные рекламные сети",
     *         required=false,
     *         @OA\Schema(type="array", @OA\Items(type="string"))
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Опции фильтров успешно получены",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="countries", type="array", @OA\Items(
     *                     @OA\Property(property="value", type="string", example="US"),
     *                     @OA\Property(property="label", type="string", example="United States"),
     *                     @OA\Property(property="selected", type="boolean", example=false)
     *                 )),
     *                 @OA\Property(property="sortOptions", type="array", @OA\Items(
     *                     @OA\Property(property="value", type="string", example="byCreationDate"),
     *                     @OA\Property(property="label", type="string", example="По дате создания"),
     *                     @OA\Property(property="selected", type="boolean", example=false)
     *                 )),
     *                 @OA\Property(property="advertisingNetworks", type="array", @OA\Items(
     *                     @OA\Property(property="value", type="string", example="facebook"),
     *                     @OA\Property(property="label", type="string", example="Facebook"),
     *                     @OA\Property(property="count", type="integer", example=1500000),
     *                     @OA\Property(property="selected", type="boolean", example=false)
     *                 ))
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Ошибка сервера",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="error"),
     *             @OA\Property(property="message", type="string", example="Error message")
     *         )
     *     )
     * )
     */
    public function getFilterOptionsApi(CreativesRequest $request)
    {
        try {
            // Создаем DTO из текущих фильтров
            // ИСПРАВЛЕНИЕ: Используем обработанные фильтры с URL приоритетами вместо сырых данных
            $processedFilters = $request->getCreativesFilters();
            $filtersDTO = CreativesFiltersDTO::fromArraySafe($processedFilters);

            // Получаем все опции с учетом текущих фильтров
            $options = $this->getSelectOptions($filtersDTO);

            return response()->json([
                'status' => 'success',
                'data' => $options,
                'meta' => [
                    'timestamp' => now()->toISOString(),
                    'currentFilters' => $filtersDTO->toArray(),
                    'hasActiveFilters' => $filtersDTO->hasActiveFilters(),
                    'activeFiltersCount' => $filtersDTO->getActiveFiltersCount(),
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => __('creatives.api.errors.failed_to_load_filter_options') . ': ' . $e->getMessage(),
                'data' => [],
            ], 500);
        }
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
        try {
            // Получаем исходные данные для анализа
            $originalInput = $request->all();

            // Используем обработанные фильтры с URL приоритетами из CreativesRequest
            $processedFilters = $request->getCreativesFilters();

            // Создаем DTO из обработанных фильтров (они уже имеют правильные приоритеты)
            $filtersDTO = CreativesFiltersDTO::fromArraySafe($processedFilters);
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
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => __('creatives.api.errors.validation_failed') . ': ' . $e->getMessage(),
                'filters' => $request->all()
            ], 422);
        }
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
            'countries' => __('creatives.countries'),
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
            // Переводы MultiSelect
            'multiSelect.selectAll' => __('creatives.multiSelect.selectAll'),
            'multiSelect.clearAll' => __('creatives.multiSelect.clearAll'),
            'multiSelect.noOptionsFound' => __('creatives.multiSelect.noOptionsFound'),
            'multiSelect.search' => __('creatives.multiSelect.search'),
            'multiSelect.selectedItems' => __('creatives.multiSelect.selectedItems'),

            // Переводы BaseSelect
            'baseSelect.selectOption' => __('creatives.baseSelect.selectOption'),
            'baseSelect.noOptionsAvailable' => __('creatives.baseSelect.noOptionsAvailable'),
            'baseSelect.onPage' => __('creatives.baseSelect.onPage'),
            'baseSelect.perPage' => __('creatives.baseSelect.perPage'),

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
            'similarCreatives_title' => __('creatives.details.similar-creatives.title'),
            'promo-premium' => __('creatives.details.similar-creatives.promo-premium'),
            'go' => __('creatives.details.similar-creatives.go'),
            'loadMore' => __('creatives.details.similar-creatives.load-more'),
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
            'countries' => [],
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

    /**
     * Получить дефолтные вкладки из enum AdvertisingFormat
     */
    protected function getDefaultTabs(): array
    {
        $formatCounts = Creative::getFormatCounts();

        return [
            'availableTabs' => [
                AdvertisingFormat::PUSH->value,
                AdvertisingFormat::INPAGE->value,
                AdvertisingFormat::FACEBOOK->value,
                AdvertisingFormat::TIKTOK->value
            ],
            'tabCounts' => $formatCounts
        ];
    }

    /**
     * Получить все опции селектов используя FilterOptionDTO
     */
    public function getSelectOptions(?CreativesFiltersDTO $filtersDTO = null)
    {
        // Если фильтры не переданы, создаем дефолтные
        if (!$filtersDTO) {
            $filtersDTO = CreativesFiltersDTO::fromArraySafe([]);
        }

        return [
            'perPage' => array_map(
                fn($option) => $option->toArray(),
                FilterOptionDTO::perPageOptions($filtersDTO->perPage)
            ),
            'countries' => array_map(
                fn($option) => $option->toArray(),
                FilterOptionDTO::countries(
                    IsoCodesHelper::getAllCountries(app()->getLocale()),
                    $filtersDTO->countries
                )
            ),
            'sortOptions' => array_map(
                fn($option) => $option->toArray(),
                FilterOptionDTO::sortOptions([$filtersDTO->sortBy])
            ),
            'dateRanges' => array_map(
                fn($option) => $option->toArray(),
                FilterOptionDTO::dateRangeOptions($filtersDTO->periodDisplay)
            ),
            'languages' => array_map(
                fn($option) => $option->toArray(),
                FilterOptionDTO::languages(
                    IsoCodesHelper::getAllLanguages(app()->getLocale()),
                    $filtersDTO->languages
                )
            ),
            'imageSizes' => array_map(
                fn($option) => $option->toArray(),
                FilterOptionDTO::imageSizeOptions($filtersDTO->imageSizes)
            ),
            'advertisingNetworks' => array_map(
                fn($option) => $option->toArray(),
                FilterOptionDTO::advertisingNetworksWithCount(
                    AdvertismentNetwork::forCreativeFilters(),
                    $filtersDTO->advertisingNetworks,
                    $this->getNetworksCounts()
                )
            ),
            'operatingSystems' => $this->getOperatingSystemsOptions($filtersDTO->operatingSystems),
            'browsers' => $this->getBrowsersOptions($filtersDTO->browsers),
            'devices' => $this->getDevicesOptions($filtersDTO->devices),
        ];
    }

    /**
     * Получить опции операционных систем
     */
    protected function getOperatingSystemsOptions(array $selectedOS = []): array
    {
        $osOptions = OperationSystem::getForSelect();
        // Конвертируем в массив если это коллекция
        if (is_object($osOptions) && method_exists($osOptions, 'toArray')) {
            $osOptions = $osOptions->toArray();
        }

        $options = [];

        foreach ($osOptions as $os) {
            $value = is_array($os) ? ($os['value'] ?? $os['code'] ?? $os) : $os;
            $label = is_array($os) ? ($os['label'] ?? $os['name'] ?? $value) : $os;

            $options[] = FilterOptionDTO::simple(
                (string)$value,
                (string)$label,
                in_array($value, $selectedOS)
            )->toArray();
        }

        return $options;
    }

    /**
     * Получить опции браузеров
     */
    protected function getBrowsersOptions(array $selectedBrowsers = []): array
    {
        $browserOptions = Browser::getBrowsersForSelect();
        // Конвертируем в массив если это коллекция
        if (is_object($browserOptions) && method_exists($browserOptions, 'toArray')) {
            $browserOptions = $browserOptions->toArray();
        }

        $options = [];

        foreach ($browserOptions as $browser) {
            $value = is_array($browser) ? ($browser['value'] ?? $browser['code'] ?? $browser) : $browser;
            $label = is_array($browser) ? ($browser['label'] ?? $browser['name'] ?? $value) : $browser;

            $options[] = FilterOptionDTO::simple(
                (string)$value,
                (string)$label,
                in_array($value, $selectedBrowsers)
            )->toArray();
        }

        return $options;
    }

    /**
     * Получить опции устройств
     */
    protected function getDevicesOptions(array $selectedDevices = []): array
    {
        $deviceOptions = DeviceType::getForSelect();
        // Конвертируем в массив если это коллекция
        if (is_object($deviceOptions) && method_exists($deviceOptions, 'toArray')) {
            $deviceOptions = $deviceOptions->toArray();
        }

        $options = [];

        foreach ($deviceOptions as $device) {
            $value = is_array($device) ? ($device['value'] ?? $device['code'] ?? $device) : $device;
            $label = is_array($device) ? ($device['label'] ?? $device['name'] ?? $value) : $device;

            $options[] = FilterOptionDTO::simple(
                (string)$value,
                (string)$label,
                in_array($value, $selectedDevices)
            )->toArray();
        }

        return $options;
    }

    /**
     * Получить количества для сетей из БД с кешированием
     */
    protected function getNetworksCounts(): array
    {
        return Cache::remember('creative_networks_counts', 60 * 10, function () {
            $counts = Creative::onlyReady() // Фильтруем только обработанные и валидные креативы
                ->join('advertisment_networks', 'creatives.advertisment_network_id', '=', 'advertisment_networks.id')
                ->selectRaw('advertisment_networks.network_name, COUNT(*) as count')
                ->groupBy('advertisment_networks.network_name')
                ->pluck('count', 'network_name')
                ->toArray();

            // Добавляем значения по умолчанию для сетей без креативов
            $defaultNetworks = ['facebook', 'google', 'tiktok', 'instagram', 'youtube', 'twitter', 'linkedin', 'snapchat'];
            foreach ($defaultNetworks as $network) {
                if (!isset($counts[$network])) {
                    $counts[$network] = 0;
                }
            }

            return $counts;
        });
    }

    public function getPerPageOptions($perPage = 12)
    {
        return [
            'perPageOptions' => array_map(
                fn($option) => $option->toArray(),
                FilterOptionDTO::perPageOptions($perPage)
            ),
            'activePerPage' => $perPage,
        ];
    }

    /**
     * Получить опции для вкладок из enum AdvertisingFormat
     */
    public function getTabOptions($activeTab = 'push')
    {
        $formatCounts = Creative::getFormatCounts();

        $tabOptions = [];
        foreach (AdvertisingFormat::cases() as $format) {
            $formatValue = $format->value;
            $tabOptions[] = FilterOptionDTO::withCount(
                $formatValue,
                ucfirst($formatValue),
                $formatCounts[$formatValue] ?? 0,
                $formatValue === $activeTab
            )->toArray();
        }

        return [
            'availableTabs' => array_map(fn($format) => $format->value, AdvertisingFormat::cases()),
            'tabOptions' => $tabOptions,
            'tabCounts' => $formatCounts,
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
        try {
            $user = $request->user();

            if (!$user) {
                return response()->json([
                    'status' => 'error',
                    'message' => __('creatives.api.errors.user_not_authenticated')
                ], 401);
            }

            $count = $user->getFavoritesCount();

            return response()->json([
                'status' => 'success',
                'data' => [
                    'count' => $count,
                    'lastUpdated' => now()->toISOString()
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => __('creatives.api.errors.failed_to_get_favorites_count') . ': ' . $e->getMessage()
            ], 500);
        }
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
        try {
            $user = $request->user();

            if (!$user) {
                return response()->json([
                    'status' => 'error',
                    'message' => __('creatives.api.errors.user_not_authenticated')
                ], 401);
            }

            $creativeId = (int)$id;

            // Проверяем, существует ли креатив среди готовых к отображению
            $creative = Creative::onlyReady()->find($creativeId);
            if (!$creative) {
                return response()->json([
                    'status' => 'error',
                    'message' => __('creatives.api.errors.creative_not_found')
                ], 404);
            }

            // Проверяем, не добавлен ли уже в избранное
            if ($user->hasFavoriteCreative($creativeId)) {
                // Получаем информацию о том, когда был добавлен
                $existingFavorite = \App\Models\Favorite::where('user_id', $user->id)
                    ->where('creative_id', $creativeId)
                    ->first();

                return response()->json([
                    'status' => 'error',
                    'message' => __('creatives.api.errors.creative_already_in_favorites'),
                    'code' => 'ALREADY_IN_FAVORITES',
                    'data' => [
                        'creativeId' => $creativeId,
                        'isFavorite' => true,
                        'totalFavorites' => $user->getFavoritesCount(),
                        'addedAt' => $existingFavorite ? $existingFavorite->created_at->toISOString() : null,
                        'shouldSync' => true // Подсказка фронтенду обновить состояние
                    ]
                ], 409);
            }

            // Добавляем в избранное
            $favorite = \App\Models\Favorite::addToFavorites($user->id, $creativeId);

            return response()->json([
                'status' => 'success',
                'data' => [
                    'creativeId' => $creativeId,
                    'isFavorite' => true,
                    'totalFavorites' => $user->getFavoritesCount(),
                    'addedAt' => $favorite->created_at->toISOString()
                ]
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'status' => 'error',
                'message' => __('creatives.api.errors.creative_not_found')
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => __('creatives.api.errors.failed_to_add_to_favorites') . ': ' . $e->getMessage()
            ], 500);
        }
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
        try {
            $user = $request->user();

            if (!$user) {
                return response()->json([
                    'status' => 'error',
                    'message' => __('creatives.api.errors.user_not_authenticated')
                ], 401);
            }

            $creativeId = (int)$id;

            // Проверяем, есть ли креатив в избранном
            if (!$user->hasFavoriteCreative($creativeId)) {
                return response()->json([
                    'status' => 'error',
                    'message' => __('creatives.api.errors.creative_not_found_in_favorites'),
                    'code' => 'NOT_IN_FAVORITES',
                    'data' => [
                        'creativeId' => $creativeId,
                        'isFavorite' => false,
                        'totalFavorites' => $user->getFavoritesCount(),
                        'shouldSync' => true // Подсказка фронтенду обновить состояние
                    ]
                ], 404);
            }

            // Удаляем из избранного
            $removed = \App\Models\Favorite::removeFromFavorites($user->id, $creativeId);

            if ($removed) {
                return response()->json([
                    'status' => 'success',
                    'data' => [
                        'creativeId' => $creativeId,
                        'isFavorite' => false,
                        'totalFavorites' => $user->getFavoritesCount(),
                        'removedAt' => now()->toISOString()
                    ]
                ]);
            }

            return response()->json([
                'status' => 'error',
                'message' => __('creatives.api.errors.failed_to_remove_from_favorites')
            ], 500);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => __('creatives.api.errors.failed_to_remove_from_favorites') . ': ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Helper method to check if creative is in user's favorites
     * Used internally by other controller methods
     */
    protected function checkIsFavorite(int $creativeId, Request $request): bool
    {
        $user = $request->user();

        // Если пользователь не аутентифицирован, возвращаем false
        if (!$user) {
            return false;
        }

        return $user->hasFavoriteCreative($creativeId);
    }

    /**
     * Проверить статус избранного для конкретного креатива
     * 
     * @OA\Get(
     *     path="/api/creatives/{id}/favorite/status",
     *     operationId="checkFavoriteStatus",
     *     tags={"Креативы - Избранное"},
     *     summary="Проверить статус избранного",
     *     description="Возвращает актуальный статус избранного для конкретного креатива",
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
     *         description="Статус избранного успешно получен",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="creativeId", type="integer", example=123),
     *                 @OA\Property(property="isFavorite", type="boolean", example=true),
     *                 @OA\Property(property="totalFavorites", type="integer", example=42),
     *                 @OA\Property(property="addedAt", type="string", format="date-time", nullable=true)
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Креатив не найден"
     *     )
     * )
     */
    public function getFavoriteStatus(Request $request, $id)
    {
        try {
            $user = $request->user();

            if (!$user) {
                return response()->json([
                    'status' => 'error',
                    'message' => __('creatives.api.errors.user_not_authenticated')
                ], 401);
            }

            $creativeId = (int)$id;

            // Проверяем, существует ли креатив среди готовых к отображению
            $creative = Creative::onlyReady()->find($creativeId);
            if (!$creative) {
                return response()->json([
                    'status' => 'error',
                    'message' => __('creatives.api.errors.creative_not_found')
                ], 404);
            }

            $isFavorite = $user->hasFavoriteCreative($creativeId);
            $addedAt = null;

            if ($isFavorite) {
                $favorite = \App\Models\Favorite::where('user_id', $user->id)
                    ->where('creative_id', $creativeId)
                    ->first();
                $addedAt = $favorite ? $favorite->created_at->toISOString() : null;
            }

            return response()->json([
                'status' => 'success',
                'data' => [
                    'creativeId' => $creativeId,
                    'isFavorite' => $isFavorite,
                    'totalFavorites' => $user->getFavoritesCount(),
                    'addedAt' => $addedAt,
                    'checkedAt' => now()->toISOString()
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => __('creatives.api.errors.failed_to_check_favorite_status') . ': ' . $e->getMessage()
            ], 500);
        }
    }

    // ============================================================================
    // МЕТОДЫ ДЛЯ РАБОТЫ С ПРЕСЕТАМИ ФИЛЬТРОВ
    // ============================================================================

    /**
     * Получить все пресеты фильтров для текущего пользователя
     * 
     * @OA\Get(
     *     path="/api/creatives/filter-presets",
     *     operationId="getFilterPresets",
     *     tags={"Креативы - Пресеты фильтров"},
     *     summary="Получить список пресетов фильтров",
     *     description="Возвращает все сохраненные пресеты фильтров для аутентифицированного пользователя",
     *     security={{"bearerAuth": {}}},
     *     @OA\Response(
     *         response=200,
     *         description="Пресеты успешно получены",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(property="data", type="array", @OA\Items(
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="name", type="string", example="Facebook USA"),
     *                 @OA\Property(property="filters", type="object"),
     *                 @OA\Property(property="has_active_filters", type="boolean", example=true),
     *                 @OA\Property(property="active_filters_count", type="integer", example=3),
     *                 @OA\Property(property="created_at", type="string", format="date-time"),
     *                 @OA\Property(property="updated_at", type="string", format="date-time")
     *             ))
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Пользователь не аутентифицирован"
     *     )
     * )
     */
    public function getFilterPresets(Request $request)
    {
        try {
            $user = $request->user();

            if (!$user) {
                return response()->json([
                    'status' => 'error',
                    'message' => __('creatives.api.errors.user_not_authenticated')
                ], 401);
            }

            $presets = FilterPreset::forUser($user->id)
                ->orderBy('name')
                ->get()
                ->map(function ($preset) {
                    return $preset->toApiArray();
                });

            return response()->json([
                'status' => 'success',
                'data' => $presets,
                'meta' => [
                    'total' => $presets->count(),
                    'timestamp' => now()->toISOString()
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => __('creatives.api.errors.failed_to_get_filter_presets') . ': ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Создать новый пресет фильтров
     * 
     * @OA\Post(
     *     path="/api/creatives/filter-presets",
     *     operationId="createFilterPreset",
     *     tags={"Креативы - Пресеты фильтров"},
     *     summary="Создать пресет фильтров",
     *     description="Создает новый пресет фильтров с текущими настройками",
     *     security={{"bearerAuth": {}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="name", type="string", example="My Facebook Preset", maxLength=255),
     *             @OA\Property(property="filters", type="object",
     *                 @OA\Property(property="searchKeyword", type="string"),
     *                 @OA\Property(property="countries", type="array", @OA\Items(type="string")),
     *                 @OA\Property(property="advertisingNetworks", type="array", @OA\Items(type="string")),
     *                 @OA\Property(property="activeTab", type="string")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Пресет успешно создан",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="name", type="string", example="My Facebook Preset"),
     *                 @OA\Property(property="filters", type="object")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Ошибка валидации"
     *     )
     * )
     */
    public function createFilterPreset(Request $request)
    {
        try {
            $user = $request->user();

            if (!$user) {
                return response()->json([
                    'status' => 'error',
                    'message' => __('creatives.api.errors.user_not_authenticated')
                ], 401);
            }

            // Валидация входных данных
            $validated = $request->validate([
                'name' => [
                    'required',
                    'string',
                    'max:255',
                    Rule::unique('filter_presets', 'name')->where('user_id', $user->id)
                ],
                'filters' => 'required|array'
            ]);

            // Создаем пресет
            $preset = FilterPreset::createPreset(
                $user->id,
                $validated['name'],
                $validated['filters']
            );

            return response()->json([
                'status' => 'success',
                'data' => $preset->toApiArray(),
                'message' => __('creatives.api.success.filter_preset_created')
            ], 201);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'status' => 'error',
                'message' => __('creatives.api.errors.validation_failed'),
                'errors' => $e->errors()
            ], 422);
        } catch (\InvalidArgumentException $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => __('creatives.api.errors.failed_to_create_filter_preset') . ': ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Получить конкретный пресет фильтров
     * 
     * @OA\Get(
     *     path="/api/creatives/filter-presets/{id}",
     *     operationId="getFilterPreset",
     *     tags={"Креативы - Пресеты фильтров"},
     *     summary="Получить пресет фильтров",
     *     description="Возвращает конкретный пресет фильтров по ID",
     *     security={{"bearerAuth": {}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="ID пресета",
     *         required=true,
     *         @OA\Schema(type="integer", minimum=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Пресет успешно получен"
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Пресет не найден"
     *     )
     * )
     */
    public function getFilterPreset(Request $request, $id)
    {
        try {
            $user = $request->user();

            if (!$user) {
                return response()->json([
                    'status' => 'error',
                    'message' => __('creatives.api.errors.user_not_authenticated')
                ], 401);
            }

            $preset = FilterPreset::forUser($user->id)->findOrFail($id);

            return response()->json([
                'status' => 'success',
                'data' => $preset->toApiArray()
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'status' => 'error',
                'message' => __('creatives.api.errors.filter_preset_not_found')
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => __('creatives.api.errors.failed_to_get_filter_preset') . ': ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Обновить пресет фильтров
     * 
     * @OA\Put(
     *     path="/api/creatives/filter-presets/{id}",
     *     operationId="updateFilterPreset",
     *     tags={"Креативы - Пресеты фильтров"},
     *     summary="Обновить пресет фильтров",
     *     description="Обновляет существующий пресет фильтров",
     *     security={{"bearerAuth": {}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="ID пресета",
     *         required=true,
     *         @OA\Schema(type="integer", minimum=1)
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="name", type="string", example="Updated Preset Name"),
     *             @OA\Property(property="filters", type="object")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Пресет успешно обновлен"
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Пресет не найден"
     *     )
     * )
     */
    public function updateFilterPreset(Request $request, $id)
    {
        try {
            $user = $request->user();

            if (!$user) {
                return response()->json([
                    'status' => 'error',
                    'message' => __('creatives.api.errors.user_not_authenticated')
                ], 401);
            }

            $preset = FilterPreset::forUser($user->id)->findOrFail($id);

            // Валидация входных данных
            $validated = $request->validate([
                'name' => [
                    'required',
                    'string',
                    'max:255',
                    Rule::unique('filter_presets', 'name')
                        ->where('user_id', $user->id)
                        ->ignore($preset->id)
                ],
                'filters' => 'required|array'
            ]);

            // Обновляем пресет
            $preset->updatePreset(
                $validated['name'],
                $validated['filters']
            );

            return response()->json([
                'status' => 'success',
                'data' => $preset->fresh()->toApiArray(),
                'message' => __('creatives.api.success.filter_preset_updated')
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'status' => 'error',
                'message' => __('creatives.api.errors.filter_preset_not_found')
            ], 404);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'status' => 'error',
                'message' => __('creatives.api.errors.validation_failed'),
                'errors' => $e->errors()
            ], 422);
        } catch (\InvalidArgumentException $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => __('creatives.api.errors.failed_to_update_filter_preset') . ': ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Удалить пресет фильтров
     * 
     * @OA\Delete(
     *     path="/api/creatives/filter-presets/{id}",
     *     operationId="deleteFilterPreset",
     *     tags={"Креативы - Пресеты фильтров"},
     *     summary="Удалить пресет фильтров",
     *     description="Удаляет существующий пресет фильтров",
     *     security={{"bearerAuth": {}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="ID пресета",
     *         required=true,
     *         @OA\Schema(type="integer", minimum=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Пресет успешно удален"
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Пресет не найден"
     *     )
     * )
     */
    public function deleteFilterPreset(Request $request, $id)
    {
        try {
            $user = $request->user();

            if (!$user) {
                return response()->json([
                    'status' => 'error',
                    'message' => __('creatives.api.errors.user_not_authenticated')
                ], 401);
            }

            $preset = FilterPreset::forUser($user->id)->findOrFail($id);
            $presetName = $preset->name;

            $preset->delete();

            return response()->json([
                'status' => 'success',
                'message' => __('creatives.api.success.filter_preset_deleted', ['name' => $presetName]),
                'data' => [
                    'deleted_id' => (int)$id,
                    'deleted_name' => $presetName,
                    'deleted_at' => now()->toISOString()
                ]
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'status' => 'error',
                'message' => __('creatives.api.errors.filter_preset_not_found')
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => __('creatives.api.errors.failed_to_delete_filter_preset') . ': ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Получить похожие креативы на основе текущего креатива
     * Учитывает тарифные ограничения пользователя
     */
    protected function getSimilarCreatives(Creative $creative, ?int $userId = null, int $limit = 6): array
    {
        // Проверяем права доступа к похожим креативам
        if ($userId) {
            $user = \App\Models\User::find($userId);
            if (!$user || !$user->canViewSimilarCreatives()) {
                return []; // Возвращаем пустой массив если нет доступа
            }
        }

        // Базовый запрос для поиска похожих креативов
        $query = Creative::where('id', '!=', $creative->id)
            ->onlyReady(); // Фильтруем только обработанные и валидные креативы

        // Приоритет 1: Тот же формат
        if ($creative->format) {
            $query->where('format', $creative->format);
        }

        // Приоритет 2: Та же страна
        if ($creative->country_id) {
            $query->where('country_id', $creative->country_id);
        }

        // Приоритет 3: Та же рекламная сеть  
        if ($creative->advertisment_network_id) {
            $query->where('advertisment_network_id', $creative->advertisment_network_id);
        }

        // Приоритет 4: Тот же язык
        if ($creative->language_id) {
            $query->where('language_id', $creative->language_id);
        }

        // Исключаем adult контент если исходный креатив не adult
        if (!$creative->is_adult) {
            $query->where('is_adult', false);
        }

        // Предзагружаем связанные данные
        $query->with([
            'country',
            'language',
            'browser',
            'advertismentNetwork'
        ]);

        // Сортируем по актуальности: сначала активные, потом по дате
        $query->orderByRaw("CASE WHEN status = 'active' THEN 1 ELSE 0 END DESC")
            ->orderByDesc('last_seen_at')
            ->orderByDesc('external_created_at');

        // Получаем результаты с лимитом
        $similarCreatives = $query->limit($limit)->get();

        // Если не хватает результатов, расширяем поиск
        if ($similarCreatives->count() < $limit) {
            $remainingLimit = $limit - $similarCreatives->count();
            $excludeIds = $similarCreatives->pluck('id')->toArray();
            $excludeIds[] = $creative->id;

            // Более широкий поиск: только формат + активные
            $additionalQuery = Creative::whereNotIn('id', $excludeIds)
                ->onlyReady(); // Фильтруем только обработанные и валидные креативы

            if ($creative->format) {
                $additionalQuery->where('format', $creative->format);
            }

            // if (!$creative->is_adult) {
            //     $additionalQuery->where('is_adult', false);
            // }

            $additionalCreatives = $additionalQuery
                ->with(['country', 'language', 'browser', 'advertismentNetwork'])
                ->orderByRaw("CASE WHEN status = 'active' THEN 1 ELSE 0 END DESC")
                ->orderByDesc('social_likes')
                ->orderByDesc('last_seen_at')
                ->limit($remainingLimit)
                ->get();

            $similarCreatives = $similarCreatives->merge($additionalCreatives);
        }

        // Конвертируем в массив для API
        return $similarCreatives->map(function ($item) use ($userId) {
            $creativeData = $item->toCreativeArray();

            // Добавляем информацию о статусе избранного для аутентифицированных пользователей
            if ($userId) {
                $creativeData['is_favorite'] = $this->checkIsFavoriteById($item->id, $userId);
            } else {
                $creativeData['is_favorite'] = false;
            }

            return $creativeData;
        })->toArray();
    }

    /**
     * Получить похожие креативы с поддержкой пагинации
     * Возвращает массив с элементами и метаинформацией о пагинации
     */
    protected function getSimilarCreativesWithPagination(Creative $creative, ?int $userId = null, int $limit = 6, int $offset = 0): array
    {
        // Проверяем права доступа к похожим креативам
        if ($userId) {
            $user = \App\Models\User::find($userId);
            if (!$user || !$user->canViewSimilarCreatives()) {
                return [
                    'items' => [],
                    'total' => 0,
                    'hasMore' => false
                ];
            }
        }

        // Строим базовый запрос для подсчета общего количества
        $baseQuery = Creative::where('id', '!=', $creative->id)
            ->onlyReady(); // Фильтруем только обработанные и валидные креативы

        // Применяем те же фильтры что и в основном методе
        if ($creative->format) {
            $baseQuery->where('format', $creative->format);
        }

        if ($creative->country_id) {
            $baseQuery->where('country_id', $creative->country_id);
        }

        if ($creative->advertisment_network_id) {
            $baseQuery->where('advertisment_network_id', $creative->advertisment_network_id);
        }

        if ($creative->language_id) {
            $baseQuery->where('language_id', $creative->language_id);
        }

        if (!$creative->is_adult) {
            $baseQuery->where('is_adult', false);
        }

        // Получаем общее количество для пагинации
        $totalCount = $baseQuery->count();

        // Строим запрос для получения конкретной страницы
        $query = clone $baseQuery;

        // Предзагружаем связанные данные
        $query->with([
            'country',
            'language',
            'browser',
            'advertismentNetwork'
        ]);

        // Сортируем по актуальности
        $query->orderByRaw("CASE WHEN status = 'active' THEN 1 ELSE 0 END DESC")
            ->orderByDesc('last_seen_at')
            ->orderByDesc('external_created_at');

        // Применяем пагинацию
        $similarCreatives = $query->offset($offset)->limit($limit)->get();

        // Если не хватает результатов и это первая страница, расширяем поиск
        if ($similarCreatives->count() < $limit && $offset === 0) {
            $remainingLimit = $limit - $similarCreatives->count();
            $excludeIds = $similarCreatives->pluck('id')->toArray();
            $excludeIds[] = $creative->id;

            // Более широкий поиск
            $additionalQuery = Creative::whereNotIn('id', $excludeIds)
                ->onlyReady(); // Фильтруем только обработанные и валидные креативы

            if ($creative->format) {
                $additionalQuery->where('format', $creative->format);
            }

            $additionalCreatives = $additionalQuery
                ->with(['country', 'language', 'browser', 'advertismentNetwork'])
                ->orderByRaw("CASE WHEN status = 'active' THEN 1 ELSE 0 END DESC")
                ->orderByDesc('social_likes')
                ->orderByDesc('last_seen_at')
                ->limit($remainingLimit)
                ->get();

            $similarCreatives = $similarCreatives->merge($additionalCreatives);

            // Обновляем общее количество с учетом расширенного поиска
            $additionalCount = Creative::whereNotIn('id', [$creative->id])
                ->onlyReady() // Фильтруем только обработанные и валидные креативы
                ->when($creative->format, fn($q) => $q->where('format', $creative->format))
                ->count();
            $totalCount = max($totalCount, $additionalCount);
        }

        // Конвертируем в массив для API
        $items = $similarCreatives->map(function ($item) use ($userId) {
            $creativeData = $item->toCreativeArray();

            // Добавляем информацию о статусе избранного для аутентифицированных пользователей
            if ($userId) {
                $creativeData['is_favorite'] = $this->checkIsFavoriteById($item->id, $userId);
            } else {
                $creativeData['is_favorite'] = false;
            }

            return $creativeData;
        })->toArray();

        // Определяем есть ли еще данные
        $hasMore = ($offset + count($items)) < $totalCount;

        return [
            'items' => $items,
            'total' => $totalCount,
            'hasMore' => $hasMore,
            'offset' => $offset,
            'limit' => $limit,
            'currentCount' => count($items)
        ];
    }

    /**
     * Helper method для проверки избранного по ID пользователя
     */
    protected function checkIsFavoriteById(int $creativeId, int $userId): bool
    {
        $user = \App\Models\User::find($userId);

        if (!$user) {
            return false;
        }

        return $user->hasFavoriteCreative($creativeId);
    }
}

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
use App\Enums\Frontend\DeviceType;
use App\Enums\Frontend\OperationSystem;
use App\Enums\Frontend\AdvertisingFormat;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

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
        try {
            // Создаем DTO для фильтров с автоматической валидацией и санитизацией
            $filtersDTO = CreativesFiltersDTO::fromRequest($request);

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
            $filtersDTO = CreativesFiltersDTO::fromRequest($request);

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
                'message' => 'Failed to load filter options: ' . $e->getMessage(),
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
                'message' => 'Validation failed: ' . $e->getMessage(),
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
                    $filtersDTO->country
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
            $counts = Creative::join('advertisment_networks', 'creatives.advertisment_network_id', '=', 'advertisment_networks.id')
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

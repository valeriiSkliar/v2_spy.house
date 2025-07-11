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

        // Получаем количество избранных креативов для текущего пользователя
        $favoritesCount = 0;
        $userData = [];
        if ($request->user()) {
            $user = $request->user();
            $favoritesCount = $user->getFavoritesCount();

            // Формируем данные пользователя для передачи в Vue Store
            $userData = [
                'id' => $user->id,
                'email' => $user->email,
                'tariff' => $user->currentTariff(),
                'is_trial' => $user->is_trial,
                'show_similar_creatives' => $user->canViewSimilarCreatives(),
                'favoritesCount' => $favoritesCount,
                'isAuthenticated' => true,
            ];
        } else {
            $userData = [
                'id' => null,
                'email' => null,
                'tariff' => null,
                'is_trial' => false,
                'show_similar_creatives' => false,
                'favoritesCount' => 0,
                'isAuthenticated' => false,
            ];
        }

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
            'favoritesCount' => $favoritesCount,
            'userData' => $userData,
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

    /**
     * Получить детали конкретного креатива
     * 
     * @OA\Get(
     *     path="/api/creatives/{id}/details",
     *     operationId="getCreativeDetails",
     *     tags={"Креативы - Детали"},
     *     summary="Получить детали креатива",
     *     description="Возвращает подробную информацию о конкретном креативе включая все метаданные, статистику и связанные данные",
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="ID креатива",
     *         required=true,
     *         @OA\Schema(type="integer", minimum=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Детали креатива успешно получены",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="id", type="integer", example=21717),
     *                 @OA\Property(property="title", type="string", example="Creative Title"),
     *                 @OA\Property(property="description", type="string", example="Creative description"),
     *                 @OA\Property(property="country", type="string", example="US"),
     *                 @OA\Property(property="language", type="string", example="en"),
     *                 @OA\Property(property="format", type="string", example="push"),
     *                 @OA\Property(property="status", type="string", example="active"),
     *                 @OA\Property(property="is_adult", type="boolean", example=false),
     *                 @OA\Property(property="has_video", type="boolean", example=true),
     *                 @OA\Property(property="video_url", type="string", example="https://example.com/video.mp4"),
     *                 @OA\Property(property="main_image_url", type="string", example="https://example.com/image.jpg"),
     *                 @OA\Property(property="icon_url", type="string", example="https://example.com/icon.png"),
     *                 @OA\Property(property="landing_url", type="string", example="https://example.com/landing"),
     *                 @OA\Property(property="social_likes", type="integer", example=1500),
     *                 @OA\Property(property="social_comments", type="integer", example=250),
     *                 @OA\Property(property="social_shares", type="integer", example=100),
     *                 @OA\Property(property="created_at", type="string", example="2024-01-15"),
     *                 @OA\Property(property="last_seen_at", type="string", example="2024-01-20"),
     *                 @OA\Property(property="advertising_network", type="object",
     *                     @OA\Property(property="name", type="string", example="facebook"),
     *                     @OA\Property(property="display_name", type="string", example="Facebook"),
     *                     @OA\Property(property="logo", type="string", example="facebook.png")
     *                 ),
     *                 @OA\Property(property="browser", type="object",
     *                     @OA\Property(property="name", type="string", example="chrome"),
     *                     @OA\Property(property="type", type="string", example="desktop")
     *                 ),
     *                 @OA\Property(property="is_favorite", type="boolean", example=false)
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
     *         response=500,
     *         description="Ошибка сервера",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="error"),
     *             @OA\Property(property="message", type="string", example="Error loading creative details")
     *         )
     *     )
     * )
     */
    public function getCreativeDetails($id)
    {
        try {
            // Находим креатив с предзагрузкой связанных данных
            $creative = Creative::with([
                'country',
                'language',
                'browser',
                'advertismentNetwork',
                'source'
            ])->find($id);

            if (!$creative) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Creative not found',
                    'data' => null
                ], 404);
            }

            // Подготавливаем детализированные данные креатива
            $creativeDetails = [
                'id' => $creative->id,
                'title' => $creative->title,
                'description' => $creative->description,
                'format' => $creative->format?->value ?? 'unknown',
                // 'is_adult' => $creative->is_adult,
                'has_video' => $creative->has_video,
                'video_url' => $creative->video_url,
                'video_duration' => $creative->video_duration,
                'main_image_url' => $creative->main_image_url,
                'main_image_size' => $creative->main_image_size,
                'icon_url' => $creative->icon_url,
                'icon_size' => $creative->icon_size,
                'landing_url' => $creative->landing_url,
                // 'external_id' => $creative->external_id,

                // Социальная статистика
                'social_likes' => $creative->social_likes ?? 0,
                'social_comments' => $creative->social_comments ?? 0,
                'social_shares' => $creative->social_shares ?? 0,

                // Даты
                'created_at' => $creative->external_created_at?->format('Y-m-d'),
                'last_seen_at' => $creative->last_seen_at?->format('Y-m-d'),
                // 'external_created_at' => $creative->external_created_at?->format('Y-m-d H:i:s'),
                'start_date' => $creative->start_date?->format('Y-m-d'),
                'end_date' => $creative->end_date?->format('Y-m-d'),

                // Метаданные обработки
                // 'is_processed' => $creative->is_processed,
                // 'processed_at' => $creative->processed_at?->format('Y-m-d H:i:s'),
                // 'is_valid' => $creative->is_valid,
                // 'validation_error' => $creative->validation_error,
                // 'processing_error' => $creative->processing_error,

                // Связанные сущности
                'country' => $creative->country ? [
                    'code' => $creative->country->iso_code_2,
                    'name' => $creative->country->name,
                    'iso_code_3' => $creative->country->iso_code_3,
                ] : null,

                'language' => $creative->language ? [
                    'code' => $creative->language->iso_code_2,
                    'name' => $creative->language->name,
                    'iso_code_3' => $creative->language->iso_code_3,
                ] : null,

                'advertising_network' => $creative->advertismentNetwork ? [
                    'name' => $creative->advertismentNetwork->network_name,
                    'display_name' => $creative->advertismentNetwork->network_display_name,
                    'logo' => $creative->advertismentNetwork->network_logo,
                    'description' => $creative->advertismentNetwork->description,
                    'traffic_type' => $creative->advertismentNetwork->traffic_type_description,
                    'is_adult' => $creative->advertismentNetwork->is_adult,
                ] : null,

                'browser' => $creative->browser ? [
                    'name' => $creative->browser->browser,
                    'type' => $creative->browser->browser_type?->value,
                    'device_type' => $creative->browser->device_type?->value,
                    'version' => $creative->browser->browser_version,
                    'platform' => $creative->browser->platform,
                    'is_mobile' => $creative->browser->ismobiledevice,
                    'is_tablet' => $creative->browser->istablet,
                ] : null,

                // 'source' => $creative->source ? [
                //     'name' => $creative->source->source_name,
                //     'display_name' => $creative->source->source_display_name,
                // ] : null,

                'platform' => $creative->platform?->value,
                'operation_system' => $creative->operation_system?->value,

                // Проверка избранного для аутентифицированного пользователя
                'is_favorite' => $this->checkIsFavorite($creative->id, request()),
                'is_active' => $creative->is_active, // Теперь работает через accessor

                // Дополнительные вычисляемые поля
                'file_sizes_detailed' => $creative->calculateFileSize(),
                'devices' => $creative->guessDevices(),
                'combined_hash' => $creative->combined_hash,

                // Метаданные ответа
                'loadedAt' => now()->toISOString(),
                'cacheKey' => "creative_details_{$id}",
            ];

            return response()->json([
                'status' => 'success',
                'data' => $creativeDetails,
                'meta' => [
                    'timestamp' => now()->toISOString(),
                    'version' => '1.0.0',
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'An error occurred while loading creative details: ' . $e->getMessage(),
                'data' => null,
                'debug' => [
                    'creative_id' => $id,
                    'error_class' => get_class($e),
                    'error_line' => $e->getLine(),
                    'error_file' => basename($e->getFile()),
                ]
            ], 500);
        }
    }

    /**
     * Получить данные текущего пользователя для Vue Store
     * 
     * @OA\Get(
     *     path="/api/creatives/user",
     *     operationId="getCurrentUser",
     *     tags={"Креативы - Пользователь"},
     *     summary="Получить данные текущего пользователя",
     *     description="Возвращает информацию о текущем пользователе включая ID, email, тариф и количество избранных",
     *     @OA\Response(
     *         response=200,
     *         description="Данные пользователя успешно получены",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="id", type="integer", example=123),
     *                 @OA\Property(property="email", type="string", example="user@example.com"),
     *                 @OA\Property(property="isAuthenticated", type="boolean", example=true),
     *                 @OA\Property(property="favoritesCount", type="integer", example=15),
     *                 @OA\Property(property="tariff", type="object",
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="name", type="string", example="Premium"),
     *                     @OA\Property(property="css_class", type="string", example="premium"),
     *                     @OA\Property(property="expires_at", type="string", example="2024-12-31"),
     *                     @OA\Property(property="status", type="string", example="Активная"),
     *                     @OA\Property(property="is_active", type="boolean", example=true),
     *                     @OA\Property(property="is_trial", type="boolean", example=false),
     *                     @OA\Property(property="show_similar_creatives", type="boolean", example=false)
     *                 ),
     *                 @OA\Property(property="show_similar_creatives", type="boolean", example=false)
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Пользователь не аутентифицирован",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="id", type="null", example=null),
     *                 @OA\Property(property="email", type="null", example=null),
     *                 @OA\Property(property="isAuthenticated", type="boolean", example=false),
     *                 @OA\Property(property="favoritesCount", type="integer", example=0),
     *                 @OA\Property(property="tariff", type="null", example=null),
     *                 @OA\Property(property="show_similar_creatives", type="boolean", example=false)
     *             )
     *         )
     *     )
     * )
     */
    public function getCurrentUser()
    {
        try {
            $user = request()->user();

            if ($user) {
                $userData = [
                    'id' => $user->id,
                    'email' => $user->email,
                    'tariff' => $user->currentTariff(),
                    'is_trial' => $user->is_trial,
                    'show_similar_creatives' => $user->canViewSimilarCreatives(),
                    'favoritesCount' => $user->getFavoritesCount(),
                    'isAuthenticated' => true,
                ];
            } else {
                $userData = [
                    'id' => null,
                    'email' => null,
                    'tariff' => null,
                    'is_trial' => false,
                    'show_similar_creatives' => false,
                    'favoritesCount' => 0,
                    'isAuthenticated' => false,
                ];
            }

            return response()->json([
                'status' => 'success',
                'data' => $userData,
                'meta' => [
                    'timestamp' => now()->toISOString(),
                    'version' => '1.0.0',
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'An error occurred while loading user data: ' . $e->getMessage(),
                'data' => [
                    'id' => null,
                    'email' => null,
                    'tariff' => null,
                    'is_trial' => false,
                    'show_similar_creatives' => false,
                    'favoritesCount' => 0,
                    'isAuthenticated' => false,
                ],
                'debug' => [
                    'error_class' => get_class($e),
                    'error_line' => $e->getLine(),
                    'error_file' => basename($e->getFile()),
                ]
            ], 500);
        }
    }

    /**
     * Получить похожие креативы для конкретного креатива
     * 
     * @OA\Get(
     *     path="/api/creatives/{id}/similar",
     *     operationId="getSimilarCreatives",
     *     tags={"Креативы - Похожие"},
     *     summary="Получить похожие креативы",
     *     description="Возвращает список креативов, похожих на указанный. Алгоритм ищет креативы с совпадающими характеристиками: формат, страна, рекламная сеть, язык. Доступ ограничен тарифным планом пользователя.",
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="ID креатива для поиска похожих",
     *         required=true,
     *         @OA\Schema(type="integer", minimum=1)
     *     ),
     *     @OA\Parameter(
     *         name="limit",
     *         in="query",
     *         description="Максимальное количество похожих креативов",
     *         required=false,
     *         @OA\Schema(type="integer", minimum=1, maximum=20, default=6)
     *     ),
     *     @OA\Parameter(
     *         name="offset",
     *         in="query",
     *         description="Смещение для пагинации",
     *         required=false,
     *         @OA\Schema(type="integer", minimum=0, default=0)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Похожие креативы успешно получены",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="similar_creatives", type="array", @OA\Items(
     *                     @OA\Property(property="id", type="integer", example=21718),
     *                     @OA\Property(property="title", type="string", example="Similar Creative Title"),
     *                     @OA\Property(property="description", type="string", example="Similar creative description"),
     *                     @OA\Property(property="format", type="string", example="push"),
     *                     @OA\Property(property="country", type="object",
     *                         @OA\Property(property="code", type="string", example="US"),
     *                         @OA\Property(property="name", type="string", example="United States")
     *                     ),
     *                     @OA\Property(property="advertising_networks", type="array", @OA\Items(type="string")),
     *                     @OA\Property(property="icon_url", type="string", example="https://example.com/icon.png"),
     *                     @OA\Property(property="main_image_url", type="string", example="https://example.com/image.jpg"),
     *                     @OA\Property(property="landing_url", type="string", example="https://example.com/landing"),
     *                     @OA\Property(property="is_favorite", type="boolean", example=false),
     *                     @OA\Property(property="is_active", type="boolean", example=true),
     *                     @OA\Property(property="social_likes", type="integer", example=1200),
     *                     @OA\Property(property="created_at", type="string", example="2024-01-15")
     *                 )),
     *                 @OA\Property(property="original_creative", type="object",
     *                     @OA\Property(property="id", type="integer", example=21717),
     *                     @OA\Property(property="title", type="string", example="Original Creative"),
     *                     @OA\Property(property="format", type="string", example="push")
     *                 ),
     *                 @OA\Property(property="search_criteria", type="object",
     *                     @OA\Property(property="format", type="string", example="push"),
     *                     @OA\Property(property="country", type="string", example="US"),
     *                     @OA\Property(property="advertising_network", type="string", example="facebook"),
     *                     @OA\Property(property="language", type="string", example="en")
     *                 ),
     *                 @OA\Property(property="count", type="integer", example=6),
     *                 @OA\Property(property="has_access", type="boolean", example=true)
     *             ),
     *             @OA\Property(property="meta", type="object",
     *                 @OA\Property(property="total", type="integer", example=25),
     *                 @OA\Property(property="offset", type="integer", example=0),
     *                 @OA\Property(property="limit", type="integer", example=6),
     *                 @OA\Property(property="hasMore", type="boolean", example=true),
     *                 @OA\Property(property="timestamp", type="string", format="date-time"),
     *                 @OA\Property(property="algorithm_version", type="string", example="1.0.0"),
     *                 @OA\Property(property="cache_key", type="string")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Доступ к похожим креативам ограничен тарифным планом",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="error"),
     *             @OA\Property(property="message", type="string", example="Similar creatives are available only for Premium users"),
     *             @OA\Property(property="code", type="string", example="PREMIUM_FEATURE_REQUIRED"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="has_access", type="boolean", example=false),
     *                 @OA\Property(property="required_plan", type="string", example="Premium"),
     *                 @OA\Property(property="current_plan", type="string", example="Basic"),
     *                 @OA\Property(property="upgrade_url", type="string", example="/tariffs")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Исходный креатив не найден",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="error"),
     *             @OA\Property(property="message", type="string", example="Creative not found")
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Ошибка сервера",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="error"),
     *             @OA\Property(property="message", type="string", example="Error loading similar creatives")
     *         )
     *     )
     * )
     */
    public function getSimilarCreativesApi($id)
    {
        try {
            // Валидируем и получаем параметры
            $limit = (int)request()->get('limit', 6);
            $offset = (int)request()->get('offset', 0);
            $limit = max(1, min(20, $limit)); // Ограничиваем от 1 до 20
            $offset = max(0, $offset); // Offset не может быть отрицательным

            // Находим исходный креатив
            $creative = Creative::with([
                'country',
                'language',
                'browser',
                'advertismentNetwork'
            ])->find($id);

            if (!$creative) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Creative not found',
                    'data' => null
                ], 404);
            }

            // Получаем текущего пользователя
            $user = request()->user();
            $userId = $user ? $user->id : null;

            // Проверяем доступ к похожим креативам (только для аутентифицированных пользователей)
            if ($user && !$user->canViewSimilarCreatives()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Similar creatives are available only for Premium users',
                    'code' => 'PREMIUM_FEATURE_REQUIRED',
                    'data' => [
                        'has_access' => false,
                        'required_plan' => 'Premium',
                        'current_plan' => $user->currentTariff()['name'] ?? 'Basic',
                        'upgrade_url' => '/tariffs',
                        'original_creative_id' => $creative->id,
                        'similar_creatives' => []
                    ]
                ], 403);
            }

            // Получаем похожие креативы с пагинацией через базовый контроллер
            $similarData = $this->getSimilarCreativesWithPagination($creative, $userId, $limit, $offset);

            // Формируем критерии поиска для отладки
            $searchCriteria = [
                'format' => $creative->format?->value,
                'country' => $creative->country?->iso_code_2,
                'advertising_network' => $creative->advertismentNetwork?->network_name,
                'language' => $creative->language?->iso_code_2,
            ];

            // Минимальная информация об исходном креативе
            $originalCreative = [
                'id' => $creative->id,
                'title' => $creative->title,
                'format' => $creative->format?->value,
                'country' => $creative->country ? [
                    'code' => $creative->country->iso_code_2,
                    'name' => $creative->country->name
                ] : null,
                'advertising_network' => $creative->advertismentNetwork?->network_name
            ];

            return response()->json([
                'status' => 'success',
                'data' => [
                    'similar_creatives' => $similarData['items'],
                    'original_creative' => $originalCreative,
                    'search_criteria' => $searchCriteria,
                    'count' => count($similarData['items']),
                    'has_access' => $user ? $user->canViewSimilarCreatives() : false,
                    'requested_limit' => $limit,
                    'requested_offset' => $offset,
                ],
                'meta' => [
                    'total' => $similarData['total'],
                    'offset' => $offset,
                    'limit' => $limit,
                    'hasMore' => $similarData['hasMore'],
                    'timestamp' => now()->toISOString(),
                    'algorithm_version' => '1.0.0',
                    'cache_key' => "similar_creatives_{$id}_{$limit}_{$offset}",
                    'user_authenticated' => $user !== null,
                    'user_id' => $userId,
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'An error occurred while loading similar creatives: ' . $e->getMessage(),
                'data' => [
                    'similar_creatives' => [],
                    'has_access' => false,
                    'count' => 0
                ],
                'debug' => [
                    'creative_id' => $id,
                    'error_class' => get_class($e),
                    'error_line' => $e->getLine(),
                    'error_file' => basename($e->getFile()),
                ]
            ], 500);
        }
    }
}

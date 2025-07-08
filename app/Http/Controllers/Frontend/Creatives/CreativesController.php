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
                'created_at' => $creative->created_at?->format('Y-m-d'),
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

                // TODO: Реализовать проверку избранного для пользователя
                'is_favorite' => false, // Пока заглушка
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
}

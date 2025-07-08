<?php

namespace App\Http\DTOs;

use Carbon\Carbon;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Contracts\Support\Jsonable;

/**
 * DTO для креативов на основе TypeScript интерфейса Creative
 * Обеспечивает type safety между frontend и backend
 */
class CreativeDTO implements Arrayable, Jsonable
{
    public function __construct(
        // Основные поля (required)
        public int $id,
        public string $title,
        public string $description,
        public string $category,
        public array|null $country,
        public string $icon_url,
        public string $landing_url,
        public string $created_at,

        // Опциональные поля
        public ?string $video_url = null,
        public ?bool $has_video = false,
        public ?string $activity_date = null,
        public ?array $advertising_networks = null,
        public ?array $languages = null,
        public ?array $operating_systems = null,
        public ?array $browsers = null,
        public ?array $devices = null,
        public ?string $main_image_url = null,
        public ?bool $is_adult = false,

        // Социальные поля (приводим к int для консистентности)
        public ?int $social_likes = null,
        public ?int $social_comments = null,
        public ?int $social_shares = null,
        public ?string $duration = null,

        // Computed свойства (добавляются на backend для frontend)
        public ?string $displayName = null,
        public ?bool $isRecent = null,
        public ?bool $isFavorite = null,
        public ?string $created_at_formatted = null,
        public ?string $last_activity_date_formatted = null,
        public ?bool $is_active = null,
        public ?string $platform = null,
        public ?string $activity_title = null,
        public ?array $file_sizes_detailed = null,
    ) {}

    /**
     * Создать DTO из массива данных (например, из модели или API)
     */
    public static function fromArray(array $data): self
    {
        // Обработка поля country для обратной совместимости
        $country = $data['country'] ?? null;
        if (is_string($country)) {
            // Если передана строка (старый формат), преобразуем в массив
            $country = [
                'code' => $country,
                'name' => $country,
                'iso_code_3' => null
            ];
        }

        return new self(
            id: $data['id'],
            title: $data['title'],
            description: $data['description'],
            category: $data['category'],
            country: $country,
            icon_url: $data['icon_url'],
            landing_url: $data['landing_url'],
            created_at: $data['created_at'],
            video_url: $data['video_url'] ?? null,
            has_video: $data['has_video'] ?? false,
            activity_date: $data['activity_date'] ?? null,
            advertising_networks: $data['advertising_networks'] ?? null,
            languages: $data['languages'] ?? null,
            operating_systems: $data['operating_systems'] ?? null,
            browsers: $data['browsers'] ?? null,
            devices: $data['devices'] ?? null,
            main_image_url: $data['main_image_url'] ?? null,
            is_adult: $data['is_adult'] ?? false,
            social_likes: isset($data['social_likes']) ? (int)$data['social_likes'] : null,
            social_comments: isset($data['social_comments']) ? (int)$data['social_comments'] : null,
            social_shares: isset($data['social_shares']) ? (int)$data['social_shares'] : null,
            duration: $data['duration'] ?? null,
            displayName: $data['displayName'] ?? null,
            isRecent: $data['isRecent'] ?? null,
            isFavorite: $data['isFavorite'] ?? null,
            created_at_formatted: $data['created_at_formatted'] ?? null,
            last_activity_date_formatted: $data['last_activity_date_formatted'] ?? null,
            is_active: $data['is_active'] ?? null,
            platform: $data['platform'] ?? null,
            activity_title: $data['activity_title'] ?? null,
            file_sizes_detailed: $data['file_sizes_detailed'] ?? null,
        );
    }

    /**
     * Создать DTO с автоматическим вычислением computed свойств
     */
    public static function fromArrayWithComputed(array $data, ?int $userId = null): self
    {
        $dto = self::fromArray($data);
        $dto->computeProperties($userId);
        return $dto;
    }

    /**
     * Вычислить computed свойства с кешированием
     * 
     * Примечание: is_active определяется исключительно моделью Creative через getIsActiveAttribute()
     * на основе статуса креатива и не вычисляется по дате активности в DTO
     */
    public function computeProperties(?int $userId = null): void
    {
        // displayName - комбинация name и title
        $this->displayName = trim($this->title);

        // Кешируем парсинг дат
        $createdAtCarbon = Carbon::parse($this->created_at);
        $now = now();

        // isRecent - создан за последние 7 дней
        $this->isRecent = $createdAtCarbon->isAfter($now->copy()->subDays(7));

        // created_at_formatted - форматированная дата
        $this->created_at_formatted = $createdAtCarbon->format('d.m.Y');

        // last_activity_date_formatted - форматированная дата активности
        if ($this->activity_date) {
            $activityCarbon = Carbon::parse($this->activity_date);
            $this->last_activity_date_formatted = $activityCarbon->format('d.m.Y');
        }

        // activity_title - локализованный заголовок активности (только если не задано значение из модели)
        if ($this->activity_title === null && $this->is_active !== null) {
            $this->activity_title = $this->is_active ? __('creatives.active') : __('creatives.was_active');
        }

        // isFavorite - требует userId для проверки
        if ($userId) {
            $this->isFavorite = $this->checkIfFavorite($userId);
        }
    }

    /**
     * Проверить является ли креатив избранным для пользователя
     */
    private function checkIfFavorite(int $userId): bool
    {
        return \App\Models\Favorite::where('user_id', $userId)
            ->where('creative_id', $this->id)
            ->exists();
    }


    /**
     * Создать коллекцию DTO из массива данных с оптимизацией
     */
    public static function collection(array $items, ?int $userId = null): array
    {
        if (empty($items)) {
            return [];
        }

        // Если много элементов - батчим проверку избранного
        if ($userId && count($items) > 10) {
            $creativeIds = array_column($items, 'id');
            $favoriteIds = self::batchCheckFavorites($userId, $creativeIds);

            return array_map(function (array $item) use ($userId, $favoriteIds) {
                $dto = self::fromArrayWithComputed($item, $userId);
                $dto->isFavorite = in_array($item['id'], $favoriteIds);
                return $dto->toArray();
            }, $items);
        }

        return array_map(
            fn(array $item) => self::fromArrayWithComputed($item, $userId)->toArray(),
            $items
        );
    }

    /**
     * Батчевая проверка избранного для оптимизации
     */
    private static function batchCheckFavorites(int $userId, array $creativeIds): array
    {
        return \App\Models\Favorite::where('user_id', $userId)
            ->whereIn('creative_id', $creativeIds)
            ->pluck('creative_id')
            ->toArray();
    }

    /**
     * Валидация данных перед созданием DTO
     */
    public static function validate(array $data): array
    {
        $errors = [];

        // Обязательные поля
        $required = ['id', 'title', 'description', 'category', 'country', 'file_size', 'icon_url', 'landing_url', 'created_at'];

        foreach ($required as $field) {
            if (empty($data[$field])) {
                $errors[] = "Field '{$field}' is required";
            }
        }

        // Типы данных
        if (isset($data['id']) && !is_numeric($data['id'])) {
            $errors[] = "Field 'id' must be numeric";
        }

        if (isset($data['has_video']) && !is_bool($data['has_video'])) {
            $errors[] = "Field 'has_video' must be boolean";
        }

        if (isset($data['is_adult']) && !is_bool($data['is_adult'])) {
            $errors[] = "Field 'is_adult' must be boolean";
        }

        // Валидация URL
        $urlFields = ['icon_url', 'landing_url', 'video_url', 'main_image_url'];
        foreach ($urlFields as $field) {
            if (isset($data[$field]) && !empty($data[$field]) && !filter_var($data[$field], FILTER_VALIDATE_URL)) {
                $errors[] = "Field '{$field}' must be a valid URL";
            }
        }

        // Валидация категории
        if (isset($data['category'])) {
            $validCategories = ['push', 'inpage', 'banner', 'video', 'native', 'facebook', 'tiktok'];
            if (!in_array($data['category'], $validCategories)) {
                $errors[] = "Field 'category' must be one of: " . implode(', ', $validCategories);
            }
        }

        // Валидация социальных полей (должны быть числовыми)
        $socialFields = ['social_likes', 'social_comments', 'social_shares'];
        foreach ($socialFields as $field) {
            if (isset($data[$field]) && !is_null($data[$field]) && !is_numeric($data[$field])) {
                $errors[] = "Field '{$field}' must be numeric";
            }
        }

        // Массивы
        $arrayFields = ['advertising_networks', 'languages', 'operating_systems', 'browsers', 'devices', 'file_sizes_detailed'];
        foreach ($arrayFields as $field) {
            if (isset($data[$field]) && !is_array($data[$field])) {
                $errors[] = "Field '{$field}' must be array";
            }
        }

        // Валидация дат
        $dateFields = ['created_at', 'activity_date'];
        foreach ($dateFields as $field) {
            if (isset($data[$field]) && !empty($data[$field])) {
                try {
                    Carbon::parse($data[$field]);
                } catch (\Exception $e) {
                    $errors[] = "Field '{$field}' must be a valid date";
                }
            }
        }

        return $errors;
    }

    /**
     * Фабричный метод для создания из модели Eloquent
     */
    public static function fromModel($model): self
    {
        // Используем новый метод toCreativeArray() из модели для правильного маппинга
        return self::fromArray($model->toCreativeArray());
    }

    /**
     * Создать коллекцию DTO из моделей Eloquent
     */
    public static function fromModels($models, ?int $userId = null): array
    {
        if (empty($models)) {
            return [];
        }

        $items = [];
        foreach ($models as $model) {
            $items[] = $model->toCreativeArray();
        }

        return self::collection($items, $userId);
    }

    /**
     * Фабричный метод для создания из API ответа
     */
    public static function fromApiResponse(array $apiData): self
    {
        // Маппинг полей если API возвращает другие названия
        $mappedData = [
            'id' => $apiData['id'] ?? $apiData['creative_id'] ?? null,
            'title' => $apiData['title'] ?? '',
            'description' => $apiData['description'] ?? $apiData['desc'] ?? '',
            'category' => $apiData['category'] ?? $apiData['type'] ?? '',
            'country' => $apiData['country'] ?? $apiData['country_code'] ?? null,
            'file_size' => $apiData['file_size'] ?? $apiData['size'] ?? '0KB',
            'icon_url' => $apiData['icon_url'] ?? $apiData['icon'] ?? '',
            'landing_url' => $apiData['landing_url'] ?? '',
            'created_at' => $apiData['created_at'] ?? $apiData['date_created'] ?? now()->format('Y-m-d'),
        ];

        // Опциональные поля
        foreach ($apiData as $key => $value) {
            if (!isset($mappedData[$key])) {
                $mappedData[$key] = $value;
            }
        }

        return self::fromArray($mappedData);
    }

    /**
     * Получить компактную версию для списков
     */
    public function toCompactArray(): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'description' => $this->description,
            'main_image_url' => $this->main_image_url,
            'category' => $this->category,
            'country' => $this->country,
            'icon_url' => $this->icon_url,
            'has_video' => $this->has_video,
            'is_adult' => $this->is_adult,
            'is_active' => $this->is_active,
            'displayName' => $this->displayName,
            'isRecent' => $this->isRecent,
            'isFavorite' => $this->isFavorite,
            'platform' => $this->platform,
            'created_at_formatted' => $this->created_at_formatted,
            // Добавляем поля необходимые для фронтенда
            'advertising_networks' => $this->advertising_networks,
            'languages' => $this->languages,
            'browsers' => $this->browsers,
            'devices' => $this->devices,
            'activity_date' => $this->activity_date,
            'activity_title' => $this->activity_title,
            'file_sizes_detailed' => $this->file_sizes_detailed,
        ];
    }

    /**
     * Получить полную версию для детального просмотра
     */
    public function toDetailedArray(): array
    {
        return $this->toArray();
    }

    /**
     * Имплементация Arrayable
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'description' => $this->description,
            'category' => $this->category,
            'country' => $this->country,
            'icon_url' => $this->icon_url,
            'landing_url' => $this->landing_url,
            'video_url' => $this->video_url,
            'has_video' => $this->has_video,
            'created_at' => $this->created_at,
            'activity_date' => $this->activity_date,
            'advertising_networks' => $this->advertising_networks,
            'languages' => $this->languages,
            'operating_systems' => $this->operating_systems,
            'browsers' => $this->browsers,
            'devices' => $this->devices,
            'main_image_url' => $this->main_image_url,
            'is_adult' => $this->is_adult,
            'social_likes' => $this->social_likes,
            'social_comments' => $this->social_comments,
            'social_shares' => $this->social_shares,
            'duration' => $this->duration,
            'displayName' => $this->displayName,
            'isRecent' => $this->isRecent,
            'isFavorite' => $this->isFavorite,
            'created_at_formatted' => $this->created_at_formatted,
            'last_activity_date_formatted' => $this->last_activity_date_formatted,
            'is_active' => $this->is_active,
            'platform' => $this->platform,
            'activity_title' => $this->activity_title,
            'file_sizes_detailed' => $this->file_sizes_detailed,
        ];
    }

    /**
     * Имплементация Jsonable
     */
    public function toJson($options = 0): string
    {
        return json_encode($this->toArray(), $options);
    }

    /**
     * Получить метаданные о креативе
     */
    public function getMetadata(): array
    {
        return [
            'hasVideo' => $this->has_video,
            'isAdult' => $this->is_adult,
            'socialEngagement' => [
                'likes' => $this->social_likes,
                'comments' => $this->social_comments,
                'shares' => $this->social_shares,
                'total' => ($this->social_likes ?? 0) + ($this->social_comments ?? 0) + ($this->social_shares ?? 0)
            ],
            'techInfo' => [
                'fileSizesDetailed' => $this->file_sizes_detailed,
                'duration' => $this->duration
            ],
            'platforms' => [
                'networks' => $this->advertising_networks,
                'os' => $this->operating_systems,
                'browsers' => $this->browsers,
                'devices' => $this->devices
            ]
        ];
    }
}

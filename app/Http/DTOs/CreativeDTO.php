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
        public string $name,
        public string $title,
        public string $description,
        public string $category,
        public string $country,
        public string $file_size,
        public string $icon_url,
        public string $landing_page_url,
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
        public ?array $image_sizes = null,
        public ?string $main_image_size = null,
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
    ) {}

    /**
     * Создать DTO из массива данных (например, из модели или API)
     */
    public static function fromArray(array $data): self
    {
        return new self(
            id: $data['id'],
            name: $data['name'],
            title: $data['title'],
            description: $data['description'],
            category: $data['category'],
            country: $data['country'],
            file_size: $data['file_size'],
            icon_url: $data['icon_url'],
            landing_page_url: $data['landing_page_url'],
            created_at: $data['created_at'],
            video_url: $data['video_url'] ?? null,
            has_video: $data['has_video'] ?? false,
            activity_date: $data['activity_date'] ?? null,
            advertising_networks: $data['advertising_networks'] ?? null,
            languages: $data['languages'] ?? null,
            operating_systems: $data['operating_systems'] ?? null,
            browsers: $data['browsers'] ?? null,
            devices: $data['devices'] ?? null,
            image_sizes: $data['image_sizes'] ?? null,
            main_image_size: $data['main_image_size'] ?? null,
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
     */
    public function computeProperties(?int $userId = null): void
    {
        // displayName - комбинация name и title
        $this->displayName = trim($this->name . ' - ' . $this->title);

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
            
            // is_active - активность за последние 30 дней
            $this->is_active = $activityCarbon->isAfter($now->copy()->subDays(30));
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
        // TODO: Реализовать проверку избранного через модель
        // Пример: return UserFavorite::where('user_id', $userId)->where('creative_id', $this->id)->exists();
        return false; // Заглушка
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
            
            return array_map(function(array $item) use ($userId, $favoriteIds) {
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
        // TODO: Реализовать батчевую проверку избранного
        // Пример: return UserFavorite::where('user_id', $userId)->whereIn('creative_id', $creativeIds)->pluck('creative_id')->toArray();
        return []; // Заглушка
    }

    /**
     * Валидация данных перед созданием DTO
     */
    public static function validate(array $data): array
    {
        $errors = [];

        // Обязательные поля
        $required = ['id', 'name', 'title', 'description', 'category', 'country', 'file_size', 'icon_url', 'landing_page_url', 'created_at'];

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
        $urlFields = ['icon_url', 'landing_page_url', 'video_url', 'main_image_url'];
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
        $arrayFields = ['advertising_networks', 'languages', 'operating_systems', 'browsers', 'devices', 'image_sizes'];
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
        return self::fromArray($model->toArray());
    }

    /**
     * Фабричный метод для создания из API ответа
     */
    public static function fromApiResponse(array $apiData): self
    {
        // Маппинг полей если API возвращает другие названия
        $mappedData = [
            'id' => $apiData['id'] ?? $apiData['creative_id'] ?? null,
            'name' => $apiData['name'] ?? $apiData['creative_name'] ?? '',
            'title' => $apiData['title'] ?? '',
            'description' => $apiData['description'] ?? $apiData['desc'] ?? '',
            'category' => $apiData['category'] ?? $apiData['type'] ?? '',
            'country' => $apiData['country'] ?? $apiData['country_code'] ?? '',
            'file_size' => $apiData['file_size'] ?? $apiData['size'] ?? '0KB',
            'icon_url' => $apiData['icon_url'] ?? $apiData['icon'] ?? '',
            'landing_page_url' => $apiData['landing_page_url'] ?? $apiData['landing_url'] ?? '',
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
            'name' => $this->name,
            'title' => $this->title,
            'category' => $this->category,
            'country' => $this->country,
            'icon_url' => $this->icon_url,
            'has_video' => $this->has_video,
            'is_adult' => $this->is_adult,
            'displayName' => $this->displayName,
            'isRecent' => $this->isRecent,
            'isFavorite' => $this->isFavorite,
            'created_at_formatted' => $this->created_at_formatted,
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
            'name' => $this->name,
            'title' => $this->title,
            'description' => $this->description,
            'category' => $this->category,
            'country' => $this->country,
            'file_size' => $this->file_size,
            'icon_url' => $this->icon_url,
            'landing_page_url' => $this->landing_page_url,
            'video_url' => $this->video_url,
            'has_video' => $this->has_video,
            'created_at' => $this->created_at,
            'activity_date' => $this->activity_date,
            'advertising_networks' => $this->advertising_networks,
            'languages' => $this->languages,
            'operating_systems' => $this->operating_systems,
            'browsers' => $this->browsers,
            'devices' => $this->devices,
            'image_sizes' => $this->image_sizes,
            'main_image_size' => $this->main_image_size,
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
                'fileSize' => $this->file_size,
                'imageSize' => $this->main_image_size,
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

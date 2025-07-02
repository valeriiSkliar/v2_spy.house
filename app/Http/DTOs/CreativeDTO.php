<?php

namespace App\Http\DTOs;

use Carbon\Carbon;

/**
 * DTO для креативов на основе TypeScript интерфейса Creative
 * Обеспечивает type safety между frontend и backend
 */
class CreativeDTO
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

        // Социальные поля
        public int|string|null $social_likes = null,
        public int|string|null $social_comments = null,
        public int|string|null $social_shares = null,
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
            social_likes: $data['social_likes'] ?? null,
            social_comments: $data['social_comments'] ?? null,
            social_shares: $data['social_shares'] ?? null,
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
     * Вычислить computed свойства
     */
    public function computeProperties(?int $userId = null): void
    {
        // displayName - комбинация name и title
        $this->displayName = trim($this->name . ' - ' . $this->title);

        // isRecent - создан за последние 7 дней
        $this->isRecent = Carbon::parse($this->created_at)->isAfter(now()->subDays(7));

        // created_at_formatted - форматированная дата
        $this->created_at_formatted = Carbon::parse($this->created_at)->format('d.m.Y');

        // last_activity_date_formatted - форматированная дата активности
        if ($this->activity_date) {
            $this->last_activity_date_formatted = Carbon::parse($this->activity_date)->format('d.m.Y');
        }

        // is_active - активность за последние 30 дней
        if ($this->activity_date) {
            $this->is_active = Carbon::parse($this->activity_date)->isAfter(now()->subDays(30));
        }

        // isFavorite - требует userId для проверки
        if ($userId) {
            // TODO: Реализовать проверку избранного
            $this->isFavorite = false; // Заглушка
        }
    }

    /**
     * Конвертировать в массив для JSON ответа (точно соответствует TypeScript интерфейсу)
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
     * Создать коллекцию DTO из массива данных
     */
    public static function collection(array $items, ?int $userId = null): array
    {
        return array_map(
            fn(array $item) => self::fromArrayWithComputed($item, $userId)->toArray(),
            $items
        );
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

        // Массивы
        $arrayFields = ['advertising_networks', 'languages', 'operating_systems', 'browsers', 'devices', 'image_sizes'];
        foreach ($arrayFields as $field) {
            if (isset($data[$field]) && !is_array($data[$field])) {
                $errors[] = "Field '{$field}' must be array";
            }
        }

        return $errors;
    }
}

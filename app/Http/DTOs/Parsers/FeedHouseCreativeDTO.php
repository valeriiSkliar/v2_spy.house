<?php

namespace App\Http\DTOs\Parsers;

use App\Enums\Frontend\AdvertisingFormat;
use App\Enums\Frontend\AdvertisingStatus;
use App\Enums\Frontend\Platform;
use App\Models\AdvertismentNetwork;
use App\Models\Browser;
use App\Services\Parsers\BrowserNormalizer;
use App\Services\Parsers\CreativePlatformNormalizer;
use App\Services\Parsers\CountryCodeNormalizer;
use App\Services\Parsers\OperationSystemNormalizer;
use App\Services\Parsers\SourceNormalizer;
use App\Services\Parsers\CreativeImageValidator;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

/**
 * DTO для креативов FeedHouse API
 *
 * Обеспечивает типизацию, валидацию и трансформацию данных
 * от FeedHouse API в унифицированный формат для записи в БД
 * 
 * Поддерживает только форматы: push, inpage
 * Все остальные форматы игнорируются при валидации
 * 
 * FORMAT-SPECIFIC ПРАВИЛА ВАЛИДАЦИИ ИЗОБРАЖЕНИЙ:
 * 
 * PUSH креативы:
 * - Валидны если оба изображения (icon + image) доступны
 * - Валидны если только main image доступна (icon_url устанавливается в null)
 * - НЕ валидны если только icon доступна без main image
 * 
 * INPAGE креативы:
 * - Валидны если хотя бы одно изображение доступно
 * - Доступное изображение записывается в поле icon_url
 * - main_image_url всегда устанавливается в null
 * - Если доступна только main image, она перемещается в icon_url
 *
 * @package App\Http\DTOs\Parsers
 * @author SeniorSoftwareEngineer
 * @version 1.0.1
 */
class FeedHouseCreativeDTO
{
    /**
     * Поддерживаемые форматы рекламы
     */
    private const SUPPORTED_FORMATS = ['push', 'inpage'];

    public function __construct(
        public readonly int $externalId,
        public readonly string $title,
        public readonly string $text,
        public readonly string $iconUrl,
        public readonly string $imageUrl,
        public readonly string $targetUrl,
        public readonly string $countryCode,
        public readonly Platform $platform,
        public readonly AdvertisingFormat $format,
        public readonly string $adNetwork,
        public readonly string $browser,
        public readonly string $os,
        public readonly string $deviceType,
        public readonly bool $isActive,
        public readonly bool $isAdult,
        public readonly Carbon $createdAt,
        public readonly int $seenCount = 0,
        public readonly ?Carbon $lastSeenAt = null,
        public readonly string $source = 'feedhouse'
    ) {}

    /**
     * Создает DTO из сырых данных API FeedHouse
     *
     * @param array $data Сырые данные от FeedHouse API
     * @return self
     */
    public static function fromApiResponse(array $data): self
    {
        return new self(
            externalId: (int) ($data['id'] ?? 0),
            title: $data['title'] ?? '',
            text: $data['text'] ?? '',
            iconUrl: $data['icon'] ?? '',
            imageUrl: $data['image'] ?? '', // Note: 'image', not 'img'
            targetUrl: $data['url'] ?? '',
            countryCode: strtoupper($data['countryIso'] ?? ''),
            platform: self::determinePlatformFromMetadata($data),
            format: self::normalizeFormat($data['format'] ?? 'push'),
            adNetwork: $data['adNetwork'] ?? 'unknown',
            browser: $data['browser'] ?? '',
            os: $data['os'] ?? '',
            deviceType: $data['deviceType'] ?? '',
            isActive: self::normalizeStatus($data['status'] ?? 'inactive'),
            isAdult: self::detectAdultContent($data),
            createdAt: self::parseCreatedAt($data['createdAt'] ?? null),
            seenCount: (int) ($data['seenCount'] ?? 0),
            lastSeenAt: self::parseLastSeenAt($data['lastSeenAt'] ?? null)
        );
    }

    /**
     * Определяет платформу на основе метаданных
     *
     * @param array $data Данные с метаинформацией
     * @return Platform
     */
    private static function determinePlatformFromMetadata(array $data): Platform
    {
        $os = strtolower($data['os'] ?? '');
        $deviceType = strtolower($data['deviceType'] ?? '');

        // Мобильные платформы
        if (str_contains($os, 'android') || str_contains($os, 'ios')) {
            return Platform::MOBILE;
        }

        // По типу устройства
        if (str_contains($deviceType, 'phone') || str_contains($deviceType, 'mobile')) {
            return Platform::MOBILE;
        }

        if (str_contains($deviceType, 'tablet')) {
            return Platform::MOBILE; // Tablet считаем как Mobile
        }

        // Desktop по умолчанию для Windows, MacOS, Linux
        if (str_contains($os, 'windows') || str_contains($os, 'macos') || str_contains($os, 'linux')) {
            return Platform::DESKTOP;
        }

        // Fallback
        return Platform::MOBILE;
    }

    /**
     * Нормализует формат рекламы
     *
     * @param string $format Формат от API
     * @return AdvertisingFormat
     */
    private static function normalizeFormat(string $format): AdvertisingFormat
    {
        return match (strtolower($format)) {
            'push' => AdvertisingFormat::PUSH,
            'inpage' => AdvertisingFormat::INPAGE,
            'native' => AdvertisingFormat::INPAGE, // Mapping native to INPAGE
            'banner' => AdvertisingFormat::INPAGE, // Mapping banner to INPAGE
            default => AdvertisingFormat::PUSH, // Fallback
        };
    }

    /**
     * Нормализует статус активности
     *
     * @param string $status Статус от API
     * @return bool
     */
    private static function normalizeStatus(string $status): bool
    {
        return strtolower($status) === 'active';
    }

    /**
     * Детектирует взрослый контент эвристически
     *
     * @param array $data Данные креатива
     * @return bool
     */
    private static function detectAdultContent(array $data): bool
    {
        $text = strtolower(($data['title'] ?? '') . ' ' . ($data['text'] ?? ''));

        $adultKeywords = [
            'sex',
            'dating',
            'adult',
            'porn',
            'xxx',
            'sexy',
            'hot girls',
            'escorts',
            'hookup',
            'nude',
            'erotic',
            'massage',
            'intimate'
        ];

        foreach ($adultKeywords as $keyword) {
            if (str_contains($text, $keyword)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Безопасный парсинг даты создания
     *
     * @param mixed $dateValue Значение даты от API
     * @return Carbon Валидная дата
     */
    private static function parseCreatedAt($dateValue): Carbon
    {
        if (empty($dateValue)) {
            return now();
        }

        try {
            $parsedDate = Carbon::parse($dateValue);

            if ($parsedDate->year <= 1970) {
                return now();
            }

            if ($parsedDate->isFuture() && $parsedDate->diffInYears(now()) > 1) {
                return now();
            }

            return $parsedDate;
        } catch (\Exception $e) {
            return now();
        }
    }

    /**
     * Безопасный парсинг даты последнего просмотра
     *
     * @param mixed $dateValue Значение даты от API
     * @return Carbon|null Валидная дата или null
     */
    private static function parseLastSeenAt($dateValue): ?Carbon
    {
        if (empty($dateValue)) {
            return null;
        }

        try {
            return Carbon::parse($dateValue);
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Преобразует DTO в массив для записи в БД
     *
     * @return array Данные для записи в таблицу creatives
     */
    public function toDatabase(): array
    {
        return [
            // Основные поля
            'external_id' => $this->externalId,
            'title' => $this->title,
            'description' => $this->text,
            'icon_url' => $this->iconUrl,
            'main_image_url' => $this->imageUrl,
            'landing_url' => $this->targetUrl,
            'platform' => $this->platform->value,
            'format' => $this->format->value,
            'is_adult' => $this->isAdult,
            'external_created_at' => $this->createdAt,
            'browser_id' => BrowserNormalizer::normalizeBrowserName($this->browser),

            // Нормализованные foreign key поля
            'source_id' => SourceNormalizer::normalizeSourceName($this->source),
            'country_id' => CountryCodeNormalizer::normalizeCountryCode($this->countryCode),
            'advertisment_network_id' => AdvertismentNetwork::where('network_name', $this->adNetwork)->first()?->id,

            // Статус
            'status' => $this->isActive ? AdvertisingStatus::Active : AdvertisingStatus::Inactive,

            // Метаданные (JSON поля)
            'metadata' => [
                'adNetwork' => $this->adNetwork,
                'browser' => $this->browser,
                'os' => $this->os,
                'deviceType' => $this->deviceType,
                'seenCount' => $this->seenCount,
                'lastSeenAt' => $this->lastSeenAt?->toISOString(),
                'source_api' => 'feedhouse_business_api'
            ],

            // Уникальный хеш
            'combined_hash' => $this->generateCombinedHash(),

            // Временные метки
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }

    /**
     * Преобразует DTO в базовую версию для немедленного сохранения
     * Включает только критически необходимые поля (для hybrid подхода)
     * Применяет format-specific правила для URL изображений
     */
    public function toBasicDatabase(): array
    {
        // Получаем правильные URL изображений согласно format-specific правилам
        $validatedImages = $this->getValidatedImageUrls();

        return [
            // Критические поля (обработаны синхронно)
            'external_id' => $this->externalId,
            'title' => $this->title,
            'description' => $this->text,
            'icon_url' => $validatedImages['icon_url'],
            'main_image_url' => $validatedImages['main_image_url'],
            'landing_url' => $this->targetUrl,
            'platform' => $this->platform->value,
            'format' => $this->format->value,
            'is_adult' => $this->isAdult, // Быстрое эвристическое определение
            'external_created_at' => $this->createdAt,

            // Базовые foreign keys
            'source_id' => SourceNormalizer::normalizeSourceName($this->source),
            'country_id' => CountryCodeNormalizer::normalizeCountryCode($this->countryCode),
            'browser_id' => BrowserNormalizer::normalizeBrowserName($this->browser),
            'advertisment_network_id' => AdvertismentNetwork::where('network_name', $this->adNetwork)->first()?->id,
            'operation_system' => OperationSystemNormalizer::normalizeWithFallback($this->os),

            // Статус
            'status' => $this->isActive ? AdvertisingStatus::Active : AdvertisingStatus::Inactive,

            // Уникальный хеш
            'combined_hash' => $this->generateCombinedHash(),

            // Метаданные (базовые + информация о валидации изображений)
            'metadata' => [
                'seenCount' => $this->seenCount,
                'processing_status' => 'basic',
                'enhancement_required' => true,
                'source_api' => 'feedhouse_business_api',
                'format_specific_validation' => [
                    'format' => $this->format->value,
                    'original_icon_url' => $this->iconUrl,
                    'original_image_url' => $this->imageUrl,
                    'applied_rules' => $this->getAppliedValidationRules($validatedImages)
                ]
            ],

            // Временные метки
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }

    /**
     * Преобразует DTO в полную версию с обогащением (для постобработки)
     */
    public function toEnhancedDatabase(array $enhancementData = []): array
    {
        $basic = $this->toBasicDatabase();

        // Добавляем результаты постобработки
        $enhanced = array_merge($basic, [
            'metadata' => array_merge($basic['metadata'], [
                // Результаты асинхронной обработки
                'browser' => $this->browser,
                'os' => $this->os,
                'deviceType' => $this->deviceType,
                'lastSeenAt' => $this->lastSeenAt?->toISOString(),
                'geo_enriched' => $enhancementData['geo_data'] ?? null,
                'category_analysis' => $enhancementData['category'] ?? null,
                'image_analysis' => $enhancementData['image_analysis'] ?? null,
                'content_analysis' => $enhancementData['content_analysis'] ?? null,
                'processing_status' => 'enhanced',
                'enhancement_required' => false,
                'enhanced_at' => now()->toISOString()
            ]),

            // Обновлённые поля
            'is_adult' => $enhancementData['refined_adult_detection'] ?? $this->isAdult,
            // add all fields from enhancementData
            // ...$enhancementData,

            'updated_at' => now(),
        ]);

        return $enhanced;
    }

    /**
     * Генерирует уникальный хеш для креатива
     *
     * @return string SHA256 хеш для идентификации креатива
     */
    private function generateCombinedHash(): string
    {
        $data = [
            'external_id' => $this->externalId,
            'source' => $this->source,
            'title' => $this->title,
            'text' => $this->text,
            'country' => $this->countryCode,
            'adNetwork' => $this->adNetwork,
        ];

        return hash('sha256', json_encode($data, JSON_UNESCAPED_UNICODE));
    }

    /**
     * Валидация данных DTO с проверкой доступности изображений
     *
     * @param bool $validateImages Включить глубокую валидацию изображений (по умолчанию true)
     * @return bool true если данные валидны
     */
    public function isValid(bool $validateImages = true): bool
    {
        // Базовая валидация - только обязательные поля
        if (empty($this->externalId)) {
            return false;
        }

        // Проверяем наличие хотя бы заголовка или текста
        if (empty($this->title) && empty($this->text)) {
            return false;
        }

        // Проверяем наличие хотя бы одного изображения
        if (empty($this->iconUrl) && empty($this->imageUrl)) {
            return false;
        }

        // Проверяем валидность URL лендинга
        if (!empty($this->targetUrl) && !filter_var($this->targetUrl, FILTER_VALIDATE_URL)) {
            return false;
        }

        // Проверяем поддерживаемые форматы - игнорируем все кроме push и inpage
        if (!in_array($this->format->value, self::SUPPORTED_FORMATS, true)) {
            return false;
        }

        // Глубокая валидация изображений (опциональная)
        if ($validateImages && config('services.creative_validator.image_validation.enabled', true)) {
            return $this->validateImageAccessibility();
        }

        return true;
    }

    /**
     * Получает правильные URL изображений согласно format-specific правилам
     *
     * @return array ['icon_url' => string|null, 'main_image_url' => string|null]
     */
    private function getValidatedImageUrls(): array
    {
        $validator = new CreativeImageValidator();

        $imageUrls = array_filter([
            'icon' => $this->iconUrl,
            'image' => $this->imageUrl
        ]);

        if (empty($imageUrls)) {
            return ['icon_url' => null, 'main_image_url' => null];
        }

        try {
            $validationResults = $validator->validateImages($imageUrls);

            $iconValid = isset($validationResults[$this->iconUrl]) && $validationResults[$this->iconUrl]['valid'];
            $imageValid = isset($validationResults[$this->imageUrl]) && $validationResults[$this->imageUrl]['valid'];

            // Format-specific логика обработки URL
            if ($this->format->value === 'push') {
                // Push: оба изображения валидны ИЛИ только main image валидна
                if ($iconValid && $imageValid) {
                    // Случай 1: оба изображения валидны
                    return [
                        'icon_url' => $this->iconUrl,
                        'main_image_url' => $this->imageUrl
                    ];
                } elseif (!$iconValid && $imageValid) {
                    // Случай 2: только main image валидна - icon_url = null
                    return [
                        'icon_url' => null,
                        'main_image_url' => $this->imageUrl
                    ];
                }

                // Если только icon валидна (но не main image) - креатив невалиден для push
                return ['icon_url' => null, 'main_image_url' => null];
            } elseif ($this->format->value === 'inpage') {
                // Inpage: валидное изображение записывается в icon_url, второе поле = null
                if ($iconValid) {
                    return [
                        'icon_url' => $this->iconUrl,
                        'main_image_url' => null
                    ];
                } elseif ($imageValid) {
                    return [
                        'icon_url' => $this->imageUrl, // main image перемещается в icon_url
                        'main_image_url' => null
                    ];
                }

                return ['icon_url' => null, 'main_image_url' => null];
            }

            // Fallback для других форматов: стандартная логика
            return [
                'icon_url' => $iconValid ? $this->iconUrl : null,
                'main_image_url' => $imageValid ? $this->imageUrl : null
            ];
        } catch (\Exception $e) {
            Log::warning('Failed to validate image URLs for creative', [
                'external_id' => $this->externalId,
                'format' => $this->format->value,
                'error' => $e->getMessage()
            ]);

            // Fallback при ошибке валидации
            return ['icon_url' => null, 'main_image_url' => null];
        }
    }

    /**
     * Получает описание примененных правил валидации
     *
     * @param array $validatedImages Результат валидации изображений
     * @return string Описание примененных правил
     */
    private function getAppliedValidationRules(array $validatedImages): string
    {
        $rules = [];

        if ($this->format->value === 'push') {
            if ($validatedImages['icon_url'] && $validatedImages['main_image_url']) {
                $rules[] = 'push_both_images_valid';
            } elseif (!$validatedImages['icon_url'] && $validatedImages['main_image_url']) {
                $rules[] = 'push_only_main_image_valid_icon_nulled';
            } else {
                $rules[] = 'push_invalid_image_combination';
            }
        } elseif ($this->format->value === 'inpage') {
            if ($validatedImages['icon_url'] && !$validatedImages['main_image_url']) {
                if ($validatedImages['icon_url'] === $this->iconUrl) {
                    $rules[] = 'inpage_icon_used_main_nulled';
                } elseif ($validatedImages['icon_url'] === $this->imageUrl) {
                    $rules[] = 'inpage_main_moved_to_icon';
                }
            } else {
                $rules[] = 'inpage_no_valid_images';
            }
        } else {
            $rules[] = 'fallback_standard_validation';
        }

        return implode(', ', $rules);
    }

    /**
     * Проверяет доступность изображений креатива с учетом format-specific правил
     *
     * @return bool true если изображения соответствуют правилам формата
     */
    private function validateImageAccessibility(): bool
    {
        try {
            // Используем новый метод для получения валидированных URL
            $validatedImages = $this->getValidatedImageUrls();

            // Format-specific валидация
            if ($this->format->value === 'push') {
                // Push: должна быть хотя бы main_image_url (icon_url может быть null)
                return !empty($validatedImages['main_image_url']);
            } elseif ($this->format->value === 'inpage') {
                // Inpage: должна быть icon_url (main_image_url всегда null)
                return !empty($validatedImages['icon_url']);
            }

            // Fallback для других форматов: хотя бы одно изображение
            return !empty($validatedImages['icon_url']) || !empty($validatedImages['main_image_url']);
        } catch (\Exception $e) {
            // При ошибке валидации логируем и пропускаем креатив если настроено fallback
            Log::warning('Image validation failed for creative', [
                'external_id' => $this->externalId,
                'title' => $this->title,
                'format' => $this->format->value,
                'error' => $e->getMessage()
            ]);

            // Если включен fallback - пропускаем валидацию при ошибках
            return config('services.creative_validator.fallback.skip_on_error', true);
        }
    }

    /**
     * Проверяет, является ли креатив дубликатом
     *
     * @param array $existingCreatives Массив существующих креативов
     * @return bool true если дубликат найден
     */
    public function isDuplicate(array $existingCreatives): bool
    {
        $currentHash = $this->generateCombinedHash();

        foreach ($existingCreatives as $creative) {
            if (isset($creative['combined_hash']) && $creative['combined_hash'] === $currentHash) {
                return true;
            }
        }

        return false;
    }

    /**
     * Проверяет, поддерживается ли формат креатива
     *
     * @return bool true если формат поддерживается (push или inpage)
     */
    public function isSupportedFormat(): bool
    {
        return in_array($this->format->value, self::SUPPORTED_FORMATS, true);
    }

    /**
     * Возвращает список поддерживаемых форматов
     *
     * @return array Массив поддерживаемых форматов
     */
    public static function getSupportedFormats(): array
    {
        return self::SUPPORTED_FORMATS;
    }
}

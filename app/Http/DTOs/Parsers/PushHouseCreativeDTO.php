<?php

namespace App\Http\DTOs\Parsers;

use App\Enums\Frontend\AdvertisingFormat;
use App\Enums\Frontend\AdvertisingStatus;
use App\Enums\Frontend\Platform;
use App\Models\AdvertismentNetwork;
use App\Services\Parsers\CreativePlatformNormalizer;
use App\Services\Parsers\CountryCodeNormalizer;
use App\Services\Parsers\SourceNormalizer;
use Carbon\Carbon;

/**
 * DTO для креативов Push.House API
 * 
 * Обеспечивает типизацию, валидацию и трансформацию данных
 * от Push.House API в унифицированный формат для записи в БД
 * 
 * @package App\Http\DTOs\Parsers
 * @author SeniorSoftwareEngineer
 * @version 1.0.0
 */
class PushHouseCreativeDTO
{
    public function __construct(
        public readonly int $externalId,
        public readonly string $title,
        public readonly string $text,
        public readonly string $iconUrl,
        public readonly string $imageUrl,
        public readonly string $targetUrl,
        public readonly float $cpc,
        public readonly string $countryCode,
        public readonly Platform $platform,
        public readonly bool $isAdult,
        public readonly bool $isActive,
        public readonly Carbon $createdAt,
        public readonly string $source = 'push_house'
    ) {}

    /**
     * Создает DTO из сырых данных API Push.House
     * Совместимо с существующим парсером PushHouseParser
     *
     * @param array $data Сырые данные от API или парсера
     * @return self
     */
    public static function fromApiResponse(array $data): self
    {
        return new self(
            externalId: $data['id'] ?? $data['res_uniq_id'] ?? 0, // Поддержка обоих форматов
            title: $data['title'] ?? '',
            text: $data['text'] ?? '',
            iconUrl: $data['icon'] ?? '',
            imageUrl: $data['img'] ?? '',
            targetUrl: $data['url'] ?? '',
            cpc: (float) ($data['cpc'] ?? $data['price_cpc'] ?? 0), // Поддержка обоих форматов
            countryCode: strtoupper($data['country'] ?? ''),
            platform: self::normalizePlatformValue($data),
            isAdult: (bool) ($data['isAdult'] ?? false),
            isActive: (bool) ($data['isActive'] ?? true), // По умолчанию true для активных
            createdAt: self::parseCreatedAt($data['created_at'] ?? null)
        );
    }

    /**
     * Безопасный парсинг даты создания
     *
     * @param mixed $dateValue Значение даты от API
     * @return Carbon Валидная дата
     */
    private static function parseCreatedAt($dateValue): Carbon
    {
        // Если значение пустое или null - используем текущую дату
        if (empty($dateValue)) {
            return now();
        }

        try {
            $parsedDate = Carbon::parse($dateValue);

            // Проверяем, что дата не является Unix timestamp 0 (1970-01-01)
            if ($parsedDate->year <= 1970) {
                return now();
            }

            // Проверяем, что дата не в будущем (более чем на год)
            if ($parsedDate->isFuture() && $parsedDate->diffInYears(now()) > 1) {
                return now();
            }

            return $parsedDate;
        } catch (\Exception $e) {
            // Если парсинг не удался - используем текущую дату
            return now();
        }
    }

    /**
     * Нормализация платформы для нового формата API
     *
     * @param array $data Данные с информацией о платформе
     * @return Platform
     */
    private static function normalizePlatformValue(array $data): Platform
    {
        // Новый формат API
        if (isset($data['platform'])) {
            return CreativePlatformNormalizer::normalizePlatform($data['platform'], 'push_house');
        }

        // Fallback
        return Platform::MOBILE;
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
            'is_adult' => $this->isAdult,
            'external_created_at' => $this->createdAt,
            'advertisment_network_id' => AdvertismentNetwork::where('network_name', 'pushhouse')->first()?->id,

            // Нормализованные foreign key поля
            'source_id' => SourceNormalizer::normalizeSourceName($this->source),
            'country_id' => CountryCodeNormalizer::normalizeCountryCode($this->countryCode),

            // Преобразование boolean в enum для статуса
            'status' => $this->isActive ? AdvertisingStatus::Active : AdvertisingStatus::Inactive,

            // Определение формата на основе изображений
            'format' => $this->determineAdvertisingFormat(),
            'combined_hash' => $this->generateCombinedHash(),

            // Стандартные временные метки
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }

    /**
     * Определяет формат рекламы на основе наличия изображений
     *
     * @return AdvertisingFormat Формат рекламы (PUSH или INPAGE)
     */
    private function determineAdvertisingFormat(): AdvertisingFormat
    {
        $hasIconImage = $this->hasValidImageUrl($this->iconUrl);
        $hasMainImage = $this->hasValidImageUrl($this->imageUrl);

        // Оба изображения (icon + img с именами файлов) → PUSH
        if ($hasIconImage && $hasMainImage) {
            return AdvertisingFormat::PUSH;
        }

        // Только main image (img) без icon → PUSH
        if (!$hasIconImage && $hasMainImage) {
            return AdvertisingFormat::PUSH;
        }

        // Только icon с именем файла → INPAGE
        if ($hasIconImage && !$hasMainImage) {
            return AdvertisingFormat::INPAGE;
        }

        // Fallback на PUSH (не должно происходить для валидных креативов)
        return AdvertisingFormat::PUSH;
    }

    /**
     * Проверяет, содержит ли URL валидное изображение
     *
     * @param string $imageUrl URL изображения
     * @return bool true если URL содержит имя файла изображения
     */
    private function hasValidImageUrl(string $imageUrl): bool
    {
        if (empty($imageUrl)) {
            return false;
        }

        // Проверяем, что URL не заканчивается на "/" (нет имени файла)
        if (str_ends_with($imageUrl, '/')) {
            return false;
        }

        // Извлекаем имя файла из URL
        $filename = basename($imageUrl);

        // Проверяем, что есть имя файла и оно содержит точку (расширение)
        return !empty($filename) && str_contains($filename, '.');
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
        ];

        return hash('sha256', json_encode($data, JSON_UNESCAPED_UNICODE));
    }

    /**
     * Валидация данных DTO
     *
     * @return bool true если данные валидны
     */
    public function isValid(): bool
    {
        // Базовая валидация
        if (empty($this->externalId) || empty($this->countryCode)) {
            return false;
        }

        // Проверяем наличие хотя бы одного валидного изображения
        $hasIconImage = $this->hasValidImageUrl($this->iconUrl);
        $hasMainImage = $this->hasValidImageUrl($this->imageUrl);

        // Если нет ни одного изображения с именем файла - креатив невалиден
        return $hasIconImage || $hasMainImage;
    }
}

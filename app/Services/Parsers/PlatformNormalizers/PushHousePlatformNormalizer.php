<?php

namespace App\Services\Parsers\PlatformNormalizers;

use App\Enums\Frontend\Platform;

class PushHousePlatformNormalizer implements PlatformNormalizerInterface
{
    /**
     * Маппинг значений Push.House на наши enum
     */
    private const PLATFORM_MAPPING = [
        'mob' => Platform::MOBILE,
        'mobile' => Platform::MOBILE,
        'desktop' => Platform::DESKTOP,
        'desk' => Platform::DESKTOP,
        'pc' => Platform::DESKTOP,
        'web' => Platform::DESKTOP,
    ];

    /**
     * Нормализует значение платформы Push.House к стандартному enum
     */
    public function normalize(string $platformValue): Platform
    {
        $normalizedValue = strtolower(trim($platformValue));

        return self::PLATFORM_MAPPING[$normalizedValue] ?? Platform::MOBILE;
    }

    /**
     * Проверяет, может ли данный нормализатор обработать Push.House источник
     */
    public function canHandle(string $source): bool
    {
        return strtolower($source) === 'push_house';
    }
}

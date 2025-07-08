<?php

namespace App\Services\Parsers;

use App\Enums\Frontend\Platform;
use App\Services\Parsers\PlatformNormalizers\PlatformNormalizerInterface;
use App\Services\Parsers\PlatformNormalizers\PushHousePlatformNormalizer;
use InvalidArgumentException;

class CreativePlatformNormalizer
{
    /**
     * Зарегистрированные нормализаторы
     * 
     * @var PlatformNormalizerInterface[]
     */
    private array $normalizers = [];

    public function __construct()
    {
        $this->registerDefaultNormalizers();
    }

    /**
     * Регистрирует нормализатор для конкретного источника
     */
    public function registerNormalizer(PlatformNormalizerInterface $normalizer): self
    {
        $this->normalizers[] = $normalizer;
        return $this;
    }

    /**
     * Нормализует значение платформы для указанного источника
     */
    public function normalize(string $platformValue, string $source): Platform
    {
        foreach ($this->normalizers as $normalizer) {
            if ($normalizer->canHandle($source)) {
                return $normalizer->normalize($platformValue);
            }
        }

        throw new InvalidArgumentException("No normalizer found for source: {$source}");
    }

    /**
     * Статический метод для быстрого использования
     */
    public static function normalizePlatform(string $platformValue, string $source): Platform
    {
        return (new self())->normalize($platformValue, $source);
    }

    /**
     * Регистрирует стандартные нормализаторы
     */
    private function registerDefaultNormalizers(): void
    {
        $this->registerNormalizer(new PushHousePlatformNormalizer());
    }
}

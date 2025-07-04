<?php

namespace App\Services\Parsers\PlatformNormalizers;

use App\Enums\Frontend\Platform;

interface PlatformNormalizerInterface
{
    /**
     * Нормализует значение платформы к стандартному enum
     */
    public function normalize(string $platformValue): Platform;

    /**
     * Проверяет, может ли данный нормализатор обработать это значение
     */
    public function canHandle(string $source): bool;
}

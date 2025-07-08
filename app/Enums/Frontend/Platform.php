<?php

namespace App\Enums\Frontend;

use App\Traits\Enum\EnumTrait;

enum Platform: string
{
    use EnumTrait;

    case MOBILE = 'mobile';
    case DESKTOP = 'desktop';

    /**
     * Получить человекочитаемое название
     */
    public function label(): string
    {
        return match ($this) {
            self::MOBILE => 'Mobile',
            self::DESKTOP => 'Desktop',
        };
    }
}

<?php

namespace App\Enums\Finance;

enum PromocodeStatus: string
{
    case ACTIVE = 'active';
    case INACTIVE = 'inactive';
    case EXPIRED = 'expired';
    case EXHAUSTED = 'exhausted';

    /**
     * Get all possible values
     *
     * @return array<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    /**
     * Get all possible names
     *
     * @return array<string>
     */
    public static function names(): array
    {
        return array_column(self::cases(), 'name');
    }

    /**
     * Check if promocode is usable
     */
    public function isUsable(): bool
    {
        return $this === self::ACTIVE;
    }

    /**
     * Get human readable label
     */
    public function label(): string
    {
        return match ($this) {
            self::ACTIVE => 'Активный',
            self::INACTIVE => 'Неактивный',
            self::EXPIRED => 'Истек',
            self::EXHAUSTED => 'Исчерпан',
        };
    }
}

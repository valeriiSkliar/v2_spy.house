<?php

namespace App\Enums\Frontend;

use App\Traits\Enum\EnumTrait;

enum DeviceType: string
{
    use EnumTrait;

    case DESKTOP = 'Desktop';
    case MOBILE = 'Mobile';
    case TABLET = 'Tablet';
    case TV = 'TV';
    case CONSOLE = 'Console';
    case UNKNOWN = 'unknown';

    /**
     * Get all enum case values.
     *
     * @return array<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    /**
     * Get a human-readable label for the enum case.
     */
    public function label(): string
    {
        return match ($this) {
            self::DESKTOP => 'Desktop',
            self::MOBILE => 'Mobile',
            self::TABLET => 'Tablet',
            self::TV => 'TV',
            self::CONSOLE => 'Console',
            self::UNKNOWN => 'Unknown',
        };
    }

    /**
     * Get a human-readable translated label for the enum case.
     */
    public function translatedLabel(): string
    {
        return __('enums.DeviceType.' . $this->name);
    }

    /**
     * Get all enum cases with their translated labels for a select list.
     *
     * @return array<string, string>
     */
    public static function getTranslatedList(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn(self $case) => [$case->name => $case->translatedLabel()])
            ->all();
    }

    /**
     * Get all enum cases in format for frontend select.
     *
     * @return array<array{value: string, label: string}>
     */
    public static function getForSelect(): array
    {
        return collect(self::cases())
            ->filter(fn(self $case) => $case !== self::CONSOLE && $case !== self::UNKNOWN)
            ->map(fn(self $case) => [
                'value' => $case->value,
                'label' => $case->label()
            ])
            ->values()
            ->all();
    }
}

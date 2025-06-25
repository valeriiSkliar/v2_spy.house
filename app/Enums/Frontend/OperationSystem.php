<?php

namespace App\Enums\Frontend;

use App\Traits\Enum\EnumTrait;

enum OperationSystem: string
{
    use EnumTrait;

    case ANDROID = 'android';
    case WINDOWS = 'windows';
    case MACOS = 'macosx';
    case IOS = 'ios';
    case LINUX = 'linux';
    case CHROMEOS = 'chromeos';
    case KINDLE = 'kindle';
    case PLAYSTATION = 'playstation';
    case XBOX = 'xbox';
    case WEBOS = 'webos';
    case OTHER = 'other';

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
            self::ANDROID => 'Android',
            self::WINDOWS => 'Windows',
            self::MACOS => 'MacOS',
            self::IOS => 'iOS',
            self::LINUX => 'Linux',
            self::CHROMEOS => 'Chrome OS',
            self::KINDLE => 'Kindle',
            self::PLAYSTATION => 'PlayStation',
            self::XBOX => 'Xbox',
            self::WEBOS => 'WebOS',
            self::OTHER => 'Other',
        };
    }

    /**
     * Get a human-readable translated label for the enum case.
     */
    public function translatedLabel(): string
    {
        return __('enums.OperationSystem.' . $this->name);
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
            ->filter(fn(self $case) => $case !== self::OTHER)
            ->map(fn(self $case) => [
                'value' => $case->label(),
                'label' => $case->label()
            ])
            ->values()
            ->all();
    }
}

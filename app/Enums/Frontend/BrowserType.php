<?php

namespace App\Enums\Frontend;

use App\Traits\Enum\EnumTrait;

enum BrowserType: string
{
    use EnumTrait;

    case BROWSER = 'Browser';
    case APPLICATION = 'Application';
    case BOT_CRAWLER = 'Bot/Crawler';
    case USERAGENT_ANONYMIZER = 'Useragent Anonymizer';
    case OFFLINE_BROWSER = 'Offline Browser';
    case MULTIMEDIA_PLAYER = 'Multimedia Player';
    case LIBRARY = 'Library';
    case FEED_READER = 'Feed Reader';
    case EMAIL_CLIENT = 'Email Client';
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
            self::BROWSER => 'Browser',
            self::APPLICATION => 'Application',
            self::BOT_CRAWLER => 'Bot/Crawler',
            self::USERAGENT_ANONYMIZER => 'Useragent Anonymizer',
            self::OFFLINE_BROWSER => 'Offline Browser',
            self::MULTIMEDIA_PLAYER => 'Multimedia Player',
            self::LIBRARY => 'Library',
            self::FEED_READER => 'Feed Reader',
            self::EMAIL_CLIENT => 'Email Client',
            self::UNKNOWN => 'Unknown',
        };
    }

    /**
     * Get a human-readable translated label for the enum case.
     */
    public function translatedLabel(): string
    {
        return __('enums.BrowserType.' . $this->name);
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
}

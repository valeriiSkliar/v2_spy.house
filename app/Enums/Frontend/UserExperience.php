<?php

namespace App\Enums\Frontend;

use App\Traits\Enum\EnumTrait;

enum UserExperience: string
{

    use EnumTrait;
    case NO_EXPERIENCE = 'No Experience';
    case BEGINNER = 'Beginner';
    case INTERMEDIATE = 'Intermediate';
    case ADVANCED = 'Advanced';
    case PROFESSIONAL = 'Professional';

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
     *
     * @return string
     */
    public function label(): string
    {
        // For now, the value itself is human-readable.
        // This can be customized later if needed, e.g., for localization.
        return $this->value;
    }

    /**
     * Get a human-readable translated label for the enum case.
     *
     * @return string
     */
    public function translatedLabel(): string
    {
        return __('enums.UserExperience.' . $this->name);
    }

    /**
     * Get all enum cases with their translated labels for a select list.
     *
     * @return array<string, string>
     */
    public static function getTranslatedList(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn(self $case) => [$case->value => $case->translatedLabel()])
            ->all();
    }
}

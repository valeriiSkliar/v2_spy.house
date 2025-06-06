<?php

namespace App\Enums\Frontend;

use App\Traits\Enum\EnumTrait;

enum UserScopeOfActivity: string
{
    use EnumTrait;

    case GAMBLING = 'Gambling';
    case BETTING = 'Betting';
    case CRYPTO = 'Crypto';
    case NUTRA = 'Nutra';
    case FINANCE = 'Finance';
    case DATING = 'Dating';
    case SWEEPSTAKES = 'Sweepstakes';
    case ECOMMERCE = 'E-commerce';
    case ADULT = 'Adult';
    case MOBILE_SUBSCRIPTIONS = 'Mobile subscriptions';
    case APPS_UTILITIES = 'Apps / Utilities';
    case GAMING = 'Gaming';
    case LOANS_MICROFINANCE = 'Loans / Microfinance';
    case INSURANCE = 'Insurance';
    case ANTIVIRUSES_CLEANERS = 'Antiviruses / Cleaners';
    case REAL_ESTATE = 'Real Estate';
    case EDUCATION = 'Education';
    case TRAVEL = 'Travel';

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
        return $this->value;
    }

    /**
     * Get a human-readable translated label for the enum case.
     */
    public function translatedLabel(): string
    {
        return __('enums.UserScopeOfActivity.'.$this->name);
    }

    /**
     * Get all enum cases with their translated labels for a select list.
     *
     * @return array<string, string>
     */
    public static function getTranslatedList(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $case) => [$case->name => $case->translatedLabel()])
            ->all();
    }
}

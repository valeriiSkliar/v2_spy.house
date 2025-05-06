<?php

namespace App\Enums\Frontend;

enum UserScopeOfActivity: string
{
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
     *
     * @return string
     */
    public function label(): string
    {
        return $this->value;
    }

    /**
     * Get a human-readable translated label for the enum case.
     *
     * @return string
     */
    public function translatedLabel(): string
    {
        return __('enums.UserScopeOfActivity.' . $this->name);
    }
}

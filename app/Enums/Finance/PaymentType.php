<?php

namespace App\Enums\Finance;

use App\Traits\Enum\EnumTrait;

enum PaymentType: string
{
    use EnumTrait;

    case DEPOSIT = 'DEPOSIT';
    case DIRECT_SUBSCRIPTION = 'DIRECT_SUBSCRIPTION';

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
            self::DEPOSIT => 'Deposit',
            self::DIRECT_SUBSCRIPTION => 'Direct Subscription',
        };
    }

    /**
     * Get a human-readable translated label for the enum case.
     */
    public function translatedLabel(): string
    {
        return __('enums.PaymentType.' . $this->name);
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

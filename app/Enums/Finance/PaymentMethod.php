<?php

namespace App\Enums\Finance;

use App\Traits\Enum\EnumTrait;

enum PaymentMethod: string
{
    use EnumTrait;

    case USDT = 'USDT';
    case PAY2_HOUSE = 'PAY2.HOUSE';
    case USER_BALANCE = 'USER_BALANCE';

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
            self::USDT => 'USDT',
            self::PAY2_HOUSE => 'Pay2.House',
            self::USER_BALANCE => 'User Balance',
        };
    }

    /**
     * Get a human-readable translated label for the enum case.
     */
    public function translatedLabel(): string
    {
        return __('enums.PaymentMethod.' . $this->name);
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
     * Get payment methods that are valid for deposit payments.
     *
     * @return array<self>
     */
    public static function getValidForDeposits(): array
    {
        return [
            self::USDT,
            self::PAY2_HOUSE,
        ];
    }

    /**
     * Check if this payment method is valid for deposits.
     */
    public function isValidForDeposits(): bool
    {
        return in_array($this, self::getValidForDeposits());
    }
}

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
     * Get HTML ID for the payment method.
     */
    public function htmlId(): string
    {
        return match ($this) {
            self::USDT => 'tether',
            self::PAY2_HOUSE => 'pay2',
            self::USER_BALANCE => 'user_balance',
        };
    }

    /**
     * Get image path for the payment method.
     */
    public function imagePath(): string
    {
        return match ($this) {
            self::USDT => '/img/pay/tether.svg',
            self::PAY2_HOUSE => '/img/pay/pay2.svg',
            self::USER_BALANCE => '/img/pay/user.svg',
        };
    }

    /**
     * Get payment methods formatted for frontend components.
     *
     * @return array<array<string, mixed>>
     */
    public static function getForFrontend(): array
    {
        return collect(self::cases())
            ->map(fn(self $case) => [
                'name' => $case->translatedLabel(),
                'id' => $case->htmlId(),
                'value' => $case->value,
                'img' => $case->imagePath(),
            ])
            ->all();
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

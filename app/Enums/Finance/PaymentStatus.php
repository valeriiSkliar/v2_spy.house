<?php

namespace App\Enums\Finance;

use App\Traits\Enum\EnumTrait;

enum PaymentStatus: string
{
    use EnumTrait;

    case PENDING = 'PENDING';
    case SUCCESS = 'SUCCESS';
    case FAILED = 'FAILED';

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
            self::PENDING => 'Pending',
            self::SUCCESS => 'Success',
            self::FAILED => 'Failed',
        };
    }

    /**
     * Get a human-readable translated label for the enum case.
     */
    public function translatedLabel(): string
    {
        return __('enums.PaymentStatus.' . $this->name);
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
     * Check if payment is successful
     */
    public function isSuccessful(): bool
    {
        return $this === self::SUCCESS;
    }

    /**
     * Check if payment is pending
     */
    public function isPending(): bool
    {
        return $this === self::PENDING;
    }

    /**
     * Check if payment is failed
     */
    public function isFailed(): bool
    {
        return $this === self::FAILED;
    }
}

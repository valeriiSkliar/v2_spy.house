<?php

namespace App\Finance\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Subscription extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'amount',
        'early_discount',
        'api_request_count',
        'search_request_count',
        'status',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'amount' => 'float',
            'early_discount' => 'float',
            'api_request_count' => 'integer',
            'search_request_count' => 'integer',
        ];
    }

    /**
     * Check if subscription is active
     */
    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    /**
     * Get formatted amount with currency
     */
    public function getFormattedAmount(): string
    {
        return '$' . number_format($this->amount, 2);
    }

    /**
     * Get early discount amount
     */
    public function getDiscountedAmount(): float
    {
        if (!$this->early_discount) {
            return $this->amount;
        }

        return $this->amount * (1 - $this->early_discount / 100);
    }

    /**
     * Get formatted discounted amount
     */
    public function getFormattedDiscountedAmount(): string
    {
        return '$' . number_format($this->getDiscountedAmount(), 2);
    }

    /**
     * Get all payments for this subscription.
     */
    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    /**
     * Get successful payments for this subscription.
     */
    public function successfulPayments(): HasMany
    {
        return $this->payments()->successful();
    }

    /**
     * Get yearly amount with discount
     */
    public function getYearlyAmount(): float
    {
        return $this->amount * 12 * (1 - $this->early_discount / 100);
    }

    /**
     * Get formatted yearly amount
     */
    public function getFormattedYearlyAmount(): string
    {
        return '$' . number_format($this->getYearlyAmount(), 2);
    }

    /**
     * Get amount by billing type
     */
    public function getAmountByBillingType(string $billingType): float
    {
        return $billingType === 'year' ? $this->getYearlyAmount() : $this->amount;
    }

    /**
     * Get formatted amount by billing type
     */
    public function getFormattedAmountByBillingType(string $billingType): string
    {
        return '$' . number_format($this->getAmountByBillingType($billingType), 2);
    }

    /**
     * Get billing period name
     */
    public function getBillingPeriodName(string $billingType): string
    {
        return $billingType === 'year' ? 'год' : 'месяц';
    }

    /**
     * Get tariff priority for upgrade/downgrade logic
     * Higher value = higher tier tariff
     */
    public function getTariffPriority(): int
    {
        $priorities = [
            'Free' => 0,
            'Start' => 1,
            'Basic' => 2,
            'Premium' => 3,
            'Enterprise' => 4,
        ];

        return $priorities[$this->name] ?? 0;
    }

    /**
     * Check if this subscription is higher tier than another
     */
    public function isHigherTierThan(Subscription $other): bool
    {
        return $this->getTariffPriority() > $other->getTariffPriority();
    }

    /**
     * Check if this subscription is lower tier than another
     */
    public function isLowerTierThan(Subscription $other): bool
    {
        return $this->getTariffPriority() < $other->getTariffPriority();
    }

    /**
     * Check if this is Enterprise subscription
     */
    public function isEnterprise(): bool
    {
        return $this->name === 'Enterprise';
    }

    /**
     * Get time compensation ratio when upgrading from another subscription
     * Based on price differences
     */
    public function getTimeCompensationRatio(Subscription $fromSubscription): float
    {
        if ($fromSubscription->amount <= 0) {
            return 0.0;
        }

        return $fromSubscription->amount / $this->amount;
    }
}

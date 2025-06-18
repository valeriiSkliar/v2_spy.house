<?php

namespace App\Finance\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

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
     * Get subscription slug (lowercase name for URL)
     */
    public function getSlug(): string
    {
        return strtolower($this->name);
    }

    /**
     * Find subscription by slug
     */
    public static function findBySlug(string $slug): ?self
    {
        return self::where(DB::raw('LOWER(name)'), strtolower($slug))
            ->where('status', 'active')
            ->first();
    }

    /**
     * Get payment URL for this subscription
     */
    public function getPaymentUrl(string $billingType = 'month'): string
    {
        return route('tariffs.payment', [
            'slug' => $this->getSlug(),
            'billingType' => $billingType
        ]);
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
        if (! $this->early_discount) {
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

        $priority = $priorities[$this->name] ?? 0;

        Log::debug('Subscription tariff priority calculation', [
            'subscription_id' => $this->id,
            'subscription_name' => $this->name,
            'calculated_priority' => $priority,
            'available_priorities' => $priorities,
        ]);

        return $priority;
    }

    /**
     * Check if this subscription is higher tier than another
     */
    public function isHigherTierThan(Subscription $other): bool
    {
        $thisPriority = $this->getTariffPriority();
        $otherPriority = $other->getTariffPriority();
        $result = $thisPriority > $otherPriority;

        Log::debug('Checking if subscription is higher tier', [
            'current_subscription' => [
                'id' => $this->id,
                'name' => $this->name,
                'priority' => $thisPriority,
            ],
            'compared_subscription' => [
                'id' => $other->id,
                'name' => $other->name,
                'priority' => $otherPriority,
            ],
            'is_higher_tier' => $result,
        ]);

        return $result;
    }

    /**
     * Check if this subscription is lower tier than another
     */
    public function isLowerTierThan(Subscription $other): bool
    {
        $thisPriority = $this->getTariffPriority();
        $otherPriority = $other->getTariffPriority();
        $result = $thisPriority < $otherPriority;

        Log::debug('Checking if subscription is lower tier', [
            'current_subscription' => [
                'id' => $this->id,
                'name' => $this->name,
                'priority' => $thisPriority,
            ],
            'compared_subscription' => [
                'id' => $other->id,
                'name' => $other->name,
                'priority' => $otherPriority,
            ],
            'is_lower_tier' => $result,
        ]);

        return $result;
    }

    /**
     * Check if this is Enterprise subscription
     */
    public static function isEnterpriseSubscription(string $subscriptionName): bool
    {
        return $subscriptionName === 'Enterprise';
    }

    /**
     * Check if this is Enterprise subscription
     */
    public function isEnterprise(): bool
    {
        return $this->name === 'Enterprise';
    }

    /**
     * Check if this is Premium subscription
     */
    public static function isPremiumSubscription(string $subscriptionName): bool
    {
        return $subscriptionName === 'Premium';
    }

    /**
     * Check if this is Premium subscription
     */
    public function isPremium(): bool
    {
        return $this->name === 'Premium';
    }

    /**
     * Check if promo banner should be shown for this subscription
     * Show promo for all subscriptions except Enterprise and Premium
     */
    public static function shouldShowPromoForSubscription(string $subscriptionName): bool
    {
        return !self::isEnterpriseSubscription($subscriptionName) &&
            !self::isPremiumSubscription($subscriptionName);
    }

    /**
     * Check if promo banner should be shown for this subscription instance
     */
    public function shouldShowPromo(): bool
    {
        return !$this->isEnterprise() && !$this->isPremium();
    }

    /**
     * Get time compensation ratio when upgrading from another subscription
     * Based on improved stepped system with minimum 30% compensation
     */
    public function getTimeCompensationRatio(Subscription $fromSubscription): float
    {
        if ($fromSubscription->amount <= 0) {
            Log::warning('Time compensation calculation with zero or negative amount', [
                'from_subscription_id' => $fromSubscription->id,
                'from_subscription_name' => $fromSubscription->name,
                'from_subscription_amount' => $fromSubscription->amount,
                'to_subscription_id' => $this->id,
                'to_subscription_name' => $this->name,
                'to_subscription_amount' => $this->amount,
            ]);

            return 0.0;
        }

        // Базовый коэффициент (пропорциональный)
        $baseRatio = $fromSubscription->amount / $this->amount;

        // Ступенчатая система с минимумом 30%
        $minCompensationRatio = 0.30;

        // Разница в цене
        $priceDifference = $this->amount / $fromSubscription->amount;

        if ($priceDifference > 50) {
            // Если новый тариф дороже в 50+ раз, даем 50% компенсации
            $compensationRatio = 0.5;
        } elseif ($priceDifference > 20) {
            // Если дороже в 20+ раз, даем 40% компенсации
            $compensationRatio = 0.4;
        } elseif ($priceDifference > 10) {
            // Если дороже в 10+ раз, даем 35% компенсации
            $compensationRatio = 0.35;
        } elseif ($priceDifference > 5) {
            // Если дороже в 5+ раз, даем 30% компенсации
            $compensationRatio = 0.3;
        } else {
            // Для остальных случаев - минимум 30% или базовый коэффициент (что больше)
            $compensationRatio = max($minCompensationRatio, $baseRatio);
        }

        Log::info('Time compensation ratio calculated with stepped system', [
            'from_subscription' => [
                'id' => $fromSubscription->id,
                'name' => $fromSubscription->name,
                'amount' => $fromSubscription->amount,
            ],
            'to_subscription' => [
                'id' => $this->id,
                'name' => $this->name,
                'amount' => $this->amount,
            ],
            'price_difference_ratio' => round($priceDifference, 2),
            'base_compensation_ratio' => round($baseRatio, 4),
            'stepped_compensation_ratio' => round($compensationRatio, 4),
            'calculation' => sprintf('Price diff: %.2f, Base: %.4f, Final: %.4f', $priceDifference, $baseRatio, $compensationRatio),
        ]);

        return $compensationRatio;
    }

    /**
     * Calculate time compensation in seconds for upgrade/downgrade
     */
    public function calculateTimeCompensation(Subscription $fromSubscription, int $timeLeftSeconds): int
    {
        $ratio = $this->getTimeCompensationRatio($fromSubscription);
        $compensatedTime = (int) ($timeLeftSeconds * $ratio);

        Log::info('Time compensation calculation details', [
            'time_left_seconds' => $timeLeftSeconds,
            'time_left_days' => round($timeLeftSeconds / 86400, 2),
            'compensation_ratio' => $ratio,
            'compensated_time_seconds' => $compensatedTime,
            'compensated_time_days' => round($compensatedTime / 86400, 2),
            'from_subscription' => [
                'id' => $fromSubscription->id,
                'name' => $fromSubscription->name,
                'amount' => $fromSubscription->amount,
            ],
            'to_subscription' => [
                'id' => $this->id,
                'name' => $this->name,
                'amount' => $this->amount,
            ],
        ]);

        return $compensatedTime;
    }
}

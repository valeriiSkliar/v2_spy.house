<?php

namespace App\Finance\Models;

use App\Enums\Finance\PaymentMethod;
use App\Enums\Finance\PaymentStatus;
use App\Enums\Finance\PaymentType;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class Payment extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'user_id',
        'amount',
        'payment_type',
        'subscription_id',
        'payment_method',
        'transaction_number',
        'promocode_id',
        'status',
        'webhook_token',
        'webhook_processed_at',
        'idempotency_key',
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
            'payment_type' => PaymentType::class,
            'payment_method' => PaymentMethod::class,
            'status' => PaymentStatus::class,
            'webhook_processed_at' => 'datetime',
        ];
    }

    /**
     * Boot the model
     */
    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (Payment $payment) {
            // Validate that deposit payments can only use valid payment methods
            if (
                $payment->payment_type === PaymentType::DEPOSIT &&
                !$payment->payment_method->isValidForDeposits()
            ) {
                throw new \InvalidArgumentException(
                    'Deposit payments can only use USDT or PAY2_HOUSE payment methods.'
                );
            }

            // Generate webhook token if not provided
            if (empty($payment->webhook_token)) {
                $payment->webhook_token = Str::random(64);
            }

            // Generate idempotency key if not provided
            if (empty($payment->idempotency_key)) {
                $payment->idempotency_key = Str::uuid();
            }
        });
    }

    /**
     * Get the user that owns the payment.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the subscription associated with the payment.
     */
    public function subscription(): BelongsTo
    {
        return $this->belongsTo(Subscription::class);
    }

    /**
     * Get the promocode associated with the payment.
     */
    public function promocode(): BelongsTo
    {
        return $this->belongsTo(Promocode::class, 'promocode_id');
    }



    /**
     * Check if payment is successful
     */
    public function isSuccessful(): bool
    {
        return $this->status->isSuccessful();
    }

    /**
     * Check if payment is pending
     */
    public function isPending(): bool
    {
        return $this->status->isPending();
    }

    /**
     * Check if payment is failed
     */
    public function isFailed(): bool
    {
        return $this->status->isFailed();
    }

    /**
     * Check if payment is for deposit
     */
    public function isDeposit(): bool
    {
        return $this->payment_type === PaymentType::DEPOSIT;
    }

    /**
     * Check if payment is for direct subscription
     */
    public function isDirectSubscription(): bool
    {
        return $this->payment_type === PaymentType::DIRECT_SUBSCRIPTION;
    }

    /**
     * Get formatted amount with currency
     */
    public function getFormattedAmount(): string
    {
        return '$' . number_format($this->amount, 2);
    }

    /**
     * Mark payment as successful
     */
    public function markAsSuccessful(): bool
    {
        $this->status = PaymentStatus::SUCCESS;
        $this->webhook_processed_at = now();

        return $this->save();
    }

    /**
     * Mark payment as failed
     */
    public function markAsFailed(): bool
    {
        $this->status = PaymentStatus::FAILED;
        $this->webhook_processed_at = now();

        return $this->save();
    }

    /**
     * Scope for successful payments
     */
    public function scopeSuccessful($query)
    {
        return $query->where('status', PaymentStatus::SUCCESS);
    }

    /**
     * Scope for pending payments
     */
    public function scopePending($query)
    {
        return $query->where('status', PaymentStatus::PENDING);
    }

    /**
     * Scope for failed payments
     */
    public function scopeFailed($query)
    {
        return $query->where('status', PaymentStatus::FAILED);
    }

    /**
     * Scope for deposit payments
     */
    public function scopeDeposits($query)
    {
        return $query->where('payment_type', PaymentType::DEPOSIT);
    }

    /**
     * Scope for subscription payments
     */
    public function scopeSubscriptions($query)
    {
        return $query->where('payment_type', PaymentType::DIRECT_SUBSCRIPTION);
    }

    /**
     * Scope to exclude payments with free subscriptions (amount = 0)
     */
    public function scopeExcludingFreeSubscriptions($query)
    {
        return $query->whereHas('subscription', function ($subQuery) {
            $subQuery->where('amount', '>', 0);
        });
    }

    /**
     * Scope for paid subscription payments only
     */
    public function scopePaidSubscriptionsOnly($query)
    {
        return $query->subscriptions()->excludingFreeSubscriptions();
    }
}

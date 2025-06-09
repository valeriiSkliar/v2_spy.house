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
}

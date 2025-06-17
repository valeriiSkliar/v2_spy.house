<?php

namespace App\Finance\Models;

use App\Enums\Finance\PromocodeStatus;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Promocode extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'promocode',
        'discount',
        'status',
        'date_start',
        'date_end',
        'count_activation',
        'max_per_user',
        'created_by_user_id',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'discount' => 'decimal:2',
            'status' => PromocodeStatus::class,
            'date_start' => 'datetime',
            'date_end' => 'datetime',
        ];
    }

    /**
     * Get the user who created this promocode.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    /**
     * Get all activations for this promocode.
     */
    public function activations(): HasMany
    {
        return $this->hasMany(PromocodeActivation::class);
    }

    /**
     * Get payments that used this promocode.
     */
    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class, 'promocode_id');
    }

    /**
     * Check if promocode is currently valid
     */
    public function isValid(): bool
    {
        if (! $this->status->isUsable()) {
            return false;
        }

        $now = now();

        // Check date validity
        if ($this->date_start && $now->isBefore($this->date_start)) {
            return false;
        }

        if ($this->date_end && $now->isAfter($this->date_end)) {
            return false;
        }

        return true;
    }

    /**
     * Check if user can use this promocode
     */
    public function canBeUsedByUser(int $userId): bool
    {
        if (! $this->isValid()) {
            return false;
        }

        $userActivationsCount = $this->activations()
            ->where('user_id', $userId)
            ->count();

        return $userActivationsCount < $this->max_per_user;
    }

    /**
     * Calculate discount amount for given price
     */
    public function calculateDiscountAmount(float $amount): float
    {
        return round(($amount * $this->discount) / 100, 2);
    }

    /**
     * Calculate final amount after discount
     */
    public function calculateFinalAmount(float $amount): float
    {
        return round($amount - $this->calculateDiscountAmount($amount), 2);
    }

    /**
     * Activate promocode for user
     */
    public function activateForUser(int $userId, string $ipAddress, string $userAgent, ?int $paymentId = null): PromocodeActivation
    {
        $activation = new PromocodeActivation([
            'promocode_id' => $this->id,
            'user_id' => $userId,
            'payment_id' => $paymentId,
            'ip_address' => $ipAddress,
            'user_agent' => $userAgent,
        ]);

        $activation->save();

        // Update activation count
        $this->increment('count_activation');

        return $activation;
    }

    /**
     * Scope for active promocodes
     */
    public function scopeActive($query)
    {
        return $query->where('status', PromocodeStatus::ACTIVE);
    }

    /**
     * Scope for valid promocodes (active and within date range)
     */
    public function scopeValid($query)
    {
        $now = now();

        return $query->where('status', PromocodeStatus::ACTIVE)
            ->where(function ($q) use ($now) {
                $q->whereNull('date_start')
                    ->orWhere('date_start', '<=', $now);
            })
            ->where(function ($q) use ($now) {
                $q->whereNull('date_end')
                    ->orWhere('date_end', '>=', $now);
            });
    }

    /**
     * Find promocode by code
     */
    public static function findByCode(string $code): ?self
    {
        return self::where('promocode', $code)->first();
    }

    /**
     * Generate unique promocode
     */
    public static function generateUniqueCode(int $length = 6): string
    {
        do {
            $code = strtoupper(substr(str_shuffle('ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789'), 0, $length));
        } while (self::where('promocode', $code)->exists());

        return $code;
    }
}

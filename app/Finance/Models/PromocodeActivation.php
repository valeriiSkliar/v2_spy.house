<?php

namespace App\Finance\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PromocodeActivation extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'promocode_id',
        'user_id',
        'payment_id',
        'ip_address',
        'user_agent',
    ];

    /**
     * Get the promocode that was activated.
     */
    public function promocode(): BelongsTo
    {
        return $this->belongsTo(Promocode::class);
    }

    /**
     * Get the user who activated the promocode.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the payment associated with this activation.
     */
    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payment::class);
    }

    /**
     * Scope for activations by user
     */
    public function scopeByUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope for activations by promocode
     */
    public function scopeByPromocode($query, int $promocodeId)
    {
        return $query->where('promocode_id', $promocodeId);
    }

    /**
     * Scope for activations with payments
     */
    public function scopeWithPayments($query)
    {
        return $query->whereNotNull('payment_id');
    }
}

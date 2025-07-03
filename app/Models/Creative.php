<?php

namespace App\Models;

use App\Enums\Frontend\AdvertisingFormat;
use App\Enums\Frontend\AdvertisingStatus;
use App\Enums\Frontend\OperationSystem;
use App\Models\Browser;
use App\Models\Frontend\IsoEntity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Creative extends Model
{
    /** @use HasFactory<\Database\Factories\CreativeFactory> */
    use HasFactory, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'format',
        'status',
        'country_id',
        'language_id',
        'browser_id',
        'operation_system',
        'advertisment_network_id',
        'is_adult',
        'external_id',
        'title',
        'description',
        'combined_hash',
        'landing_url',
        'last_seen_at',
    ];

    /**
     * Get the attributes that should be cast.
     */
    protected function casts(): array
    {
        return [
            'format' => AdvertisingFormat::class,
            'status' => AdvertisingStatus::class,
            'operation_system' => OperationSystem::class,
            'is_adult' => 'boolean',
            'external_id' => 'integer',
            'last_seen_at' => 'datetime',
        ];
    }

    /**
     * Связь с страной (ISO сущность типа 'country')
     */
    public function country(): BelongsTo
    {
        return $this->belongsTo(IsoEntity::class, 'country_id')
            ->where('type', 'country');
    }

    /**
     * Связь с языком (ISO сущность типа 'language')
     */
    public function language(): BelongsTo
    {
        return $this->belongsTo(IsoEntity::class, 'language_id')
            ->where('type', 'language');
    }

    /**
     * Связь с браузером
     */
    public function browser(): BelongsTo
    {
        return $this->belongsTo(Browser::class, 'browser_id');
    }

    /**
     * Связь с рекламной сетью
     */
    public function advertismentNetwork(): BelongsTo
    {
        return $this->belongsTo(AdvertismentNetwork::class, 'advertisment_network_id');
    }
}

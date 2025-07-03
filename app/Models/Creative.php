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

class Creative extends Model
{
    /** @use HasFactory<\Database\Factories\CreativeFactory> */
    use HasFactory;

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
}

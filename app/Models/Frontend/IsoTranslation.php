<?php

namespace App\Models\Frontend;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class IsoTranslation extends Model
{
    protected $fillable = [
        'entity_id',
        'language_code',
        'translated_name',
    ];

    /**
     * Отношение к ISO сущности
     */
    public function entity(): BelongsTo
    {
        return $this->belongsTo(IsoEntity::class, 'entity_id');
    }
}

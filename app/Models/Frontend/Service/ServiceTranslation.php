<?php

namespace App\Models\Frontend\Service;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ServiceTranslation extends Model
{
    protected $fillable = [
        'service_id',
        'language_code',
        'name',
        'description',
        'article',
    ];

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }
}

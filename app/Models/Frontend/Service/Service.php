<?php

namespace App\Models\Frontend\Service;

use App\Models\Frontend\Rating;
use App\Models\Frontend\Service\ServiceCategories;
use Database\Factories\Frontend\Service\ServiceFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\URL;
use Spatie\Translatable\HasTranslations;

class Service extends Model
{
    use HasFactory;
    use HasTranslations;

    /**
     * Create a new factory instance for the model.
     *
     * @return \Illuminate\Database\Eloquent\Factories\Factory
     */
    protected static function newFactory()
    {
        return ServiceFactory::new();
    }

    public array $translatable = ['name', 'description', 'code_description'];

    protected $fillable = [
        'name',
        'description',
        'logo',
        'url',
        'redirect_url',
        'status',
        'category_id',
        'views',
        'reviews_count',
        'rating',
        'code',
        'code_description',
        'code_valid_from',
        'code_valid_until',
        'is_active_code',
        'is_pinned',
        'pinned_until'
    ];

    protected $casts = [
        'name' => 'array',
        'description' => 'array',
        'views' => 'integer',
        'reviews_count' => 'integer',
        'rating' => 'float',
        'is_active_code' => 'boolean',
        'code_valid_from' => 'datetime',
        'code_valid_until' => 'datetime',
        'is_pinned' => 'boolean',
        'pinned_until' => 'datetime'
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($service) {
            $service->redirect_url = URL::to('/services/redirect/' . ($service->id ?? 0));
        });

        static::created(function ($service) {
            // Update the redirect_url with the actual ID after creation
            $service->redirect_url = URL::to('/services/redirect/' . $service->id);
            $service->save();
        });
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(ServiceCategories::class, 'category_id');
    }

    public function ratings(): HasMany
    {
        return $this->hasMany(Rating::class);
    }
}

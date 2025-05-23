<?php

namespace App\Models\Frontend\Service;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Translatable\HasTranslations;

class ServiceCategory extends Model
{
    use HasFactory;
    use HasTranslations;

    protected $fillable = ['slug'];

    public $translatable = ['name', 'description'];

    public function translations(): HasMany
    {
        return $this->hasMany(ServiceCategoryTranslation::class);
    }

    public function services(): HasMany
    {
        return $this->hasMany(Service::class, 'category_id');
    }

    public function translate(?string $languageCode = null): ?ServiceCategoryTranslation
    {
        $languageCode = $languageCode ?? app()->getLocale();

        return $this->translations()->where('language_code', $languageCode)->first();
    }
}

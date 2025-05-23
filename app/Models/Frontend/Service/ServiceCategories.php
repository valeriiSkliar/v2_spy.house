<?php

namespace App\Models\Frontend\Service;

use Database\Factories\Frontend\Service\ServiceCategoriesFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Translatable\HasTranslations;

class ServiceCategories extends Model
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
        return ServiceCategoriesFactory::new();
    }

    public $translatable = ['name', 'description'];

    protected $table = 'service_categories';

    protected $fillable = [
        'name',
        'description',
        'slug',
        'group_name',
        'image',
        'status',
    ];

    protected $casts = [
        'name' => 'array',
        'description' => 'array',
    ];

    public function services()
    {
        return $this->hasMany(Service::class, 'category_id');
    }
}

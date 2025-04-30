<?php

namespace App\Models\Frontend\Service;

use App\Models\Frontend\Service\Service;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Translatable\HasTranslations;
use Database\Factories\Frontend\Service\ServiceCategoriesFactory;

class ServiceCategories extends Model
{
    use HasTranslations;
    use HasFactory;

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
        'status'
    ];

    protected $casts = [
        'name' => 'array',
        'description' => 'array'
    ];

    public function services()
    {
        return $this->hasMany(Service::class, 'category_id');
    }
}

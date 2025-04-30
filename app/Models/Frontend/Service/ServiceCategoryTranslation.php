<?php

namespace App\Models\Frontend\Service;

use Illuminate\Database\Eloquent\Model;

class ServiceCategoryTranslation extends Model
{
    protected $fillable = ['service_category_id', 'language_code', 'name'];

    public function category()
    {
        return $this->belongsTo(ServiceCategory::class, 'service_category_id');
    }
}

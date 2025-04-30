<?php

namespace App\Models\Blog;

use App\Models\SEO\SeoMeta;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Tag extends Model
{
    use HasFactory;

    public function seo(): \Illuminate\Database\Eloquent\Relations\MorphOne
    {
        return $this->morphOne(SeoMeta::class, 'seoable');
    }
}

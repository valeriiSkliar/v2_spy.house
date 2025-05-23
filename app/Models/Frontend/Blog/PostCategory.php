<?php

namespace App\Models\Frontend\Blog;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Kalnoy\Nestedset\NodeTrait;
use Spatie\Translatable\HasTranslations;

class PostCategory extends Model
{
    use HasFactory;
    use HasTranslations;
    use NodeTrait;

    public $translatable = ['name'];

    protected $fillable = ['name', 'slug', 'parent_id', 'posts_count'];

    protected $casts = [
        'name' => 'json',
    ];

    protected $appends = ['name_en', 'name_ru'];

    // public function getNameAttribute($value)
    // {
    //     return $this->getTranslation('name', app()->getLocale());
    // }

    public function getNameEnAttribute()
    {
        return $this->name['en'] ?? '';
    }

    public function getNameRuAttribute()
    {
        return $this->name['ru'] ?? '';
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(PostCategory::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(PostCategory::class, 'parent_id');
    }

    public function posts(): BelongsToMany
    {
        return $this->belongsToMany(BlogPost::class, 'blog_post_categories', 'category_id', 'post_id');
    }

    /**
     * Increment posts count
     */
    public function incrementPostsCount(): void
    {
        $this->increment('posts_count');
        if ($this->parent_id) {
            $this->parent->incrementPostsCount();
        }
    }

    /**
     * Decrement posts count
     */
    public function decrementPostsCount(): void
    {
        $this->decrement('posts_count');
        if ($this->parent_id) {
            $this->parent->decrementPostsCount();
        }
    }
}

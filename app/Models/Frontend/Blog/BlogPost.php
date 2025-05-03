<?php

namespace App\Models\Frontend\Blog;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Translatable\HasTranslations;
use App\Models\Frontend\Rating;

class BlogPost extends Model
{
    /** @use HasFactory<\Database\Factories\Blog\BlogPostFactory> */
    use HasFactory;
    use HasTranslations;
    protected $fillable = [
        'title',
        'content',
        'summary',
        'slug',
        'views_count',
        'author_id',
        'featured_image',
        'is_published',
        'average_rating'
    ];

    public $translatable = [
        'title',
        'content',
        'summary',
        'is_published' => 'boolean',
        'average_rating' => 'float'
    ];

    protected $casts = [
        'title' => 'json',
        'content' => 'json',
        'summary' => 'json',
        'is_published' => 'boolean'
    ];

    /**
     * Get the route key name for Laravel's route model binding.
     */
    public function getRouteKeyName()
    {
        return 'id';
    }

    /**
     * Get the author of the post
     */
    public function author(): BelongsTo
    {
        return $this->belongsTo(Author::class);
    }

    /**
     * Get the categories for the post
     */
    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(PostCategory::class, 'blog_post_categories', 'post_id', 'category_id');
    }

    /**
     * Increment the view count
     */
    public function incrementViewCount(): void
    {
        $this->increment('views_count');
    }

    public function scopePopular(Builder $query): Builder
    {
        return $query->orderBy('views_count', 'desc');
    }

    public function comments(): HasMany
    {
        return $this->hasMany(BlogComment::class, 'post_id');
    }

    /**
     * Get the ratings for the blog post.
     */
    public function ratings(): HasMany
    {
        return $this->hasMany(Rating::class, 'blog_id');
    }

    /**
     * Calculate the average rating for the blog post.
     */
    public function averageRating(): ?float
    {
        // Use the relationship to calculate the average
        // Cast the result to float for consistency
        return (float) $this->ratings()->avg('rating');
    }

    public function relatedPosts(): BelongsToMany
    {
        return $this->belongsToMany(BlogPost::class, 'related_posts', 'post_id', 'related_post_id')
            ->withPivot('sort_order')
            ->orderBy('sort_order');
    }
}

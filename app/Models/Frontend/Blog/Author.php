<?php

namespace App\Models\Frontend\Blog;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Translatable\HasTranslations;

class Author extends Model
{
    /** @use HasFactory<\Database\Factories\Blog\AuthorFactory> */
    use HasFactory;

    use HasTranslations;

    public $translatable = ['name', 'bio'];

    protected $fillable = ['name', 'avatar', 'bio', 'is_default'];

    protected $casts = [
        'is_default' => 'boolean',
    ];

    public function posts(): HasMany
    {
        return $this->hasMany(BlogPost::class);
    }

    public static function getDefault()
    {
        return static::where('is_default', true)->first();
    }

    protected static function boot()
    {
        parent::boot();

        static::saving(function ($author) {
            if ($author->is_default) {
                // Remove default status from other authors
                static::where('id', '!=', $author->id)
                    ->where('is_default', true)
                    ->update(['is_default' => false]);
            }
        });
    }
}

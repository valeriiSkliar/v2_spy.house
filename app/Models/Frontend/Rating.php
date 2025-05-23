<?php

namespace App\Models\Frontend;

use App\Models\Frontend\Blog\BlogPost;
use App\Models\Frontend\Service\Service;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Rating extends Model
{
    /** @use HasFactory<\Database\Factories\RatingFactory> */
    use HasFactory;

    protected $table = 'ratings';

    protected $fillable = [
        'user_id',
        'service_id',
        'blog_id',
        'rating',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function blog(): BelongsTo
    {
        return $this->belongsTo(BlogPost::class, 'blog_id');
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class, 'service_id');
    }

    public function scopeByUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeByBlog($query, $blogId)
    {
        return $query->where('blog_id', $blogId);
    }

    public function scopeByService($query, $serviceId)
    {
        return $query->where('service_id', $serviceId);
    }

    public function averageRating($blogId)
    {
        return $this->where('blog_id', $blogId)->avg('rating');
    }

    public function averageRatingForService($serviceId)
    {
        return $this->where('service_id', $serviceId)->avg('rating');
    }
}

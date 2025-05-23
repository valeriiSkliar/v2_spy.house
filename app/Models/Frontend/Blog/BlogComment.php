<?php

namespace App\Models\Frontend\Blog;

use App\Enums\Frontend\CommentStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BlogComment extends Model
{
    /** @use HasFactory<\Database\Factories\Blog\BlogCommentFactory> */
    use HasFactory;

    protected $fillable = [
        'post_id',
        'parent_id',
        'author_name',
        'email',
        'avatar',
        'content',
        'rating',
        'is_spam',
        'status',
    ];

    protected $casts = [
        'is_spam' => 'boolean',
        'status' => CommentStatus::class,
        'rating' => 'integer',
    ];

    protected $with = ['replies'];

    public function post(): BelongsTo
    {
        return $this->belongsTo(BlogPost::class, 'post_id');
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(BlogComment::class, 'parent_id');
    }

    public function replies(): HasMany
    {
        return $this->hasMany(BlogComment::class, 'parent_id')
            ->where('status', CommentStatus::APPROVED->value);
    }

    public function isApproved(): bool
    {
        return $this->status === CommentStatus::APPROVED;
    }

    public function isPending(): bool
    {
        return $this->status === CommentStatus::PENDING;
    }

    public function isRejected(): bool
    {
        return $this->status === CommentStatus::REJECTED;
    }
}

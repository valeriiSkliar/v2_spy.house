<?php

namespace App\Models\Frontend;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

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
}

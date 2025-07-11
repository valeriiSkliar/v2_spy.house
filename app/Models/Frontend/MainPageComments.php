<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Translatable\HasTranslations;

class MainPageComments extends Model
{
    /** @use HasFactory<\Database\Factories\MainPageCommentsFactory> */
    use HasFactory, SoftDeletes, HasTranslations;

    /**
     * The attributes that are translatable.
     *
     * @var array
     */
    public $translatable = [
        'heading',
        'user_position',
        'user_name',
        'text',
        'content'
    ];

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'heading',
        'user_position',
        'user_name',
        'thumbnail_src',
        'email',
        'text',
        'content',
        'is_active',
        'display_order'
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'is_active' => 'boolean',
        'display_order' => 'integer',
        'heading' => 'array',
        'user_position' => 'array',
        'user_name' => 'array',
        'text' => 'array',
        'content' => 'array'
    ];
}

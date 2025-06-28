<?php

namespace App\Models\Frontend\Service;

use Illuminate\Database\Eloquent\Model;

class Language extends Model
{
    protected $fillable = ['code', 'name', 'is_active'];
}

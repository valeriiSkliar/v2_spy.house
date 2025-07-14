<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AdSource extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'source_name',
        'source_display_name',
        'parser_status',
        'parser_state',
        'parser_last_error',
        'parser_last_error_at',
        'parser_last_error_code',
        'parser_last_error_message',
        'parser_last_error_trace',
        'parser_last_error_file',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'parser_state' => 'array',
        'parser_last_error' => 'array',
        'parser_last_error_at' => 'datetime',
    ];

    /**
     * Получить источник по системному имени
     */
    public static function findBySourceName(string $sourceName): ?self
    {
        return self::where('source_name', $sourceName)->first();
    }

    /**
     * Получить все активные источники
     */
    public static function getActive(): \Illuminate\Database\Eloquent\Collection
    {
        return self::orderBy('source_display_name')->get();
    }

    /**
     * Проверить существование источника
     */
    public static function exists(string $sourceName): bool
    {
        return self::where('source_name', $sourceName)->exists();
    }
}

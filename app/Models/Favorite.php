<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Favorite extends Model
{
    use HasFactory;

    /**
     * Поля, которые можно массово заполнять
     */
    protected $fillable = [
        'user_id',
        'creative_id',
    ];

    /**
     * Поля, которые должны быть приведены к определенным типам
     */
    protected $casts = [
        'user_id' => 'integer',
        'creative_id' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Связь с пользователем
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Связь с креативом
     */
    public function creative(): BelongsTo
    {
        return $this->belongsTo(Creative::class);
    }

    /**
     * Проверить, есть ли креатив в избранном у пользователя
     */
    public static function isFavorite(int $userId, int $creativeId): bool
    {
        return self::where('user_id', $userId)
            ->where('creative_id', $creativeId)
            ->exists();
    }

    /**
     * Добавить креатив в избранное
     */
    public static function addToFavorites(int $userId, int $creativeId): self
    {
        return self::firstOrCreate([
            'user_id' => $userId,
            'creative_id' => $creativeId,
        ]);
    }

    /**
     * Удалить креатив из избранного
     */
    public static function removeFromFavorites(int $userId, int $creativeId): bool
    {
        return self::where('user_id', $userId)
            ->where('creative_id', $creativeId)
            ->delete() > 0;
    }

    /**
     * Получить количество избранных креативов для пользователя
     */
    public static function getFavoritesCount(int $userId): int
    {
        return self::where('user_id', $userId)->count();
    }
}

<?php

namespace App\Models\Frontend;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Cache;

class IsoTranslation extends Model
{
    protected $fillable = [
        'entity_id',
        'language_code',
        'translated_name',
    ];

    /**
     * Отношение к ISO сущности
     */
    public function entity(): BelongsTo
    {
        return $this->belongsTo(IsoEntity::class, 'entity_id');
    }

    /**
     * Получить кешированный перевод по entity_id и языку
     */
    public static function getCachedTranslation(int $entityId, string $languageCode): ?string
    {
        $cacheKey = "iso_translation.{$entityId}.{$languageCode}";

        return Cache::remember($cacheKey, now()->addDays(30), function () use ($entityId, $languageCode) {
            $translation = static::where('entity_id', $entityId)
                ->where('language_code', $languageCode)
                ->first();

            return $translation ? $translation->translated_name : null;
        });
    }

    /**
     * Получить все кешированные переводы для сущности
     */
    public static function getCachedTranslationsForEntity(int $entityId): array
    {
        $cacheKey = "iso_translations.entity.{$entityId}";

        return Cache::remember($cacheKey, now()->addDays(30), function () use ($entityId) {
            return static::where('entity_id', $entityId)
                ->pluck('translated_name', 'language_code')
                ->toArray();
        });
    }

    /**
     * Получить кешированные переводы для множества сущностей одним запросом
     */
    public static function getCachedTranslationsForEntities(array $entityIds, string $languageCode): array
    {
        $cacheKey = "iso_translations.bulk." . md5(implode(',', $entityIds)) . ".{$languageCode}";

        return Cache::remember($cacheKey, now()->addDays(7), function () use ($entityIds, $languageCode) {
            return static::whereIn('entity_id', $entityIds)
                ->where('language_code', $languageCode)
                ->pluck('translated_name', 'entity_id')
                ->toArray();
        });
    }

    /**
     * Очистить кеш переводов для конкретной сущности
     */
    public function clearEntityCache(): void
    {
        $languages = ['en', 'ru']; // Добавьте нужные языки

        foreach ($languages as $lang) {
            Cache::forget("iso_translation.{$this->entity_id}.{$lang}");
        }

        Cache::forget("iso_translations.entity.{$this->entity_id}");

        // Очищаем также кеш самой сущности
        IsoEntity::clearCache();
    }

    /**
     * Автоматическая очистка кеша при изменении переводов
     */
    protected static function boot()
    {
        parent::boot();

        static::saved(function ($translation) {
            $translation->clearEntityCache();
        });

        static::deleted(function ($translation) {
            $translation->clearEntityCache();
        });
    }
}

<?php

namespace App\Models\Frontend;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Cache;

class IsoEntity extends Model
{
    protected $fillable = [
        'type',
        'iso_code_2',
        'iso_code_3',
        'numeric_code',
        'name',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * Отношение к переводам
     */
    public function translations(): HasMany
    {
        return $this->hasMany(IsoTranslation::class, 'entity_id');
    }

    /**
     * Креативы, использующие данную сущность как страну
     */
    public function creativesAsCountry(): HasMany
    {
        return $this->hasMany(\App\Models\Creative::class, 'country_id');
    }

    /**
     * Креативы, использующие данную сущность как язык
     */
    public function creativesAsLanguage(): HasMany
    {
        return $this->hasMany(\App\Models\Creative::class, 'language_id');
    }

    /**
     * Scope для фильтрации по типу сущности
     */
    public function scopeCountries(Builder $query): Builder
    {
        return $query->where('type', 'country');
    }

    /**
     * Scope для фильтрации по типу сущности
     */
    public function scopeLanguages(Builder $query): Builder
    {
        return $query->where('type', 'language');
    }

    /**
     * Scope для активных сущностей
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope для поиска по ISO2 коду
     */
    public function scopeByIso2(Builder $query, string $code): Builder
    {
        return $query->where('iso_code_2', strtoupper($code));
    }

    /**
     * Scope для поиска по ISO3 коду
     */
    public function scopeByIso3(Builder $query, string $code): Builder
    {
        return $query->where('iso_code_3', strtoupper($code));
    }

    /**
     * Получить перевод для указанного языка
     */
    public function getTranslation(string $languageCode): ?IsoTranslation
    {
        return $this->translations()
            ->where('language_code', $languageCode)
            ->first();
    }

    /**
     * Получить локализованное название
     */
    public function getLocalizedName(string $languageCode = 'en'): string
    {
        $translation = $this->getTranslation($languageCode);
        return $translation ? $translation->translated_name : $this->name;
    }

    /**
     * Проверка, является ли сущность страной
     */
    public function isCountry(): bool
    {
        return $this->type === 'country';
    }

    /**
     * Проверка, является ли сущность языком
     */
    public function isLanguage(): bool
    {
        return $this->type === 'language';
    }

    /**
     * Статический метод для поиска страны по ISO2
     */
    public static function findCountryByIso2(string $iso2): ?self
    {
        return static::countries()->active()->byIso2($iso2)->first();
    }

    /**
     * Статический метод для поиска языка по ISO2
     */
    public static function findLanguageByIso2(string $iso2): ?self
    {
        return static::languages()->active()->byIso2($iso2)->first();
    }

    /**
     * Валидация кода страны по ISO2 или ISO3
     */
    public static function isValidCountryCode(string $code): bool
    {
        if (empty(trim($code))) {
            return false;
        }

        $code = strtoupper(trim($code));

        // Специальные значения, которые всегда разрешены
        $specialValues = ['DEFAULT', 'ALL COUNTRIES', 'ALL'];
        if (in_array($code, $specialValues)) {
            return true;
        }

        // Проверяем наличие страны в базе данных по ISO2 или ISO3
        return static::countries()
            ->active()
            ->where(function ($query) use ($code) {
                $query->where('iso_code_2', $code)
                    ->orWhere('iso_code_3', $code);
            })
            ->exists();
    }

    /**
     * Валидация кода языка по ISO2 или ISO3
     */
    public static function isValidLanguageCode(string $code): bool
    {
        if (empty(trim($code))) {
            return false;
        }

        $code = strtolower(trim($code));

        // Специальные значения, которые всегда разрешены
        $specialValues = ['default', 'all languages', 'all'];
        if (in_array($code, $specialValues)) {
            return true;
        }

        // Проверяем наличие языка в базе данных по ISO2 или ISO3
        return static::languages()
            ->active()
            ->where(function ($query) use ($code) {
                $query->where('iso_code_2', $code)
                    ->orWhere('iso_code_3', $code);
            })
            ->exists();
    }

    /**
     * Получить все доступные переводы для сущности
     */
    public function getAvailableTranslations(): array
    {
        return $this->translations()
            ->pluck('translated_name', 'language_code')
            ->toArray();
    }

    /**
     * Получить кешированный список стран для фильтров (формат для селектов)
     */
    public static function getCachedCountriesForFilters(string $languageCode = 'en'): array
    {
        $cacheKey = "iso_entities.countries.filters.{$languageCode}";

        return Cache::remember($cacheKey, now()->addDays(7), function () use ($languageCode) {
            return static::countries()
                ->active()
                ->with(['translations' => function ($query) use ($languageCode) {
                    $query->where('language_code', $languageCode);
                }])
                ->get()
                ->map(function ($entity) use ($languageCode) {
                    return [
                        'value' => $entity->iso_code_2,
                        'label' => $entity->getLocalizedName($languageCode),
                        'code' => $entity->iso_code_2, // Для флагов
                    ];
                })
                ->sortBy('label')
                ->values()
                ->toArray();
        });
    }

    /**
     * Получить кешированный список языков для фильтров (формат для селектов)
     */
    public static function getCachedLanguagesForFilters(string $languageCode = 'en'): array
    {
        $cacheKey = "iso_entities.languages.filters.{$languageCode}";

        return Cache::remember($cacheKey, now()->addDays(7), function () use ($languageCode) {
            return static::languages()
                ->active()
                ->with(['translations' => function ($query) use ($languageCode) {
                    $query->where('language_code', $languageCode);
                }])
                ->get()
                ->map(function ($entity) use ($languageCode) {
                    return [
                        'value' => $entity->iso_code_2,
                        'label' => $entity->getLocalizedName($languageCode),
                    ];
                })
                ->sortBy('label')
                ->values()
                ->toArray();
        });
    }

    /**
     * Получить кешированный список популярных стран для быстрых фильтров
     */
    public static function getCachedPopularCountries(string $languageCode = 'en'): array
    {
        $cacheKey = "iso_entities.popular_countries.{$languageCode}";

        return Cache::remember($cacheKey, now()->addDays(30), function () use ($languageCode) {
            // Популярные страны для рекламы (можно настроить через конфиг)
            $popularIsoCodes = ['US', 'CA', 'GB', 'AU', 'DE', 'FR', 'IT', 'ES', 'PT', 'BR', 'RU', 'JP', 'KR', 'CN'];

            return static::countries()
                ->active()
                ->whereIn('iso_code_2', $popularIsoCodes)
                ->with(['translations' => function ($query) use ($languageCode) {
                    $query->where('language_code', $languageCode);
                }])
                ->get()
                ->map(function ($entity) use ($languageCode) {
                    return [
                        'value' => $entity->iso_code_2,
                        'label' => $entity->getLocalizedName($languageCode),
                        'code' => $entity->iso_code_2,
                    ];
                })
                ->sortBy('label')
                ->values()
                ->toArray();
        });
    }

    /**
     * Получить кешированную карту ISO2 -> локализованное название для быстрого поиска
     */
    public static function getCachedCountryMap(string $languageCode = 'en'): array
    {
        $cacheKey = "iso_entities.country_map.{$languageCode}";

        return Cache::remember($cacheKey, now()->addDays(7), function () use ($languageCode) {
            return static::countries()
                ->active()
                ->with(['translations' => function ($query) use ($languageCode) {
                    $query->where('language_code', $languageCode);
                }])
                ->get()
                ->pluck('iso_code_2')
                ->mapWithKeys(function ($iso2) use ($languageCode) {
                    $entity = static::findCountryByIso2($iso2);
                    return [$iso2 => $entity ? $entity->getLocalizedName($languageCode) : $iso2];
                })
                ->toArray();
        });
    }

    /**
     * Получить кешированную карту ISO2 -> локализованное название языков
     */
    public static function getCachedLanguageMap(string $languageCode = 'en'): array
    {
        $cacheKey = "iso_entities.language_map.{$languageCode}";

        return Cache::remember($cacheKey, now()->addDays(7), function () use ($languageCode) {
            return static::languages()
                ->active()
                ->with(['translations' => function ($query) use ($languageCode) {
                    $query->where('language_code', $languageCode);
                }])
                ->get()
                ->pluck('iso_code_2')
                ->mapWithKeys(function ($iso2) use ($languageCode) {
                    $entity = static::findLanguageByIso2($iso2);
                    return [$iso2 => $entity ? $entity->getLocalizedName($languageCode) : $iso2];
                })
                ->toArray();
        });
    }

    /**
     * Очистить весь кеш ISO сущностей
     */
    public static function clearCache(): void
    {
        $languages = ['en', 'ru']; // Добавьте нужные языки

        foreach ($languages as $lang) {
            Cache::forget("iso_entities.countries.filters.{$lang}");
            Cache::forget("iso_entities.languages.filters.{$lang}");
            Cache::forget("iso_entities.popular_countries.{$lang}");
            Cache::forget("iso_entities.country_map.{$lang}");
            Cache::forget("iso_entities.language_map.{$lang}");
        }

        // Очищаем кеш валидных стран в CreativesFiltersDTO
        if (class_exists('\App\Http\DTOs\CreativesFiltersDTO')) {
            \App\Http\DTOs\CreativesFiltersDTO::clearCountriesCache();
        }
    }

    /**
     * Автоматическая очистка кеша при изменении данных
     */
    protected static function boot()
    {
        parent::boot();

        static::saved(function () {
            static::clearCache();
        });

        static::deleted(function () {
            static::clearCache();
        });
    }
}

<?php

namespace App\Models\Frontend;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Builder;

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
     * Получить все доступные переводы для сущности
     */
    public function getAvailableTranslations(): array
    {
        return $this->translations()
            ->pluck('translated_name', 'language_code')
            ->toArray();
    }
}

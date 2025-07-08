<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Facades\Cache;
use App\Models\Parser\Source;

class AdvertismentNetwork extends Model
{
    /** @use HasFactory<\Database\Factories\AdvertismentNetworkFactory> */
    use HasFactory;

    protected $fillable = [
        'network_display_name',
        'network_name',
        'description',
        'traffic_type_description',
        'network_url',
        'network_logo',
        'total_clicks',
        'is_adult',
        'is_active',
    ];

    protected static function booted()
    {
        static::saved(function () {
            self::clearCache();
        });

        static::deleted(function () {
            self::clearCache();
        });
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public static function allCached()
    {
        return Cache::remember('advertisment_networks', 60 * 60, function () {
            return self::all();
        });
    }

    /**
     * Get networks for creative filters with minimal data and maximum speed
     * Returns only id, network_name, and network_logo for optimal performance
     */
    public static function forCreativeFilters()
    {
        return Cache::remember('advertisment_networks_filters', 60 * 60 * 24, function () {
            return self::active()
                ->select('id', 'network_name', 'network_logo', 'network_display_name')
                ->orderBy('network_name')
                ->get()
                ->map(function ($network) {
                    return [
                        'value' => $network->network_name,
                        'label' => $network->network_display_name,
                        'logo' => $network->network_logo,
                    ];
                });
        });
    }

    /**
     * Get all networks for creative filters (including inactive)
     * Fallback method when no active networks found
     */
    public static function forCreativeFiltersAll()
    {
        return Cache::remember('advertisment_networks_filters_all', 60 * 60 * 24, function () {
            return self::select('id', 'network_name', 'network_logo', 'is_active')
                ->orderBy('is_active', 'desc')
                ->orderBy('network_name')
                ->get()
                ->map(function ($network) {
                    return [
                        'value' => $network->network_name,
                        'label' => $network->network_display_name . ($network->is_active ? '' : ' (inactive)'),
                        'logo' => $network->network_logo,
                        'disabled' => !$network->is_active,
                    ];
                });
        });
    }

    /**
     * Get networks grouped by traffic type for advanced filters
     */
    public static function forCreativeFiltersGrouped()
    {
        return Cache::remember('advertisment_networks_filters_grouped', 60 * 60 * 24, function () {
            return self::active()
                ->select('id', 'network_name', 'network_logo', 'traffic_type_description')
                ->orderBy('traffic_type_description')
                ->orderBy('network_name')
                ->get()
                ->groupBy('traffic_type_description')
                ->map(function ($networks, $trafficType) {
                    return [
                        'label' => ucfirst(str_replace('_', ' ', $trafficType)),
                        'options' => $networks->map(function ($network) {
                            return [
                                'value' => $network->network_name,
                                'label' => $network->network_display_name,
                                'logo' => $network->network_logo,
                            ];
                        })->values()
                    ];
                })->values();
        });
    }

    /**
     * Clear networks cache (useful for admin updates)
     */
    public static function clearCache()
    {
        Cache::forget('advertisment_networks');
        Cache::forget('advertisment_networks_filters');
        Cache::forget('advertisment_networks_filters_all');
        Cache::forget('advertisment_networks_filters_grouped');
    }

    // public function advertisements(): HasMany
    // {
    //     return $this->hasMany(
    //         Advertisement::class, 
    //         'advertisment_network_name', 
    //         'network_name'
    //     );
    // }

    // public function parserSource(): HasOne
    // {
    //     return $this->hasOne(Source::class, 'name', 'network_name');
    // }

    /**
     * Креативы, связанные с данной рекламной сетью
     */
    public function creatives(): HasMany
    {
        return $this->hasMany(\App\Models\Creative::class, 'advertisment_network_id');
    }

    /**
     * Активные креативы, связанные с данной рекламной сетью
     */
    public function activeCreatives(): HasMany
    {
        return $this->creatives()->where('status', \App\Enums\Frontend\AdvertisingStatus::Active);
    }

    /**
     * Получить количество креативов для данной сети
     */
    public function getCreativesCountAttribute(): int
    {
        return $this->creatives()->count();
    }

    /**
     * Получить количество активных креативов для данной сети
     */
    public function getActiveCreativesCountAttribute(): int
    {
        return $this->activeCreatives()->count();
    }

    // public function isInParser(): bool
    // {
    //     $source = Source::where('is_active', true)->first();
    //     if (!$source) {
    //         return false;
    //     }

    //     $networks = $source->parsing_meta['networks'] ?? [];
    //     return collect($networks)->contains('id', $this->id);
    // }

    // public function getParserSource(): ?Source
    // {
    //     $source = Source::where('is_active', true)->first();
    //     if (!$source) {
    //         return null;
    //     }

    //     return collect($source->parsing_meta['networks'] ?? [])->contains('id', $this->id) ? $source : null;
    // }
}

<?php

namespace App\Models;

use App\Enums\Frontend\BrowserType;
use App\Enums\Frontend\DeviceType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Cache;

class Browser extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'browser',
        'browser_type',
        'device_type',
        'ismobiledevice',
        'istablet',
        'user_agent',
        'is_for_filter',
        'browser_version',
        'platform',
        'is_active',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'browser_type' => BrowserType::class,
        'device_type' => DeviceType::class,
        'ismobiledevice' => 'boolean',
        'istablet' => 'boolean',
        'is_for_filter' => 'boolean',
        'is_active' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Получить все возможные типы браузеров
     */
    public static function getBrowserTypes(): array
    {
        return BrowserType::values();
    }

    /**
     * Получить все возможные типы устройств
     */
    public static function getDeviceTypes(): array
    {
        return DeviceType::values();
    }

    /**
     * Скоп по типу устройства
     */
    public function scopeByDeviceType(Builder $query, DeviceType $type): Builder
    {
        return $query->where('device_type', $type);
    }

    // Скопы (Query Scopes)

    /**
     * Скоп для активных браузеров
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * Скоп для мобильных устройств
     */
    public function scopeMobile(Builder $query): Builder
    {
        return $query->where('ismobiledevice', true);
    }

    /**
     * Скоп для планшетов
     */
    public function scopeTablet(Builder $query): Builder
    {
        return $query->where('istablet', true);
    }

    /**
     * Скоп для десктопных браузеров
     */
    public function scopeDesktop(Builder $query): Builder
    {
        return $query->where('device_type', DeviceType::DESKTOP)
            ->where('ismobiledevice', false)
            ->where('istablet', false);
    }

    /**
     * Скоп для обычных браузеров (не боты)
     */
    public function scopeBrowsers(Builder $query): Builder
    {
        return $query->where('browser_type', BrowserType::BROWSER);
    }

    /**
     * Скоп для ботов и краулеров
     */
    public function scopeBots(Builder $query): Builder
    {
        return $query->where('browser_type', BrowserType::BOT_CRAWLER);
    }

    /**
     * Скоп для браузеров используемых в фильтрации
     */
    public function scopeForFilter(Builder $query): Builder
    {
        return $query->where('is_for_filter', true);
    }

    /**
     * Скоп по типу браузера
     */
    public function scopeByBrowserType(Builder $query, BrowserType $type): Builder
    {
        return $query->where('browser_type', $type);
    }

    /**
     * Скоп по названию браузера
     */
    public function scopeByBrowser(Builder $query, string $browser): Builder
    {
        return $query->where('browser', $browser);
    }

    // Аксессоры (Accessors)

    /**
     * Получить краткое описание браузера
     */
    public function getDescriptionAttribute(): string
    {
        $version = $this->browser_version ? " {$this->browser_version}" : '';
        $platform = $this->platform ? " на {$this->platform}" : '';

        return "{$this->browser}{$version}{$platform}";
    }

    /**
     * Проверить является ли браузер мобильным (мобильный или планшет)
     */
    public function getIsMobileOrTabletAttribute(): bool
    {
        return $this->ismobiledevice || $this->istablet;
    }

    /**
     * Получить тип устройства на русском языке
     */
    public function getDeviceTypeRuAttribute(): string
    {
        return $this->device_type->translatedLabel();
    }

    /**
     * Получить тип браузера на русском языке
     */
    public function getBrowserTypeRuAttribute(): string
    {
        return $this->browser_type->translatedLabel();
    }

    /**
     * Проверить является ли браузер обычным браузером
     */
    public function getIsBrowserAttribute(): bool
    {
        return $this->browser_type === BrowserType::BROWSER;
    }

    /**
     * Проверить является ли браузер ботом
     */
    public function getIsBotAttribute(): bool
    {
        return $this->browser_type === BrowserType::BOT_CRAWLER;
    }

    /**
     * Проверить является ли устройство десктопом
     */
    public function getIsDesktopAttribute(): bool
    {
        return $this->device_type === DeviceType::DESKTOP;
    }

    /**
     * Проверить является ли устройство мобильным
     */
    public function getIsMobileDeviceAttribute(): bool
    {
        return $this->device_type === DeviceType::MOBILE;
    }

    /**
     * Проверить является ли устройство планшетом
     */
    public function getIsTabletDeviceAttribute(): bool
    {
        return $this->device_type === DeviceType::TABLET;
    }

    // Статические методы

    /**
     * Получить популярные браузеры для фильтрации
     */
    public static function getPopularForFilter(int $limit = 10): \Illuminate\Database\Eloquent\Collection
    {
        return static::active()
            ->forFilter()
            ->browsers()
            ->desktop()
            ->orderBy('browser')
            ->limit($limit)
            ->get();
    }

    /**
     * Получить мобильные браузеры для фильтрации
     */
    public static function getMobileForFilter(int $limit = 10): \Illuminate\Database\Eloquent\Collection
    {
        return static::active()
            ->forFilter()
            ->browsers()
            ->mobile()
            ->orderBy('browser')
            ->limit($limit)
            ->get();
    }

    /**
     * Поиск браузера по User-Agent строке
     */
    public static function findByUserAgent(string $userAgent): ?static
    {
        return static::where('user_agent', $userAgent)->first();
    }

    /**
     * Получить случайный User-Agent по типу устройства
     */
    public static function getRandomUserAgent(DeviceType $deviceType = DeviceType::DESKTOP): ?string
    {
        $browser = static::active()
            ->forFilter()
            ->browsers()
            ->where('device_type', $deviceType)
            ->inRandomOrder()
            ->first();

        return $browser?->user_agent;
    }

    /**
     * Статистика по типам браузеров
     */
    public static function getBrowserTypeStats(): \Illuminate\Support\Collection
    {
        return static::active()
            ->selectRaw('browser_type, COUNT(*) as count')
            ->groupBy('browser_type')
            ->orderBy('count', 'desc')
            ->get();
    }

    /**
     * Статистика по типам устройств
     */
    public static function getDeviceTypeStats(): \Illuminate\Support\Collection
    {
        return static::active()
            ->selectRaw('device_type, COUNT(*) as count')
            ->groupBy('device_type')
            ->orderBy('count', 'desc')
            ->get();
    }

    // Методы для быстрого получения данных с кэшированием

    /**
     * Получить все активные браузеры для фильтрации (с кэшированием)
     */
    public static function getForFilters(int $cacheTtl = 3600): \Illuminate\Support\Collection
    {
        return Cache::remember('browsers_for_filters', $cacheTtl, function () {
            return static::active()
                ->forFilter()
                ->browsers()
                ->select(['id', 'browser', 'browser_version', 'device_type', 'platform'])
                ->orderBy('browser')
                ->orderBy('browser_version')
                ->get();
        });
    }

    /**
     * Получить уникальные имена браузеров для фильтров в формате value/label
     */
    public static function getBrowsersForSelect(int $cacheTtl = 3600): array
    {
        return Cache::remember('browsers_select_options', $cacheTtl, function () {
            return static::active()
                ->forFilter()
                ->browsers()
                ->selectRaw('DISTINCT browser')
                ->orderBy('browser')
                ->pluck('browser')
                ->map(fn($browser) => [
                    'value' => $browser,
                    'label' => $browser
                ])
                ->toArray();
        });
    }

    /**
     * Получить браузеры по типу устройства для фильтров
     */
    public static function getBrowsersByDeviceType(DeviceType $deviceType, int $cacheTtl = 3600): array
    {
        $cacheKey = "browsers_device_{$deviceType->value}";

        return Cache::remember($cacheKey, $cacheTtl, function () use ($deviceType) {
            return static::active()
                ->forFilter()
                ->browsers()
                ->byDeviceType($deviceType)
                ->selectRaw('DISTINCT browser')
                ->orderBy('browser')
                ->pluck('browser')
                ->map(fn($browser) => [
                    'value' => $browser,
                    'label' => $browser
                ])
                ->toArray();
        });
    }

    /**
     * Получить популярные браузеры для быстрого доступа
     */
    public static function getPopularBrowsersForSelect(int $limit = 10, int $cacheTtl = 3600): array
    {
        return Cache::remember("popular_browsers_{$limit}", $cacheTtl, function () use ($limit) {
            return static::active()
                ->forFilter()
                ->browsers()
                ->selectRaw('browser, COUNT(*) as usage_count')
                ->groupBy('browser')
                ->orderBy('usage_count', 'desc')
                ->orderBy('browser')
                ->limit($limit)
                ->pluck('browser')
                ->map(fn($browser) => [
                    'value' => $browser,
                    'label' => $browser
                ])
                ->toArray();
        });
    }

    /**
     * Получить мобильные браузеры для фильтров
     */
    public static function getMobileBrowsersForSelect(int $cacheTtl = 3600): array
    {
        return Cache::remember('mobile_browsers_select', $cacheTtl, function () {
            return static::active()
                ->forFilter()
                ->browsers()
                ->mobile()
                ->selectRaw('DISTINCT browser')
                ->orderBy('browser')
                ->pluck('browser')
                ->map(fn($browser) => [
                    'value' => $browser,
                    'label' => $browser
                ])
                ->toArray();
        });
    }

    /**
     * Получить десктопные браузеры для фильтров
     */
    public static function getDesktopBrowsersForSelect(int $cacheTtl = 3600): array
    {
        return Cache::remember('desktop_browsers_select', $cacheTtl, function () {
            return static::active()
                ->forFilter()
                ->browsers()
                ->desktop()
                ->selectRaw('DISTINCT browser')
                ->orderBy('browser')
                ->pluck('browser')
                ->map(fn($browser) => [
                    'value' => $browser,
                    'label' => $browser
                ])
                ->toArray();
        });
    }

    /**
     * Получить браузеры с группировкой по типам устройств
     */
    public static function getBrowsersGroupedByDevice(int $cacheTtl = 3600): array
    {
        return Cache::remember('browsers_grouped_by_device', $cacheTtl, function () {
            $browsers = static::active()
                ->forFilter()
                ->browsers()
                ->select(['browser', 'device_type'])
                ->get();

            $grouped = [];
            foreach (DeviceType::cases() as $deviceType) {
                $deviceBrowsers = $browsers
                    ->where('device_type', $deviceType)
                    ->pluck('browser')
                    ->unique()
                    ->sort()
                    ->map(fn($browser) => [
                        'value' => $browser,
                        'label' => $browser
                    ])
                    ->values()
                    ->toArray();

                if (!empty($deviceBrowsers)) {
                    $grouped[$deviceType->value] = [
                        'label' => $deviceType->translatedLabel(),
                        'browsers' => $deviceBrowsers
                    ];
                }
            }

            return $grouped;
        });
    }

    /**
     * Получить статистику использования браузеров для аналитики
     */
    public static function getBrowserUsageStats(int $cacheTtl = 3600): array
    {
        return Cache::remember('browser_usage_stats', $cacheTtl, function () {
            return static::active()
                ->selectRaw('browser, device_type, COUNT(*) as count')
                ->groupBy(['browser', 'device_type'])
                ->orderBy('count', 'desc')
                ->get()
                ->map(function ($item) {
                    return [
                        'browser' => $item->browser,
                        'device_type' => $item->device_type->translatedLabel(),
                        'count' => $item->count,
                        'percentage' => 0 // будет вычислено позже
                    ];
                })
                ->toArray();
        });
    }

    /**
     * Быстрый поиск браузеров по названию (с кэшированием)
     */
    public static function searchBrowsers(string $query, int $limit = 20, int $cacheTtl = 1800): array
    {
        $cacheKey = "browser_search_" . md5(strtolower($query)) . "_{$limit}";

        return Cache::remember($cacheKey, $cacheTtl, function () use ($query, $limit) {
            return static::active()
                ->forFilter()
                ->browsers()
                ->where('browser', 'LIKE', "%{$query}%")
                ->selectRaw('DISTINCT browser')
                ->orderBy('browser')
                ->limit($limit)
                ->pluck('browser')
                ->map(fn($browser) => [
                    'value' => $browser,
                    'label' => $browser
                ])
                ->toArray();
        });
    }

    /**
     * Очистить кэш браузеров
     */
    public static function clearBrowsersCache(): void
    {
        $cacheKeys = [
            'browsers_for_filters',
            'browsers_select_options',
            'mobile_browsers_select',
            'desktop_browsers_select',
            'browsers_grouped_by_device',
            'browser_usage_stats'
        ];

        foreach ($cacheKeys as $key) {
            Cache::forget($key);
        }

        // Очищаем кэш для каждого типа устройства
        foreach (DeviceType::cases() as $deviceType) {
            Cache::forget("browsers_device_{$deviceType->value}");
        }

        // Очищаем кэш популярных браузеров (разные лимиты)
        for ($i = 5; $i <= 50; $i += 5) {
            Cache::forget("popular_browsers_{$i}");
        }
    }

    /**
     * Автоматическая очистка кэша при изменении данных
     */
    protected static function boot()
    {
        parent::boot();

        static::saved(function () {
            static::clearBrowsersCache();
        });

        static::deleted(function () {
            static::clearBrowsersCache();
        });
    }
}

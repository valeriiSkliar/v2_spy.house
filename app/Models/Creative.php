<?php

namespace App\Models;

use App\Enums\Frontend\AdvertisingFormat;
use App\Enums\Frontend\AdvertisingStatus;
use App\Enums\Frontend\OperationSystem;
use App\Models\Browser;
use App\Models\Frontend\IsoEntity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Cache;

class Creative extends Model
{
    /** @use HasFactory<\Database\Factories\CreativeFactory> */
    use HasFactory, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'format',
        'status',
        'country_id',
        'language_id',
        'browser_id',
        'operation_system',
        'advertisment_network_id',
        'external_id',
        'is_adult',
        'title',
        'description',
        'combined_hash',
        'landing_url',
        'start_date',
        'end_date',
        'is_processed',
        'has_video',
        'video_url',
        'video_duration',
        'main_image_url',
        'main_image_size',
        'icon_url',
        'icon_size',
        'social_likes',
        'social_comments',
        'social_shares',
        'last_seen_at',
    ];

    /**
     * Get the attributes that should be cast.
     */
    protected function casts(): array
    {
        return [
            'format' => AdvertisingFormat::class,
            'status' => AdvertisingStatus::class,
            'operation_system' => OperationSystem::class,
            'external_id' => 'integer',
            'is_adult' => 'boolean',
            'start_date' => 'datetime',
            'end_date' => 'datetime',
            'is_processed' => 'boolean',
            'has_video' => 'boolean',
            'social_likes' => 'integer',
            'social_comments' => 'integer',
            'social_shares' => 'integer',
            'last_seen_at' => 'datetime',
        ];
    }

    /**
     * Связь с страной (ISO сущность типа 'country')
     */
    public function country(): BelongsTo
    {
        return $this->belongsTo(IsoEntity::class, 'country_id')
            ->where('type', 'country');
    }

    /**
     * Связь с языком (ISO сущность типа 'language')
     */
    public function language(): BelongsTo
    {
        return $this->belongsTo(IsoEntity::class, 'language_id')
            ->where('type', 'language');
    }

    /**
     * Связь с браузером
     */
    public function browser(): BelongsTo
    {
        return $this->belongsTo(Browser::class, 'browser_id');
    }

    /**
     * Связь с рекламной сетью
     */
    public function advertismentNetwork(): BelongsTo
    {
        return $this->belongsTo(AdvertismentNetwork::class, 'advertisment_network_id');
    }

    /**
     * Scope для фильтрации по формату рекламы
     */
    public function scopeByFormat($query, $format)
    {
        if ($format && $format !== 'default') {
            return $query->where('format', $format);
        }
        return $query;
    }

    /**
     * Scope для фильтрации по стране
     */
    public function scopeByCountry($query, $countryCode)
    {
        if ($countryCode && $countryCode !== 'default') {
            return $query->whereHas('country', function ($q) use ($countryCode) {
                $q->where('code', $countryCode);
            });
        }
        return $query;
    }

    /**
     * Scope для фильтрации по языку
     */
    public function scopeByLanguage($query, $languageCodes)
    {
        if (!empty($languageCodes)) {
            return $query->whereHas('language', function ($q) use ($languageCodes) {
                $q->whereIn('code', $languageCodes);
            });
        }
        return $query;
    }

    /**
     * Scope для фильтрации по рекламным сетям
     */
    public function scopeByAdvertisingNetworks($query, $networkIds)
    {
        if (!empty($networkIds)) {
            return $query->whereHas('advertismentNetwork', function ($q) use ($networkIds) {
                $q->whereIn('network_name', $networkIds);
            });
        }
        return $query;
    }

    /**
     * Scope для фильтрации по браузерам
     */
    public function scopeByBrowsers($query, $browserIds)
    {
        if (!empty($browserIds)) {
            return $query->whereHas('browser', function ($q) use ($browserIds) {
                $q->whereIn('name', $browserIds);
            });
        }
        return $query;
    }

    /**
     * Scope для фильтрации по операционным системам
     */
    public function scopeByOperatingSystems($query, $osSystems)
    {
        if (!empty($osSystems)) {
            return $query->whereIn('operation_system', $osSystems);
        }
        return $query;
    }

    /**
     * Scope для фильтрации по контенту для взрослых
     */
    public function scopeByAdultContent($query, $onlyAdult)
    {
        if ($onlyAdult) {
            return $query->where('is_adult', true);
        }
        return $query;
    }

    /**
     * Scope для поиска по ключевым словам
     */
    public function scopeBySearchKeyword($query, $keyword)
    {
        if ($keyword) {
            return $query->where(function ($q) use ($keyword) {
                $q->where('title', 'like', '%' . $keyword . '%')
                    ->orWhere('description', 'like', '%' . $keyword . '%');
            });
        }
        return $query;
    }

    /**
     * Scope для фильтрации по периоду
     */
    public function scopeByPeriod($query, $period)
    {
        if (!$period || $period === 'default') {
            return $query;
        }

        switch ($period) {
            case 'today':
                return $query->whereDate('created_at', today());
            case 'yesterday':
                return $query->whereDate('created_at', today()->subDay());
            case 'last7':
                return $query->where('created_at', '>=', now()->subDays(7));
            case 'last30':
                return $query->where('created_at', '>=', now()->subDays(30));
            case 'last90':
                return $query->where('created_at', '>=', now()->subDays(90));
            default:
                return $query;
        }
    }

    /**
     * Scope для сортировки
     */
    public function scopeOrderByType($query, $sortBy)
    {
        switch ($sortBy) {
            case 'byCreationDate':
            case 'creation':
                return $query->orderBy('created_at', 'desc');
            case 'byActivity':
            case 'activity':
                return $query->orderBy('last_seen_at', 'desc');
            case 'byPopularity':
            case 'popularity':
                return $query->orderByRaw('(social_likes + social_comments + social_shares) DESC');
            default:
                return $query->orderBy('created_at', 'desc');
        }
    }

    /**
     * Получить креативы с пагинацией и фильтрами
     */
    public static function getFilteredCreatives(array $filters, int $perPage = 12)
    {
        $query = self::with(['country', 'language', 'browser', 'advertismentNetwork'])
            ->byFormat($filters['activeTab'] ?? 'push')
            ->byCountry($filters['country'] ?? null)
            ->byLanguage($filters['languages'] ?? [])
            ->byAdvertisingNetworks($filters['advertisingNetworks'] ?? [])
            ->byBrowsers($filters['browsers'] ?? [])
            ->byOperatingSystems($filters['operatingSystems'] ?? [])
            ->byAdultContent($filters['onlyAdult'] ?? false)
            ->bySearchKeyword($filters['searchKeyword'] ?? null)
            ->byPeriod($filters['periodDisplay'] ?? 'default')
            ->orderByType($filters['sortBy'] ?? 'creation');

        return $query->paginate($perPage, ['*'], 'page', $filters['page'] ?? 1);
    }

    /**
     * Получить количество креативов с фильтрами
     */
    public static function getFilteredCount(array $filters)
    {
        return self::byFormat($filters['activeTab'] ?? 'push')
            ->byCountry($filters['country'] ?? null)
            ->byLanguage($filters['languages'] ?? [])
            ->byAdvertisingNetworks($filters['advertisingNetworks'] ?? [])
            ->byBrowsers($filters['browsers'] ?? [])
            ->byOperatingSystems($filters['operatingSystems'] ?? [])
            ->byAdultContent($filters['onlyAdult'] ?? false)
            ->bySearchKeyword($filters['searchKeyword'] ?? null)
            ->byPeriod($filters['periodDisplay'] ?? 'default')
            ->count();
    }

    /**
     * Получить статистику по форматам для вкладок с кешированием
     */
    public static function getFormatCounts()
    {
        return Cache::remember('creative_format_counts', 60 * 5, function () {
            $counts = self::selectRaw('format, COUNT(*) as count')
                ->groupBy('format')
                ->pluck('count', 'format')
                ->toArray();

            $totalCount = array_sum($counts);

            return [
                'push' => $counts[AdvertisingFormat::PUSH->value] ?? 0,
                'inpage' => $counts[AdvertisingFormat::INPAGE->value] ?? 0,
                'tiktok' => $counts[AdvertisingFormat::TIKTOK->value] ?? 0,
                'facebook' => $counts[AdvertisingFormat::FACEBOOK->value] ?? 0,
                'total' => $totalCount
            ];
        });
    }

    /**
     * Преобразовать модель в массив для DTO
     */
    public function toCreativeArray(): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'description' => $this->description,
            'category' => $this->format->value,
            'country' => $this->country?->code ?? 'unknown',
            'file_size' => $this->calculateFileSize(),
            'icon_url' => $this->icon_url ?? '',
            'landing_page_url' => $this->landing_url ?? '',
            'video_url' => $this->video_url,
            'has_video' => $this->has_video,
            'created_at' => $this->created_at->format('Y-m-d'),
            'activity_date' => $this->last_seen_at?->format('Y-m-d'),
            'advertising_networks' => $this->advertismentNetwork ? [$this->advertismentNetwork->network_name] : [],
            'languages' => $this->language ? [$this->language->code] : [],
            'operating_systems' => $this->operation_system ? [$this->operation_system->value] : [],
            'browsers' => $this->browser ? [$this->browser->name] : [],
            'devices' => $this->guessDevices(),
            'image_sizes' => $this->main_image_size ? [$this->main_image_size] : [],
            'main_image_size' => $this->main_image_size,
            'main_image_url' => $this->main_image_url,
            'is_adult' => $this->is_adult,
            'social_likes' => $this->social_likes,
            'social_comments' => $this->social_comments,
            'social_shares' => $this->social_shares,
            'duration' => $this->video_duration,
        ];
    }

    /**
     * Вычислить размер файла (заглушка)
     */
    private function calculateFileSize(): string
    {
        // TODO: Реализовать реальный расчет размера файла
        return rand(100, 5000) . 'KB';
    }

    /**
     * Угадать устройства на основе других данных
     */
    private function guessDevices(): array
    {
        // TODO: Реализовать логику определения устройств
        $devices = ['desktop'];

        if ($this->operation_system?->value === 'android' || $this->operation_system?->value === 'ios') {
            $devices[] = 'mobile';
        }

        return $devices;
    }
}

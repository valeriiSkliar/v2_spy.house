<?php

namespace App\Models;

use App\Enums\Frontend\AdvertisingFormat;
use App\Enums\Frontend\AdvertisingStatus;
use App\Enums\Frontend\OperationSystem;
use App\Enums\Frontend\Platform;
use App\Models\AdSource;
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
        'source_id',
        'platform',
        'is_processed',
        'processed_at',
        'is_valid',
        'validation_error',
        'processing_error',
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
        'external_created_at',
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
            'platform' => Platform::class,
            'external_id' => 'integer',
            'is_adult' => 'boolean',
            'start_date' => 'datetime',
            'end_date' => 'datetime',
            'is_processed' => 'boolean',
            'processed_at' => 'datetime',
            'is_valid' => 'boolean',
            'has_video' => 'boolean',
            'social_likes' => 'integer',
            'social_comments' => 'integer',
            'social_shares' => 'integer',
            'last_seen_at' => 'datetime',
            'external_created_at' => 'datetime',
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
     * Связь с источником креатива
     */
    public function source(): BelongsTo
    {
        return $this->belongsTo(AdSource::class, 'source_id');
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
     * Scope для фильтрации по странам (поддерживает массив)
     */
    public function scopeByCountry($query, $countries)
    {
        // Если передали строку (обратная совместимость), преобразуем в массив
        if (is_string($countries)) {
            $countries = $countries !== 'default' ? [$countries] : [];
        }

        if (!empty($countries)) {
            return $query->whereHas('country', function ($q) use ($countries) {
                $q->whereIn('iso_code_2', $countries);
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
                $q->whereIn('iso_code_2', $languageCodes);
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
                $q->whereIn('browser', $browserIds);
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
     * Scope для фильтрации по платформе
     */
    public function scopeByPlatform($query, $platform)
    {
        if ($platform && $platform !== 'default') {
            return $query->where('platform', $platform);
        }
        return $query;
    }

    /**
     * Scope для фильтрации по источнику
     */
    public function scopeBySource($query, $sourceIds)
    {
        if (!empty($sourceIds)) {
            return $query->whereIn('source_id', $sourceIds);
        }
        return $query;
    }

    /**
     * Scope для фильтрации только валидных креативов
     */
    public function scopeOnlyValid($query)
    {
        return $query->where('is_valid', true);
    }

    /**
     * Scope для фильтрации только обработанных креативов
     */
    public function scopeOnlyProcessed($query)
    {
        return $query->where('is_processed', true);
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
        $query = self::with(['country', 'language', 'browser', 'advertismentNetwork', 'source'])
            ->byFormat($filters['activeTab'] ?? 'push')
            ->byCountry($filters['countries'] ?? [])
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
            ->byCountry($filters['countries'] ?? [])
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
            'country' => $this->country ? [
                'code' => $this->country->iso_code_2,
                'name' => $this->country->name,
                'iso_code_3' => $this->country->iso_code_3,
            ] : null,
            'file_size' => $this->calculateFileSize(),
            'icon_url' => $this->icon_url ?? '',
            'landing_url' => $this->landing_url ?? '',
            'video_url' => $this->video_url,
            'has_video' => $this->has_video,
            'created_at' => $this->created_at->format('Y-m-d'),
            'activity_date' => $this->last_seen_at?->format('Y-m-d'),
            'external_created_at' => $this->external_created_at?->format('Y-m-d'),
            'advertising_networks' => $this->advertismentNetwork ? [$this->advertismentNetwork->network_display_name ?? $this->advertismentNetwork->network_name] : [],
            'languages' => $this->language ? [$this->language->iso_code_2] : [],
            'operating_systems' => $this->operation_system ? [$this->operation_system->value] : [],
            'browsers' => $this->browser && $this->browser->browser ? [$this->browser->browser] : [],
            'devices' => $this->guessDevices(),
            'image_sizes' => $this->main_image_size ? [$this->main_image_size] : [],
            'main_image_size' => $this->main_image_size,
            'main_image_url' => $this->main_image_url,
            'platform' => $this->platform?->value,
            'source' => $this->source?->source_display_name ?? $this->source?->source_name ?? 'unknown',
            'is_adult' => $this->is_adult,
            'is_processed' => $this->is_processed,
            'is_valid' => $this->is_valid,
            'processed_at' => $this->processed_at?->format('Y-m-d H:i:s'),
            'validation_error' => $this->validation_error,
            'processing_error' => $this->processing_error,
            'social_likes' => $this->social_likes,
            'social_comments' => $this->social_comments,
            'social_shares' => $this->social_shares,
            'duration' => $this->video_duration,
        ];
    }

    /**
     * Вычислить размер файла (заглушка)
     */
    public function calculateFileSize(): string
    {
        // TODO: Реализовать реальный расчет размера файла
        return rand(100, 5000) . 'KB';
    }

    /**
     * Угадать устройства на основе других данных
     */
    public function guessDevices(): array
    {
        // TODO: Реализовать логику определения устройств
        $devices = ['desktop'];

        if ($this->operation_system?->value === 'android' || $this->operation_system?->value === 'ios') {
            $devices[] = 'mobile';
        }

        return $devices;
    }

    /**
     * Accessor для поля is_active
     * Вычисляет активность на основе статуса креатива
     */
    public function getIsActiveAttribute(): bool
    {
        return $this->status === AdvertisingStatus::Active;
    }
}

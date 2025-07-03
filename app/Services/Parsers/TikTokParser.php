<?php

declare(strict_types=1);

namespace App\Services\Parsers;

use App\Services\Parsers\Exceptions\ParserException;
use Illuminate\Support\Facades\Log;

/**
 * TikTok API Parser
 * 
 * Парсер для извлечения данных из TikTok Business API
 * Поддерживает получение кампаний, объявлений и статистики
 * 
 * @package App\Services\Parsers
 * @author SeniorSoftwareEngineer
 * @version 1.0.0
 */
class TikTokParser extends BaseParser
{
    /**
     * Available endpoints for TikTok Business API
     */
    private const ENDPOINTS = [
        'campaigns' => 'campaign/get/',
        'adgroups' => 'adgroup/get/',
        'ads' => 'ad/get/',
        'creatives' => 'creative/get/',
        'reports' => 'report/get/'
    ];

    /**
     * Default parameters for TikTok API
     */
    private array $defaultParams = [
        'data_level' => 'AUCTION_CAMPAIGN',
        'dimensions' => ['campaign_id'],
        'metrics' => ['impressions', 'clicks', 'spend', 'conversions']
    ];

    /**
     * TikTok advertiser ID
     */
    private string $advertiserId;

    /**
     * Initialize TikTok parser
     *
     * @param string $apiKey TikTok API access token
     * @param string $advertiserId TikTok advertiser ID
     * @param array $options Additional configuration options
     */
    public function __construct(string $apiKey, string $advertiserId, array $options = [])
    {
        $baseUrl = $options['base_url'] ?? config('services.tiktok.base_url', 'https://business-api.tiktok.com/open_api/v1.3');

        $this->advertiserId = $advertiserId;

        // TikTok specific options
        $tiktokOptions = array_merge([
            'timeout' => 60,
            'rate_limit' => config('services.tiktok.rate_limit', 100),
            'max_retries' => 3,
            'retry_delay' => 3,
            'parser_name' => 'TikTok'
        ], $options);

        parent::__construct($apiKey, $baseUrl, $tiktokOptions);
    }

    /**
     * Fetch data from TikTok API
     *
     * @param array $params Request parameters
     * @return array Fetched data
     * @throws ParserException
     */
    public function fetchData(array $params = []): array
    {
        $endpoint = $params['endpoint'] ?? 'campaigns';

        if (!isset(self::ENDPOINTS[$endpoint])) {
            throw new ParserException("Invalid endpoint: {$endpoint}. Available: " . implode(', ', array_keys(self::ENDPOINTS)));
        }

        $requestParams = array_merge($this->defaultParams, $params, [
            'advertiser_id' => $this->advertiserId
        ]);

        // Remove our internal params
        unset($requestParams['endpoint']);

        $response = $this->makeRequest(self::ENDPOINTS[$endpoint], $requestParams);
        $responseData = $response->json();

        if (!$responseData || $responseData['code'] !== 0) {
            throw new ParserException("TikTok API error: " . ($responseData['message'] ?? 'Unknown error'));
        }

        $data = $responseData['data'] ?? [];
        $list = $data['list'] ?? [];

        // Parse each item
        $parsedItems = [];
        foreach ($list as $item) {
            $parsedItems[] = $this->parseItem($item);
        }

        Log::channel('parsers')->info("TikTok data fetched", [
            'endpoint' => $endpoint,
            'items_count' => count($parsedItems)
        ]);

        return [
            'data' => $parsedItems,
            'metadata' => [
                'endpoint' => $endpoint,
                'advertiser_id' => $this->advertiserId,
                'fetched_at' => now()->toISOString(),
                'parser' => $this->parserName
            ]
        ];
    }

    /**
     * Parse individual item from TikTok API into unified format
     *
     * @param array $item Raw item data from API
     * @return array Parsed item in unified format
     */
    public function parseItem(array $item): array
    {
        // Unified format for all TikTok items
        $parsed = [
            'id' => $item['campaign_id'] ?? $item['adgroup_id'] ?? $item['ad_id'] ?? $item['creative_id'] ?? null,
            'type' => $this->detectItemType($item),
            'name' => $item['campaign_name'] ?? $item['adgroup_name'] ?? $item['ad_name'] ?? $item['creative_name'] ?? null,
            'status' => $this->normalizeStatus($item['status'] ?? 'unknown'),
            'created_at' => $this->parseDate($item['create_time'] ?? null),
            'updated_at' => $this->parseDate($item['modify_time'] ?? null),
            'source' => 'tiktok',
            'raw_data' => $item
        ];

        // Add type-specific fields
        switch ($parsed['type']) {
            case 'campaign':
                $parsed = array_merge($parsed, $this->parseCampaign($item));
                break;
            case 'adgroup':
                $parsed = array_merge($parsed, $this->parseAdgroup($item));
                break;
            case 'ad':
                $parsed = array_merge($parsed, $this->parseAd($item));
                break;
            case 'creative':
                $parsed = array_merge($parsed, $this->parseCreative($item));
                break;
            case 'report':
                $parsed = array_merge($parsed, $this->parseReport($item));
                break;
        }

        return $parsed;
    }

    /**
     * Fetch campaigns from TikTok
     *
     * @param array $params Additional parameters
     * @return array Campaign data
     */
    public function fetchCampaigns(array $params = []): array
    {
        return $this->fetchData(array_merge($params, ['endpoint' => 'campaigns']));
    }

    /**
     * Fetch ad groups from TikTok
     *
     * @param array $params Additional parameters
     * @return array Ad group data
     */
    public function fetchAdGroups(array $params = []): array
    {
        return $this->fetchData(array_merge($params, ['endpoint' => 'adgroups']));
    }

    /**
     * Fetch ads from TikTok
     *
     * @param array $params Additional parameters
     * @return array Ad data
     */
    public function fetchAds(array $params = []): array
    {
        return $this->fetchData(array_merge($params, ['endpoint' => 'ads']));
    }

    /**
     * Fetch creatives from TikTok
     *
     * @param array $params Additional parameters
     * @return array Creative data
     */
    public function fetchCreatives(array $params = []): array
    {
        return $this->fetchData(array_merge($params, ['endpoint' => 'creatives']));
    }

    /**
     * Fetch reports from TikTok
     *
     * @param array $params Additional parameters
     * @return array Report data
     */
    public function fetchReports(array $params = []): array
    {
        return $this->fetchData(array_merge($params, ['endpoint' => 'reports']));
    }

    /**
     * Get authentication headers for TikTok API
     *
     * @return array
     */
    protected function getAuthHeaders(): array
    {
        return [
            'Access-Token' => $this->apiKey,
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
            'User-Agent' => 'SpyHouse-TikTok-Parser/1.0'
        ];
    }

    /**
     * Detect item type from raw data
     *
     * @param array $item
     * @return string
     */
    private function detectItemType(array $item): string
    {
        if (isset($item['campaign_id']) && !isset($item['adgroup_id'])) {
            return 'campaign';
        }
        if (isset($item['adgroup_id']) && !isset($item['ad_id'])) {
            return 'adgroup';
        }
        if (isset($item['ad_id']) && !isset($item['creative_id'])) {
            return 'ad';
        }
        if (isset($item['creative_id'])) {
            return 'creative';
        }
        if (isset($item['impressions']) || isset($item['clicks'])) {
            return 'report';
        }
        return 'campaign';
    }

    /**
     * Normalize status values
     *
     * @param string $status
     * @return string
     */
    private function normalizeStatus(string $status): string
    {
        return match (strtoupper($status)) {
            'ENABLE', 'ACTIVE' => 'active',
            'DISABLE', 'PAUSED' => 'paused',
            'AUDIT', 'PENDING' => 'pending',
            'REJECT', 'REJECTED' => 'rejected',
            'DONE', 'COMPLETED' => 'completed',
            default => 'unknown'
        };
    }

    /**
     * Parse date string to ISO format
     *
     * @param string|null $date
     * @return string|null
     */
    private function parseDate(?string $date): ?string
    {
        if (!$date) {
            return null;
        }

        try {
            // TikTok uses Unix timestamp
            if (is_numeric($date)) {
                return \Carbon\Carbon::createFromTimestamp($date)->toISOString();
            }
            return \Carbon\Carbon::parse($date)->toISOString();
        } catch (\Exception $e) {
            Log::channel('parsers')->warning("Failed to parse date: {$date}");
            return null;
        }
    }

    /**
     * Parse campaign-specific data
     *
     * @param array $item
     * @return array
     */
    private function parseCampaign(array $item): array
    {
        return [
            'campaign_id' => $item['campaign_id'] ?? null,
            'budget' => $item['budget'] ?? null,
            'budget_mode' => $item['budget_mode'] ?? null,
            'objective_type' => $item['objective_type'] ?? null,
            'campaign_type' => $item['campaign_type'] ?? null,
            'special_industries' => $item['special_industries'] ?? []
        ];
    }

    /**
     * Parse adgroup-specific data
     *
     * @param array $item
     * @return array
     */
    private function parseAdgroup(array $item): array
    {
        return [
            'adgroup_id' => $item['adgroup_id'] ?? null,
            'campaign_id' => $item['campaign_id'] ?? null,
            'budget' => $item['budget'] ?? null,
            'bid_type' => $item['bid_type'] ?? null,
            'bid_price' => $item['bid_price'] ?? null,
            'optimization_goal' => $item['optimization_goal'] ?? null,
            'targeting' => $item['targeting'] ?? [],
            'schedule_type' => $item['schedule_type'] ?? null
        ];
    }

    /**
     * Parse ad-specific data
     *
     * @param array $item
     * @return array
     */
    private function parseAd(array $item): array
    {
        return [
            'ad_id' => $item['ad_id'] ?? null,
            'adgroup_id' => $item['adgroup_id'] ?? null,
            'creative_id' => $item['creative_id'] ?? null,
            'ad_format' => $item['ad_format'] ?? null,
            'landing_page_url' => $item['landing_page_url'] ?? null,
            'display_name' => $item['display_name'] ?? null
        ];
    }

    /**
     * Parse creative-specific data
     *
     * @param array $item
     * @return array
     */
    private function parseCreative(array $item): array
    {
        return [
            'creative_id' => $item['creative_id'] ?? null,
            'ad_name' => $item['ad_name'] ?? null,
            'ad_text' => $item['ad_text'] ?? null,
            'call_to_action' => $item['call_to_action'] ?? null,
            'creative_type' => $item['creative_type'] ?? null,
            'video_id' => $item['video_id'] ?? null,
            'image_ids' => $item['image_ids'] ?? [],
            'landing_page_url' => $item['landing_page_url'] ?? null
        ];
    }

    /**
     * Parse report-specific data
     *
     * @param array $item
     * @return array
     */
    private function parseReport(array $item): array
    {
        return [
            'impressions' => $item['impressions'] ?? 0,
            'clicks' => $item['clicks'] ?? 0,
            'conversions' => $item['conversions'] ?? 0,
            'spend' => $item['spend'] ?? 0,
            'ctr' => $item['ctr'] ?? 0,
            'cvr' => $item['cvr'] ?? 0,
            'cpc' => $item['cpc'] ?? 0,
            'cpm' => $item['cpm'] ?? 0,
            'cost_per_conversion' => $item['cost_per_conversion'] ?? 0,
            'stat_time_day' => $item['stat_time_day'] ?? null,
            'video_play_actions' => $item['video_play_actions'] ?? 0,
            'video_watched_2s' => $item['video_watched_2s'] ?? 0,
            'video_watched_6s' => $item['video_watched_6s'] ?? 0
        ];
    }

    /**
     * Get advertiser ID
     *
     * @return string
     */
    public function getAdvertiserId(): string
    {
        return $this->advertiserId;
    }
}

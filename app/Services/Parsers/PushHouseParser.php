<?php

declare(strict_types=1);

namespace App\Services\Parsers;

use App\Services\Parsers\Exceptions\ParserException;
use Illuminate\Support\Facades\Log;

/**
 * PushHouse API Parser
 * 
 * Парсер для извлечения данных из PushHouse API
 * Поддерживает получение кампаний, креативов и статистики
 * 
 * @package App\Services\Parsers
 * @author SeniorSoftwareEngineer
 * @version 1.0.0
 */
class PushHouseParser extends BaseParser
{
    /**
     * Available endpoints for PushHouse API
     */
    private const ENDPOINTS = [
        'campaigns' => 'campaigns',
        'creatives' => 'creatives',
        'statistics' => 'statistics',
        'offers' => 'offers'
    ];

    /**
     * Default parameters for PushHouse API
     */
    private array $defaultParams = [
        'format' => 'json',
        'version' => 'v1'
    ];

    /**
     * Initialize PushHouse parser
     *
     * @param string|null $apiKey PushHouse API key (null for open endpoints)
     * @param array $options Additional configuration options
     */
    public function __construct(?string $apiKey = null, array $options = [])
    {
        $baseUrl = $options['base_url'] ?? config('services.push_house.base_url', 'https://api.pushhouse.com');

        // PushHouse specific options
        $pushHouseOptions = array_merge([
            'timeout' => 45,
            'rate_limit' => config('services.push_house.rate_limit', 1000),
            'max_retries' => 3,
            'retry_delay' => 2,
            'parser_name' => 'PushHouse',
            'requires_auth' => !empty($apiKey) // Authentication required only if API key provided
        ], $options);

        parent::__construct($apiKey, $baseUrl, $pushHouseOptions);
    }

    /**
     * Fetch data from PushHouse API
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

        $requestParams = array_merge($this->defaultParams, $params);

        // Remove our internal params
        unset($requestParams['endpoint']);

        $response = $this->makeRequest(self::ENDPOINTS[$endpoint], $requestParams);
        $responseData = $response->json();

        if (!$responseData || !isset($responseData['data'])) {
            throw new ParserException("Invalid response format from PushHouse API");
        }

        // Parse each item
        $parsedItems = [];
        foreach ($responseData['data'] as $item) {
            $parsedItems[] = $this->parseItem($item);
        }

        Log::channel('parsers')->info("PushHouse data fetched", [
            'endpoint' => $endpoint,
            'items_count' => count($parsedItems)
        ]);

        return [
            'data' => $parsedItems,
            'metadata' => [
                'endpoint' => $endpoint,
                'fetched_at' => now()->toISOString(),
                'parser' => $this->parserName
            ]
        ];
    }

    /**
     * Parse individual item from PushHouse API into unified format
     *
     * @param array $item Raw item data from API
     * @return array Parsed item in unified format
     */
    public function parseItem(array $item): array
    {
        // Unified format for all PushHouse items
        $parsed = [
            'id' => $item['id'] ?? null,
            'type' => $this->detectItemType($item),
            'name' => $item['name'] ?? $item['title'] ?? null,
            'status' => $this->normalizeStatus($item['status'] ?? 'unknown'),
            'created_at' => $this->parseDate($item['created_at'] ?? null),
            'updated_at' => $this->parseDate($item['updated_at'] ?? null),
            'source' => 'pushhouse',
            'raw_data' => $item
        ];

        // Add type-specific fields
        switch ($parsed['type']) {
            case 'campaign':
                $parsed = array_merge($parsed, $this->parseCampaign($item));
                break;
            case 'creative':
                $parsed = array_merge($parsed, $this->parseCreative($item));
                break;
            case 'statistic':
                $parsed = array_merge($parsed, $this->parseStatistic($item));
                break;
            case 'offer':
                $parsed = array_merge($parsed, $this->parseOffer($item));
                break;
        }

        return $parsed;
    }

    /**
     * Fetch campaigns from PushHouse
     *
     * @param array $params Additional parameters
     * @return array Campaign data
     */
    public function fetchCampaigns(array $params = []): array
    {
        return $this->fetchData(array_merge($params, ['endpoint' => 'campaigns']));
    }

    /**
     * Fetch creatives from PushHouse
     *
     * @param array $params Additional parameters
     * @return array Creative data
     */
    public function fetchCreatives(array $params = []): array
    {
        return $this->fetchData(array_merge($params, ['endpoint' => 'creatives']));
    }

    /**
     * Fetch statistics from PushHouse
     *
     * @param array $params Additional parameters
     * @return array Statistics data
     */
    public function fetchStatistics(array $params = []): array
    {
        return $this->fetchData(array_merge($params, ['endpoint' => 'statistics']));
    }

    /**
     * Fetch offers from PushHouse
     *
     * @param array $params Additional parameters
     * @return array Offer data
     */
    public function fetchOffers(array $params = []): array
    {
        return $this->fetchData(array_merge($params, ['endpoint' => 'offers']));
    }

    /**
     * Get authentication headers for PushHouse API
     *
     * @return array
     */
    protected function getAuthHeaders(): array
    {
        $headers = [
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
            'User-Agent' => 'SpyHouse-PushHouse-Parser/1.0'
        ];

        // Add API key header only if provided
        if (!empty($this->apiKey)) {
            $headers['X-API-Key'] = $this->apiKey;
        }

        return $headers;
    }

    /**
     * Detect item type from raw data
     *
     * @param array $item
     * @return string
     */
    private function detectItemType(array $item): string
    {
        if (isset($item['campaign_id'])) {
            return 'creative';
        }
        if (isset($item['impressions']) || isset($item['clicks'])) {
            return 'statistic';
        }
        if (isset($item['payout']) || isset($item['offer_url'])) {
            return 'offer';
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
        return match (strtolower($status)) {
            'active', 'running', 'live' => 'active',
            'paused', 'stopped' => 'paused',
            'pending', 'review' => 'pending',
            'rejected', 'declined' => 'rejected',
            'completed', 'finished' => 'completed',
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
            'budget' => $item['budget'] ?? null,
            'daily_budget' => $item['daily_budget'] ?? null,
            'bid' => $item['bid'] ?? null,
            'targeting' => $item['targeting'] ?? [],
            'schedule' => $item['schedule'] ?? null
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
            'campaign_id' => $item['campaign_id'] ?? null,
            'title' => $item['title'] ?? null,
            'description' => $item['description'] ?? null,
            'image_url' => $item['image_url'] ?? null,
            'landing_url' => $item['landing_url'] ?? null,
            'format' => $item['format'] ?? 'push'
        ];
    }

    /**
     * Parse statistic-specific data
     *
     * @param array $item
     * @return array
     */
    private function parseStatistic(array $item): array
    {
        return [
            'impressions' => $item['impressions'] ?? 0,
            'clicks' => $item['clicks'] ?? 0,
            'conversions' => $item['conversions'] ?? 0,
            'spend' => $item['spend'] ?? 0,
            'revenue' => $item['revenue'] ?? 0,
            'ctr' => $item['ctr'] ?? 0,
            'cvr' => $item['cvr'] ?? 0,
            'cpc' => $item['cpc'] ?? 0,
            'cpm' => $item['cpm'] ?? 0,
            'date' => $item['date'] ?? null
        ];
    }

    /**
     * Parse offer-specific data
     *
     * @param array $item
     * @return array
     */
    private function parseOffer(array $item): array
    {
        return [
            'offer_url' => $item['offer_url'] ?? null,
            'payout' => $item['payout'] ?? null,
            'payout_type' => $item['payout_type'] ?? null,
            'category' => $item['category'] ?? null,
            'countries' => $item['countries'] ?? [],
            'description' => $item['description'] ?? null
        ];
    }
}

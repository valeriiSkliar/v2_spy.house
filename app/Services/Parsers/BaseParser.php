<?php

declare(strict_types=1);

namespace App\Services\Parsers;

use App\Services\Parsers\Exceptions\ParserException;
use App\Services\Parsers\Exceptions\RateLimitException;
use App\Services\Parsers\Exceptions\ApiKeyException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

/**
 * Base parser class providing common functionality for API data extraction
 * 
 * @package App\Services\Parsers
 * @author SeniorSoftwareEngineer
 * @version 1.0.0
 */
abstract class BaseParser
{
    /**
     * API key for authentication (nullable for open endpoints)
     */
    protected ?string $apiKey;

    /**
     * Base URL for API endpoints
     */
    protected string $baseUrl;

    /**
     * HTTP client timeout in seconds
     */
    protected int $timeout;

    /**
     * Rate limit: requests per minute
     */
    protected int $rateLimit;

    /**
     * Maximum retry attempts for failed requests
     */
    protected int $maxRetries;

    /**
     * Delay between retry attempts in seconds
     */
    protected int $retryDelay;

    /**
     * Parser name for logging and caching
     */
    protected string $parserName;

    /**
     * Whether this parser requires authentication
     */
    protected bool $requiresAuth;

    /**
     * Initialize parser with configuration
     *
     * @param string|null $apiKey API key for authentication (null for open endpoints)
     * @param string $baseUrl Base URL for API endpoints
     * @param array $options Additional configuration options
     * 
     * @throws ParserException If required parameters are missing
     */
    public function __construct(?string $apiKey = null, string $baseUrl, array $options = [])
    {
        if (empty($baseUrl)) {
            throw new ParserException('Base URL is required');
        }

        $this->apiKey = $apiKey;
        $this->baseUrl = rtrim($baseUrl, '/');
        $this->timeout = $options['timeout'] ?? 30;
        $this->rateLimit = $options['rate_limit'] ?? 60;
        $this->maxRetries = $options['max_retries'] ?? 3;
        $this->retryDelay = $options['retry_delay'] ?? 1;
        $this->parserName = $options['parser_name'] ?? static::class;
        $this->requiresAuth = $options['requires_auth'] ?? true;

        // Validate API key requirement
        if ($this->requiresAuth && empty($this->apiKey)) {
            throw new ParserException('API key is required for authenticated endpoints');
        }
    }

    /**
     * Make HTTP request with retry logic and error handling
     *
     * @param string $endpoint API endpoint path
     * @param array $params Query parameters
     * @param string $method HTTP method (GET, POST, etc.)
     * @param array $headers Additional headers
     * @param int $retries Number of retry attempts
     * 
     * @return Response
     * @throws ParserException On unrecoverable errors
     * @throws RateLimitException When rate limit is exceeded
     * @throws ApiKeyException When API key is invalid
     */
    protected function makeRequest(
        string $endpoint,
        array $params = [],
        string $method = 'GET',
        array $headers = [],
        int $retries = null
    ): Response {
        $retries = $retries ?? $this->maxRetries;
        $url = $this->baseUrl . '/' . ltrim($endpoint, '/');

        // Check rate limit
        $this->checkRateLimit();

        // Prepare headers with authentication
        $headers = array_merge($this->getAuthHeaders(), $headers);

        $this->logRequest($url, $params, $method);

        for ($attempt = 1; $attempt <= $retries + 1; $attempt++) {
            try {
                $response = Http::timeout($this->timeout)
                    ->withHeaders($headers)
                    ->send($method, $url, [
                        'query' => $method === 'GET' ? $params : [],
                        'json' => $method !== 'GET' ? $params : []
                    ]);

                $this->logResponse($response, $attempt);

                // Handle successful response
                if ($response->successful()) {
                    $this->updateRateLimitCounter();
                    return $response;
                }

                // Handle specific error codes
                $this->handleError($response);

                // If we reach here, it's a retryable error
                if ($attempt <= $retries) {
                    $this->logRetry($attempt, $retries, $response->status());
                    sleep($this->retryDelay * $attempt); // Exponential backoff
                }
            } catch (\Exception $e) {
                if ($attempt <= $retries) {
                    $this->logRetry($attempt, $retries, 0, $e->getMessage());
                    sleep($this->retryDelay * $attempt);
                } else {
                    throw new ParserException("Request failed after {$retries} retries: " . $e->getMessage(), 0, $e);
                }
            }
        }

        throw new ParserException("Request failed after {$retries} retries");
    }

    /**
     * Handle HTTP error responses
     *
     * @param Response $response
     * @throws ApiKeyException
     * @throws RateLimitException
     * @throws ParserException
     */
    protected function handleError(Response $response): void
    {
        $statusCode = $response->status();
        $responseBody = $response->body();

        switch ($statusCode) {
            case 401:
                // Only throw API key exception if authentication is required
                if ($this->requiresAuth) {
                    throw new ApiKeyException("Invalid API key: {$responseBody}");
                } else {
                    throw new ParserException("Unauthorized access: {$responseBody}");
                }

            case 403:
                // Only throw API key exception if authentication is required
                if ($this->requiresAuth) {
                    throw new ApiKeyException("Access forbidden: {$responseBody}");
                } else {
                    throw new ParserException("Access forbidden: {$responseBody}");
                }

            case 429:
                $retryAfter = $response->header('Retry-After', 60);
                throw new RateLimitException("Rate limit exceeded. Retry after {$retryAfter} seconds", (int)$retryAfter);

            case 404:
                throw new ParserException("Endpoint not found: {$responseBody}");

            case 500:
            case 502:
            case 503:
            case 504:
                // These are retryable errors, don't throw exception
                return;

            default:
                throw new ParserException("HTTP {$statusCode}: {$responseBody}");
        }
    }

    /**
     * Get authentication headers
     *
     * @return array
     */
    protected function getAuthHeaders(): array
    {
        $headers = [
            'Accept' => 'application/json',
            'User-Agent' => 'SpyHouse-Parser/1.0'
        ];

        // Add authentication header only if API key is provided
        if (!empty($this->apiKey)) {
            $headers['Authorization'] = 'Bearer ' . $this->apiKey;
        }

        return $headers;
    }

    /**
     * Check and enforce rate limiting
     *
     * @throws RateLimitException
     */
    protected function checkRateLimit(): void
    {
        $cacheKey = "parser_rate_limit:{$this->parserName}:" . now()->format('Y-m-d-H-i');
        $currentCount = Cache::get($cacheKey, 0);

        if ($currentCount >= $this->rateLimit) {
            throw new RateLimitException("Rate limit of {$this->rateLimit} requests per minute exceeded");
        }
    }

    /**
     * Update rate limit counter
     */
    protected function updateRateLimitCounter(): void
    {
        $cacheKey = "parser_rate_limit:{$this->parserName}:" . now()->format('Y-m-d-H-i');
        Cache::put($cacheKey, Cache::get($cacheKey, 0) + 1, 60);
    }

    /**
     * Log API request
     */
    protected function logRequest(string $url, array $params, string $method): void
    {
        Log::channel('parsers')->info("API Request", [
            'parser' => $this->parserName,
            'method' => $method,
            'url' => $url,
            'params' => $params,
            'timestamp' => now()->toISOString()
        ]);
    }

    /**
     * Log API response
     */
    protected function logResponse(Response $response, int $attempt): void
    {
        Log::channel('parsers')->info("API Response", [
            'parser' => $this->parserName,
            'status' => $response->status(),
            'attempt' => $attempt,
            'response_size' => strlen($response->body()),
            'timestamp' => now()->toISOString()
        ]);
    }

    /**
     * Log retry attempt
     */
    protected function logRetry(int $attempt, int $maxRetries, int $statusCode, string $error = null): void
    {
        Log::channel('parsers')->warning("API Retry", [
            'parser' => $this->parserName,
            'attempt' => $attempt,
            'max_retries' => $maxRetries,
            'status_code' => $statusCode,
            'error' => $error,
            'timestamp' => now()->toISOString()
        ]);
    }

    /**
     * Make concurrent requests using HTTP pool
     *
     * @param array $requests Array of request configurations
     * @return array Array of responses
     */
    protected function makePoolRequest(array $requests): array
    {
        $responses = Http::pool(function ($pool) use ($requests) {
            $headers = $this->getAuthHeaders();

            foreach ($requests as $key => $request) {
                $url = $this->baseUrl . '/' . ltrim($request['endpoint'], '/');
                $method = $request['method'] ?? 'GET';
                $params = $request['params'] ?? [];

                $poolRequest = $pool->timeout($this->timeout)->withHeaders($headers);

                if ($method === 'GET') {
                    $poolRequest->get($url, $params);
                } else {
                    $poolRequest->send($method, $url, ['json' => $params]);
                }
            }
        });

        foreach ($responses as $key => $response) {
            $this->logResponse($response, 1);
            if (!$response->successful()) {
                $this->handleError($response);
            }
        }

        return $responses;
    }

    /**
     * Abstract method to fetch data from API
     *
     * @param array $params Request parameters
     * @return array Fetched data
     */
    abstract public function fetchData(array $params = []): array;

    /**
     * Abstract method to parse individual item into unified format
     *
     * @param array $item Raw item data
     * @return array Parsed item data
     */
    abstract public function parseItem(array $item): array;

    /**
     * Get parser configuration
     *
     * @return array
     */
    public function getConfig(): array
    {
        return [
            'parser_name' => $this->parserName,
            'base_url' => $this->baseUrl,
            'rate_limit' => $this->rateLimit,
            'timeout' => $this->timeout,
            'max_retries' => $this->maxRetries
        ];
    }

    /**
     * Get parser statistics
     *
     * @return array
     */
    public function getStats(): array
    {
        $cacheKey = "parser_rate_limit:{$this->parserName}:" . now()->format('Y-m-d-H-i');

        return [
            'requests_this_minute' => Cache::get($cacheKey, 0),
            'rate_limit' => $this->rateLimit,
            'requests_remaining' => max(0, $this->rateLimit - Cache::get($cacheKey, 0))
        ];
    }
}

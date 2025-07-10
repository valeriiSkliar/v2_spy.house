<?php

declare(strict_types=1);

namespace App\Services\Parsers;

use App\Services\Parsers\Exceptions\ParserException;
use App\Models\AdSource;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\Facades\Log;

/**
 * Parser Manager
 * 
 * Centralized manager for handling multiple API parsers
 * Provides unified interface for parser operations
 * 
 * @package App\Services\Parsers
 * @author SeniorSoftwareEngineer
 * @version 1.0.0
 */
class ParserManager
{
    /**
     * Available parsers
     */
    private const PARSERS = [
        'pushhouse' => PushHouseParser::class,
        'tiktok' => TikTokParser::class,
        'feedhouse' => FeedHouseParser::class,
    ];

    /**
     * Laravel application instance
     */
    private Application $app;

    /**
     * Cached parser instances
     */
    private array $parsers = [];

    /**
     * Initialize parser manager
     *
     * @param Application $app Laravel application instance
     */
    public function __construct(Application $app)
    {
        $this->app = $app;
    }

    /**
     * Get parser instance by name
     *
     * @param string $name Parser name
     * @return BaseParser
     * @throws ParserException
     */
    public function parser(string $name): BaseParser
    {
        $name = strtolower($name);

        if (!isset(self::PARSERS[$name])) {
            throw new ParserException("Parser '{$name}' not found. Available: " . implode(', ', array_keys(self::PARSERS)));
        }

        if (!isset($this->parsers[$name])) {
            $this->parsers[$name] = $this->app->make(self::PARSERS[$name]);
        }

        return $this->parsers[$name];
    }

    /**
     * Get PushHouse parser
     *
     * @return PushHouseParser
     */
    public function pushHouse(): PushHouseParser
    {
        return $this->parser('pushhouse');
    }

    /**
     * Get TikTok parser
     *
     * @return TikTokParser
     */
    public function tikTok(): TikTokParser
    {
        return $this->parser('tiktok');
    }

    /**
     * Get FeedHouse parser
     *
     * @return FeedHouseParser
     */
    public function feedHouse(): FeedHouseParser
    {
        return $this->parser('feedhouse');
    }

    /**
     * Get FeedHouse parser with AdSource state management
     *
     * @param AdSource $adSource AdSource model for state management
     * @param array $params Additional parameters
     * @return array Parsing results
     */
    public function feedHouseWithState(AdSource $adSource, array $params = []): array
    {
        $parser = $this->feedHouse();
        return $parser->fetchWithStateManagement($adSource, $params);
    }

    /**
     * Fetch data from multiple parsers concurrently
     *
     * @param array $requests Array of parser requests
     * @return array Results from all parsers
     */
    public function fetchMultiple(array $requests): array
    {
        $results = [];
        $startTime = microtime(true);

        foreach ($requests as $key => $request) {
            try {
                $parserName = $request['parser'] ?? '';
                $method = $request['method'] ?? 'fetchData';
                $params = $request['params'] ?? [];

                $parser = $this->parser($parserName);

                if (!method_exists($parser, $method)) {
                    throw new ParserException("Method '{$method}' not found in parser '{$parserName}'");
                }

                $results[$key] = [
                    'success' => true,
                    'data' => $parser->$method($params),
                    'parser' => $parserName,
                    'method' => $method
                ];
            } catch (\Exception $e) {
                $results[$key] = [
                    'success' => false,
                    'error' => $e->getMessage(),
                    'parser' => $request['parser'] ?? 'unknown',
                    'method' => $request['method'] ?? 'unknown'
                ];

                Log::channel('parsers')->error("Parser request failed", [
                    'key' => $key,
                    'request' => $request,
                    'error' => $e->getMessage()
                ]);
            }
        }

        $duration = microtime(true) - $startTime;

        Log::channel('parsers')->info("Multiple parser requests completed", [
            'requests_count' => count($requests),
            'success_count' => count(array_filter($results, fn($r) => $r['success'])),
            'duration' => $duration
        ]);

        return $results;
    }

    /**
     * Get all available parsers
     *
     * @return array
     */
    public function getAvailableParsers(): array
    {
        return array_keys(self::PARSERS);
    }

    /**
     * Clear cached parser instances
     */
    public function clearCache(): void
    {
        $this->parsers = [];
    }
}

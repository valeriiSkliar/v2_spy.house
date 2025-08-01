<?php

declare(strict_types=1);

namespace App\Providers;

use App\Services\Parsers\PushHouseParser;
use App\Services\Parsers\TikTokParser;
use App\Services\Parsers\ParserManager;
use Illuminate\Support\ServiceProvider;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\Facades\Cache;

/**
 * Service Provider for API Parsers
 * 
 * Registers parsers in the Laravel container and provides
 * a centralized manager for parser operations
 * 
 * @package App\Providers
 * @version 1.0.0
 */
class ParserServiceProvider extends ServiceProvider
{
    /**
     * All of the container bindings that should be registered.
     *
     * @var array
     */
    public $bindings = [
        //
    ];

    /**
     * All of the container singletons that should be registered.
     *
     * @var array
     */
    public $singletons = [
        ParserManager::class => ParserManager::class,
    ];

    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Register PushHouse Parser
        $this->app->bind(PushHouseParser::class, function (Application $app) {
            $config = config('services.push_house');
            $apiKey = $config['api_key'] ?? null;

            // PushHouse can work without API key for open endpoints
            return new PushHouseParser($apiKey, [
                'base_url' => $config['base_url'],
                'rate_limit' => $config['rate_limit'],
                'timeout' => $config['timeout'],
                'max_retries' => $config['max_retries'],
                'retry_delay' => $config['retry_delay'],
            ]);
        });

        // Register TikTok Parser
        $this->app->bind(TikTokParser::class, function (Application $app) {
            $config = config('services.tiktok');

            if (empty($config['api_key'])) {
                throw new \InvalidArgumentException('TikTok API key is not configured');
            }

            if (empty($config['advertiser_id'])) {
                throw new \InvalidArgumentException('TikTok advertiser ID is not configured');
            }

            return new TikTokParser($config['api_key'], $config['advertiser_id'], [
                'base_url' => $config['base_url'],
                'rate_limit' => $config['rate_limit'],
                'timeout' => $config['timeout'],
                'max_retries' => $config['max_retries'],
                'retry_delay' => $config['retry_delay'],
            ]);
        });

        // Register Parser Manager
        $this->app->singleton(ParserManager::class, function (Application $app) {
            return new ParserManager($app);
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Validate parser configurations only in console mode or once per day
        if ($this->app->runningInConsole() || !Cache::has('parser_config_validated_today')) {
            $this->validateParserConfigurations();

            // Mark as validated for today (only for web requests)
            if (!$this->app->runningInConsole()) {
                Cache::put('parser_config_validated_today', true, now()->addHours(24));
            }
        }
    }

    /**
     * Validate parser configurations
     */
    private function validateParserConfigurations(): void
    {
        $parsers = ['push_house', 'tiktok'];

        foreach ($parsers as $parser) {
            $config = config("services.{$parser}");

            if (!$config) {
                continue;
            }

            // Validate required fields
            $requiredFields = ['base_url'];

            // API key is required for all parsers except PushHouse (which supports open endpoints)
            if ($parser !== 'push_house') {
                $requiredFields[] = 'api_key';
            }

            if ($parser === 'tiktok') {
                $requiredFields[] = 'advertiser_id';
            }

            foreach ($requiredFields as $field) {
                if (empty($config[$field])) {
                    $cacheKey = "parser_config_missing_{$parser}_{$field}";
                    if (!\Illuminate\Support\Facades\Cache::has($cacheKey)) {
                        \Illuminate\Support\Facades\Log::warning("Parser configuration missing required field", [
                            'parser' => $parser,
                            'field' => $field
                        ]);
                        // Cache for 24 hours to prevent spam
                        \Illuminate\Support\Facades\Cache::put($cacheKey, true, now()->addHours(24));
                    }
                }
            }

            // Log warning if PushHouse API key is missing (but don't fail) - only once per day
            if ($parser === 'push_house' && empty($config['api_key'])) {
                $cacheKey = "parser_config_warning_{$parser}_no_api_key";
                if (!\Illuminate\Support\Facades\Cache::has($cacheKey)) {
                    \Illuminate\Support\Facades\Log::info("PushHouse parser configured without API key - using open endpoints only", [
                        'parser' => $parser
                    ]);
                    // Cache for 24 hours to prevent spam
                    \Illuminate\Support\Facades\Cache::put($cacheKey, true, now()->addHours(24));
                }
            }

            // Validate numeric fields
            $numericFields = ['rate_limit', 'timeout', 'max_retries', 'retry_delay'];
            foreach ($numericFields as $field) {
                if (isset($config[$field]) && !is_numeric($config[$field])) {
                    $cacheKey = "parser_config_invalid_{$parser}_{$field}";
                    if (!\Illuminate\Support\Facades\Cache::has($cacheKey)) {
                        \Illuminate\Support\Facades\Log::warning("Parser configuration invalid numeric value", [
                            'parser' => $parser,
                            'field' => $field,
                            'value' => $config[$field]
                        ]);
                        // Cache for 24 hours to prevent spam
                        \Illuminate\Support\Facades\Cache::put($cacheKey, true, now()->addHours(24));
                    }
                }
            }
        }
    }
}

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
 * Использует пагинацию через path-параметры и фильтрацию статуса
 * 
 * @package App\Services\Parsers
 * @author SeniorSoftwareEngineer
 * @version 1.1.0
 */
class PushHouseParser extends BaseParser
{

    /**
     * Initialize PushHouse parser
     *
     * @param string|null $apiKey PushHouse API key (null for open endpoints)
     * @param array $options Additional configuration options
     */
    public function __construct(?string $apiKey = null, array $options = [])
    {
        $baseUrl = $options['base_url'] ?? config('services.push_house.base_url', 'https://api.push.house/v1');

        // PushHouse specific options
        $pushHouseOptions = array_merge([
            'timeout' => 45,
            'rate_limit' => config('services.push_house.rate_limit', 1000),
            'max_retries' => 3,
            'retry_delay' => 2,
            'parser_name' => 'PushHouse',
            'requires_auth' => !empty($apiKey) // Authentication required only if API key provided
        ], $options);

        parent::__construct($baseUrl, $apiKey, $pushHouseOptions);
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
        $endpoint = $params['endpoint'] ?? 'ads';
        $status = $params['status'] ?? 'active'; // По умолчанию только активные
        $startPage = $params['start_page'] ?? 1;
        $maxPages = $params['max_pages'] ?? 100; // Защита от бесконечного цикла

        $allData = [];
        $currentPage = $startPage;

        while ($currentPage <= $maxPages) {
            // Формируем URL с пагинацией через path: /ads/5/active
            $url = "{$endpoint}/{$currentPage}/{$status}";

            try {
                Log::info("PushHouse: Fetching page {$currentPage}", [
                    'url' => $url,
                    'status' => $status
                ]);

                $response = $this->makeRequest($url);
                $data = $response->json();

                // Если ответ пустой массив - достигли конца пагинации
                if (empty($data) || !is_array($data)) {
                    Log::info("PushHouse: No more data on page {$currentPage}");
                    break;
                }

                // Форматируем и добавляем данные в общий массив
                $formattedData = $this->formatResult($data);
                $allData = array_merge($allData, $formattedData);

                Log::info("PushHouse: Retrieved " . count($data) . " items from page {$currentPage}");

                $currentPage++;

                // Throttling между запросами
                if ($currentPage <= $maxPages) {
                    usleep(500000); // 0.5 секунды задержка
                }
            } catch (\Exception $e) {
                Log::error("PushHouse: Error fetching page {$currentPage}", [
                    'error' => $e->getMessage(),
                    'url' => $url
                ]);

                // Если ошибка на первой странице - пробрасываем исключение
                if ($currentPage === $startPage) {
                    throw new ParserException("Failed to fetch data from PushHouse: " . $e->getMessage());
                }

                // Для остальных страниц - просто прерываем цикл
                break;
            }
        }

        Log::info("PushHouse: Total fetched items", [
            'count' => count($allData),
            'pages_processed' => $currentPage - $startPage
        ]);

        return $allData;
    }



    /**
     * Parse individual item from PushHouse API
     * Returns raw data as-is for DTO processing
     *
     * @param array $item Raw item data from API
     * @return array Raw item data (no transformation)
     */
    public function parseItem(array $item): array
    {
        // Return raw data as-is - transformation will be handled by PushHouseCreativeDTO
        return $item;
    }

    /**
     * Get feeds using simplified API (compatible with original implementation)
     * Returns raw data for DTO processing
     *
     * @return array Raw feeds array
     */
    public function getFeeds(): array
    {
        $feeds = [];
        $offset = 0;
        $maxPages = 100; // Защита от зацикливания

        while ($offset < $maxPages) {
            try {
                $url = "ads/{$offset}/active";
                $response = $this->makeRequest($url);
                $data = $response->json();

                if (empty($data)) {
                    break;
                }

                $feeds = array_merge($feeds, $this->formatResult($data));
                $offset++;

                // Rate limiting
                usleep(500000); // 0.5 сек

            } catch (\Exception $e) {
                Log::error("PushHouse getFeeds error on offset {$offset}: " . $e->getMessage());
                break;
            }
        }

        Log::info('PushHouse: Count feeds: ' . count($feeds));
        echo 'Count feeds: ' . count($feeds) . PHP_EOL;

        return $feeds;
    }

    /**
     * Format result data from API response
     * Returns raw data without transformation for DTO processing
     *
     * @param array $data Raw API response data
     * @return array Raw feeds array
     */
    private function formatResult(array $data): array
    {
        $feeds = [];
        foreach ($data as $feed) {
            // Конвертируем объект в массив если нужно
            $feedArray = is_object($feed) ? (array) $feed : $feed;
            // No transformation - return raw data for DTO processing
            $feeds[] = $feedArray;
        }
        return $feeds;
    }
}

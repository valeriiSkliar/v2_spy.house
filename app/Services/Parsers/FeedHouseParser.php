<?php

declare(strict_types=1);

namespace App\Services\Parsers;

use App\Services\Parsers\Exceptions\ParserException;
use App\Models\AdSource;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use Illuminate\Http\Client\Response;

/**
 * FeedHouse API Parser
 *
 * Парсер для извлечения данных из FeedHouse Business API
 * Поддерживает получение кампаний, объявлений и креативов
 * Использует модель AdSource для сохранения состояния парсинга (lastId)
 *
 * @package App\Services\Parsers
 * @author SeniorSoftwareEngineer
 * @version 1.0.0
 */
class FeedHouseParser extends BaseParser
{

    /**
     * Initialize FeedHouse parser
     *
     * @param string|null $apiKey FeedHouse API access token (null to use from config)
     * @param array $options Additional configuration options
     */
    public function __construct(?string $apiKey = null, array $options = [])
    {
        // Get values from config if not provided
        $apiKey = $apiKey ?? config('services.feedhouse.api_key');
        $baseUrl = $options['base_url'] ?? config('services.feedhouse.base_url', 'https://api.feed.house/internal/v1/feed-campaigns');

        // Validate required parameters
        if (empty($apiKey)) {
            throw new ParserException('FeedHouse API key is required. Set FEEDHOUSE_API_KEY in .env or pass as parameter');
        }

        // FeedHouse specific options
        $feedHouseOptions = array_merge([
            'timeout' => config('services.feedhouse.timeout', 60),
            'rate_limit' => config('services.feedhouse.rate_limit', 100),
            'max_retries' => config('services.feedhouse.max_retries', 3),
            'retry_delay' => config('services.feedhouse.retry_delay', 3),
            'parser_name' => 'FeedHouse'
        ], $options);

        parent::__construct($baseUrl, $apiKey, $feedHouseOptions);
    }

    /**
     * Fetch data from FeedHouse API with AdSource state management
     * Основной метод для работы с состоянием AdSource и порционной обработкой
     *
     * @param AdSource $adSource Модель источника для сохранения состояния
     * @param array $params Дополнительные параметры
     * @return array Результат парсинга
     */
    public function fetchWithStateManagement(AdSource $adSource, array $params = []): array
    {
        try {
            // 1. Обновляем статус на 'running'
            $adSource->update(['parser_status' => 'running']);

            // 2. Получаем lastId из состояния
            $lastId = $adSource->parser_state['lastId'] ?? null;

            // 3. Определяем режим работы
            $mode = $params['mode'] ?? 'regular';
            if ($mode === 'initial_scan') {
                $lastId = null; // Сброс для полного скана
            }

            // 4. Настройки пагинации для порционной обработки
            $batchSize = $params['batch_size'] ?? 200;
            $queueChunkSize = $params['queue_chunk_size'] ?? 50;
            $dryRun = $params['dry_run'] ?? false;
            $limit = $params['limit'] ?? $batchSize;
            $formats = $params['formats'] ?? ['push', 'inpage'];
            $adNetworks = $params['adNetworks'] ?? ['rollerads', 'richads'];

            // 5. Выполняем порционную обработку
            $processedCount = 0;
            $currentLastId = $lastId;
            $pageCount = 0;

            while (true) {
                $pageCount++;

                // Формируем параметры запроса
                $queryParams = [
                    'limit' => $limit,
                    'formats' => implode(',', $formats),
                    'adNetworks' => implode(',', $adNetworks)
                ];

                // Добавляем lastId только если он есть
                if ($currentLastId !== null) {
                    $queryParams['lastId'] = $currentLastId;
                }

                // Выполняем запрос через fetchData для правильной аутентификации
                $batch = $this->fetchData($queryParams);

                // Проверяем, есть ли данные
                if (empty($batch) || !is_array($batch)) {
                    Log::info("FeedHouse: No more data on page {$pageCount}");
                    break;
                }

                // 6. Немедленно обрабатываем порцию без накопления в памяти
                if (!$dryRun) {
                    $this->processBatchInChunks($batch, $queueChunkSize);
                }
                $processedCount += count($batch);

                // 7. КРИТИЧНО: Сохраняем состояние после каждой итерации
                $currentLastId = max(array_column($batch, 'id'));
                $adSource->parser_state = ['lastId' => $currentLastId];
                $adSource->save();

                Log::info("FeedHouse: Page {$pageCount} processed", [
                    'batch_size' => count($batch),
                    'total_processed' => $processedCount,
                    'lastId' => $currentLastId,
                    'dry_run' => $dryRun
                ]);

                // 8. Проверяем условия завершения
                $batchCount = count($batch);

                // 9. Освобождаем память
                unset($batch);

                if ($batchCount < $limit) {
                    Log::info("FeedHouse: Last page reached");
                    break;
                }

                // 10. Rate limiting между запросами
                usleep(500000); // 0.5 сек между запросами
            }

            // 11. Успешное завершение
            $adSource->update([
                'parser_status' => 'idle',
                'parser_last_error' => null,
                'parser_last_error_at' => null,
                'parser_last_error_message' => null
            ]);

            Log::info("FeedHouse: Parsing completed successfully", [
                'total_processed' => $processedCount,
                'final_lastId' => $currentLastId,
                'pages_processed' => $pageCount
            ]);

            // Возвращаем статистику вместо данных (порционный подход)
            return [
                'total_processed' => $processedCount,
                'final_last_id' => $currentLastId ?? null,
                'pages_processed' => $pageCount,
                'status' => 'completed'
            ];
        } catch (\Exception $e) {
            // 12. Обработка ошибок с сохранением в AdSource
            $adSource->update([
                'parser_status' => 'failed',
                'parser_last_error' => [
                    'message' => $e->getMessage(),
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                    'trace' => $e->getTraceAsString()
                ],
                'parser_last_error_at' => now(),
                'parser_last_error_message' => $e->getMessage(),
                'parser_last_error_code' => $e->getCode(),
            ]);

            Log::error("FeedHouse: Parsing failed", [
                'error' => $e->getMessage(),
                'adSource_id' => $adSource->id,
                'last_successful_lastId' => $currentLastId ?? 'none'
            ]);

            throw new ParserException("FeedHouse parsing failed: " . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Fetch data from FeedHouse API (без state management)
     *
     * @param array $params Request parameters
     * @return array Fetched data
     * @throws ParserException
     */
    public function fetchData(array $params = []): array
    {
        try {
            // FeedHouse API использует простые GET запросы с query parameters
            $queryParams = [
                'limit' => $params['limit'] ?? 5,
            ];

            // Обрабатываем formats - может прийти как массив или строка
            if (isset($params['formats'])) {
                $queryParams['formats'] = is_array($params['formats'])
                    ? implode(',', $params['formats'])
                    : $params['formats'];
            } else {
                $queryParams['formats'] = 'push,inpage';
            }

            // Обрабатываем adNetworks - может прийти как массив или строка
            if (isset($params['adNetworks'])) {
                $queryParams['adNetworks'] = is_array($params['adNetworks'])
                    ? implode(',', $params['adNetworks'])
                    : $params['adNetworks'];
            } else {
                $queryParams['adNetworks'] = 'rollerads,richads';
            }

            // Добавляем API key в зависимости от настройки auth_method
            $authMethod = config('services.feedhouse.auth_method', 'header');
            if ($authMethod === 'query') {
                $queryParams['key'] = $this->apiKey;
            }

            // Добавляем lastId только если он есть и не null
            if (!empty($params['lastId'])) {
                $queryParams['lastId'] = $params['lastId'];
            }

            Log::info("FeedHouse: Making native curl request", [
                'query_params' => $queryParams,
                'auth_method' => $authMethod,
                'base_url' => $this->baseUrl
            ]);

            // Используем нативный curl для FeedHouse 
            $data = $this->makeNativeCurlRequest($queryParams);

            if (!is_array($data)) {
                throw new ParserException("Invalid response format from FeedHouse API");
            }

            Log::info("FeedHouse: Data fetched successfully", [
                'items_count' => count($data),
                'lastId' => $params['lastId'] ?? 'none'
            ]);

            return $data;
        } catch (\Exception $e) {
            Log::error("FeedHouse: Fetch failed", [
                'error' => $e->getMessage(),
                'error_code' => $e->getCode(),
                'error_class' => get_class($e),
                'params' => $params
            ]);

            // Специальная обработка rate limit ошибок
            if ($e->getCode() === 429) {
                Log::info("FeedHouse: Rate limit detected, throwing RateLimitException", [
                    'original_message' => $e->getMessage(),
                    'code' => $e->getCode()
                ]);
                throw new \App\Services\Parsers\Exceptions\RateLimitException(
                    "FeedHouse rate limit exceeded: " . $e->getMessage(),
                    60 // retry after 60 seconds
                );
            }

            throw new ParserException("FeedHouse fetch failed: " . $e->getMessage(), $e->getCode(), $e);
        }
    }

    /**
     * Специальный метод для FeedHouse API с использованием нативного curl
     */
    private function makeNativeCurlRequest(array $params): array
    {
        $fullUrl = $this->baseUrl . '?' . http_build_query($params);

        // Получаем заголовки аутентификации
        $authHeaders = $this->getAuthHeaders();
        $headers = [];
        foreach ($authHeaders as $name => $value) {
            $headers[] = "{$name}: {$value}";
        }

        Log::info("FeedHouse: Native curl request", [
            'full_url' => $fullUrl,
            'headers' => $headers
        ]);

        $curl = curl_init();
        curl_setopt_array($curl, [
            CURLOPT_URL => $fullUrl,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => $this->timeout,
            CURLOPT_CUSTOMREQUEST => 'GET',
            CURLOPT_HTTPHEADER => $headers,
        ]);

        $responseBody = curl_exec($curl);
        $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        $curlError = curl_error($curl);
        curl_close($curl);

        if ($curlError) {
            throw new ParserException("Curl error: " . $curlError);
        }

        // Пытаемся декодировать JSON независимо от HTTP кода
        $data = json_decode($responseBody, true);

        // Если JSON валидный, проверяем его на ошибки FeedHouse API
        if (json_last_error() === JSON_ERROR_NONE && is_array($data) && isset($data['error'])) {
            $errorMessage = $data['message'] ?? 'Unknown error';
            $errorCode = 0; // По умолчанию

            // Логируем структуру ошибки для отладки
            Log::debug("FeedHouse API Error structure", [
                'error_field' => $data['error'],
                'message_field' => $data['message'] ?? null,
                'full_response' => $data
            ]);

            // Извлекаем числовой код из строки вида "code=429, message=..."
            if (is_string($data['error']) && preg_match('/code=(\d+)/', $data['error'], $matches)) {
                $errorCode = (int)$matches[1];
                Log::debug("FeedHouse: Extracted error code", ['code' => $errorCode]);
            } elseif (isset($data['code'])) {
                // Альтернативный путь - прямое поле code
                $errorCode = (int)$data['code'];
                Log::debug("FeedHouse: Found direct error code", ['code' => $errorCode]);
            }

            throw new ParserException("FeedHouse API Error {$errorCode}: {$errorMessage}", $errorCode);
        }

        // Fallback: если JSON невалидный или HTTP код не 200
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new ParserException("JSON decode error: " . json_last_error_msg() . " (HTTP {$httpCode})");
        }

        if ($httpCode !== 200) {
            throw new ParserException("HTTP {$httpCode}: {$responseBody}", $httpCode);
        }

        if (!is_array($data)) {
            throw new ParserException("Invalid response format from FeedHouse API");
        }

        return $data;
    }

    /**
     * Parse individual item from FeedHouse API
     *
     * @param array $item Raw item data from API
     * @return array Parsed item data
     */
    public function parseItem(array $item): array
    {
        // Простое преобразование - детальная обработка будет в DTO
        return $item;
    }

    /**
     * Обрабатывает порцию данных и отправляет в очереди (порционная обработка)
     */
    private function processBatchInChunks(array $batch, int $chunkSize): void
    {
        $chunks = array_chunk($batch, $chunkSize);

        foreach ($chunks as $chunk) {
            // Обрабатываем каждый элемент через DTO
            $processedItems = [];
            foreach ($chunk as $item) {
                $parsedItem = $this->parseItem($item);
                if (!empty($parsedItem)) {
                    $processedItems[] = $parsedItem;
                }
            }

            // TODO: Отправляем в очередь для постобработки
            // ProcessFeedHouseCreativesJob::dispatch($processedItems);

            Log::info("FeedHouse: Batch chunk processed", [
                'items_count' => count($processedItems),
                'chunk_size' => count($chunk)
            ]);
        }
    }

    /**
     * FeedHouse поддерживает два метода аутентификации:
     * 1. Query parameter: ?key=api_key (по умолчанию)
     * 2. Header: X-Api-Key: api_key
     */
    protected function getAuthHeaders(): array
    {
        $headers = [
            'Accept' => '*/*',  // Используем как в curl
            'User-Agent' => 'curl/8.9.1'  // Имитируем curl для совместимости
        ];

        // Если используется header аутентификация
        $authMethod = config('services.feedhouse.auth_method', 'query');
        if ($authMethod === 'header' && !empty($this->apiKey)) {
            $headerName = config('services.feedhouse.auth_header_name', 'X-Api-Key');
            $headers[$headerName] = $this->apiKey;
        }

        return $headers;
    }
}

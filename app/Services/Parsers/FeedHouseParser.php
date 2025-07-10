<?php

declare(strict_types=1);

namespace App\Services\Parsers;

use App\Services\Parsers\Exceptions\ParserException;
use App\Models\AdSource;
use App\Http\DTOs\Parsers\FeedHouseCreativeDTO;
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
     * НОВАЯ ЛОГИКА: One-shot режим для периодических запусков через Scheduler
     * За один запуск обрабатывает максимум N элементов, затем останавливается
     *
     * @param AdSource $adSource Модель источника для сохранения состояния
     * @param array $params Дополнительные параметры
     * @return array Результат парсинга
     */
    public function fetchWithStateManagement(AdSource $adSource, array $params = []): array
    {
        try {
            // 1. Проверяем, не запущен ли уже парсер
            if ($adSource->parser_status === 'running') {
                $timeDiff = now()->diffInMinutes($adSource->updated_at);
                if ($timeDiff < 10) { // Если меньше 10 минут, считаем что еще выполняется
                    Log::info("FeedHouse: Parser already running, skipping", [
                        'adSource_id' => $adSource->id,
                        'running_for_minutes' => $timeDiff
                    ]);
                    return [
                        'total_processed' => 0,
                        'status' => 'skipped',
                        'reason' => 'already_running',
                        'running_for_minutes' => $timeDiff
                    ];
                } else {
                    // Сброс зависшего статуса
                    Log::warning("FeedHouse: Resetting stuck parser status", [
                        'adSource_id' => $adSource->id,
                        'stuck_for_minutes' => $timeDiff
                    ]);
                }
            }

            // 2. Обновляем статус на 'running'
            $adSource->update(['parser_status' => 'running']);

            // 3. Получаем lastId из состояния
            $lastId = $adSource->parser_state['lastId'] ?? null;

            // 4. Определяем режим работы
            $mode = $params['mode'] ?? 'regular';
            if ($mode === 'initial_scan') {
                $lastId = null; // Сброс для полного скана
            }

            // 5. НОВЫЕ НАСТРОЙКИ: One-shot режим
            $isOneShot = $params['one_shot'] ?? true; // По умолчанию one-shot
            $maxItemsPerRun = $params['max_items_per_run'] ?? 1000; // 1000 элементов за запуск
            $batchSize = $params['batch_size'] ?? 200; // Размер страницы API
            $dryRun = $params['dry_run'] ?? false;
            $formats = $params['formats'] ?? ['push', 'inpage'];
            $adNetworks = $params['adNetworks'] ?? $this->getActiveNetworks();

            Log::info("FeedHouse: Starting one-shot parsing cycle", [
                'mode' => $mode,
                'one_shot' => $isOneShot,
                'max_items_per_run' => $maxItemsPerRun,
                'batch_size' => $batchSize,
                'starting_lastId' => $lastId,
                'dry_run' => $dryRun
            ]);

            // 6. One-shot обработка
            if ($isOneShot) {
                return $this->performOneShotProcessing(
                    $adSource,
                    $lastId,
                    $maxItemsPerRun,
                    $batchSize,
                    $formats,
                    $adNetworks,
                    $dryRun
                );
            } else {
                // 7. Fallback: Старая логика для backward compatibility
                return $this->performContinuousProcessing(
                    $adSource,
                    $lastId,
                    $params
                );
            }
        } catch (\Exception $e) {
            // 8. Обработка ошибок с сохранением в AdSource
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
                'adSource_id' => $adSource->id
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
                $queryParams['adNetworks'] = implode(',', $this->getActiveNetworks());
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

            Log::info("FeedHouse: Making request via BaseParser integration", [
                'query_params' => $queryParams,
                'auth_method' => $authMethod
            ]);

            // Используем интегрированный makeRequest() вместо нативного curl
            $response = $this->makeRequest('', $queryParams, 'GET');

            // Декодируем JSON ответ
            $data = json_decode($response->body(), true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new ParserException("JSON decode error: " . json_last_error_msg());
            }

            if (!is_array($data)) {
                throw new ParserException("Invalid response format from FeedHouse API");
            }

            Log::info("FeedHouse: Data fetched successfully via BaseParser", [
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
     * Override BaseParser makeRequest to use native curl for FeedHouse API compatibility
     * Интегрирует нативный curl с полной логикой BaseParser (rate limit, retry, logging)
     *
     * @param string $endpoint API endpoint path  
     * @param array $params Query parameters
     * @param string $method HTTP method (GET, POST, etc.)
     * @param array $headers Additional headers
     * @param int $retries Number of retry attempts
     * 
     * @return \Illuminate\Http\Client\Response Mock response object for compatibility
     * @throws ParserException On unrecoverable errors
     * @throws RateLimitException When rate limit is exceeded
     */
    protected function makeRequest(
        string $endpoint,
        array $params = [],
        string $method = 'GET',
        array $headers = [],
        ?int $retries = null
    ): \Illuminate\Http\Client\Response {
        $retries = $retries ?? $this->maxRetries;
        $url = $this->baseUrl . '/' . ltrim($endpoint, '/');

        // 1. ПРИМЕНЯЕМ RATE LIMITING из BaseParser
        $this->checkRateLimit();

        // 2. Подготавливаем заголовки с аутентификацией
        $headers = array_merge($this->getAuthHeaders(), $headers);

        // 3. ЛОГИРУЕМ ЗАПРОС через BaseParser
        $this->logRequest($url, $params, $method);

        // 4. RETRY ЛОГИКА с exponential backoff из BaseParser
        for ($attempt = 1; $attempt <= $retries + 1; $attempt++) {
            try {
                // Используем нативный curl для совместимости с FeedHouse API
                $responseData = $this->makeNativeCurlRequestWithRetry($url, $params, $headers, $attempt);

                // Создаем mock Response объект для совместимости с BaseParser
                $mockResponse = $this->createMockResponse($responseData['body'], $responseData['http_code']);

                // 5. ЛОГИРУЕМ ОТВЕТ через BaseParser
                $this->logResponse($mockResponse, $attempt);

                // Handle successful response
                if ($mockResponse->successful()) {
                    // 6. ОБНОВЛЯЕМ RATE LIMIT COUNTER из BaseParser
                    $this->updateRateLimitCounter();
                    return $mockResponse;
                }

                // 7. ОБРАБОТКА ОШИБОК через BaseParser
                $this->handleError($mockResponse);

                // If we reach here, it's a retryable error
                if ($attempt <= $retries) {
                    $this->logRetry($attempt, $retries, $mockResponse->status());
                    sleep($this->retryDelay * $attempt); // Exponential backoff из конфига
                }
            } catch (\Exception $e) {
                if ($attempt <= $retries) {
                    $this->logRetry($attempt, $retries, 0, $e->getMessage());
                    sleep($this->retryDelay * $attempt); // Используем retry_delay из конфига
                } else {
                    throw new ParserException("Request failed after {$retries} retries: " . $e->getMessage(), 0, $e);
                }
            }
        }

        throw new ParserException("Request failed after {$retries} retries");
    }

    /**
     * Нативный curl запрос, адаптированный для интеграции с BaseParser
     * 
     * @param string $fullUrl Полный URL с query параметрами
     * @param array $params Query параметры
     * @param array $headers Заголовки запроса
     * @param int $attempt Номер попытки (для логирования)
     * @return array ['body' => string, 'http_code' => int]
     * @throws ParserException
     */
    private function makeNativeCurlRequestWithRetry(string $baseUrl, array $params, array $headers, int $attempt): array
    {
        // Формируем URL с query параметрами для FeedHouse API
        $fullUrl = $baseUrl;
        if (!empty($params)) {
            $fullUrl .= '?' . http_build_query($params);
        }

        // Конвертируем заголовки в формат для curl
        $curlHeaders = [];
        foreach ($headers as $name => $value) {
            $curlHeaders[] = "{$name}: {$value}";
        }

        Log::info("FeedHouse: Native curl request (attempt {$attempt})", [
            'full_url' => $fullUrl,
            'headers' => $curlHeaders,
            'timeout' => $this->timeout
        ]);

        $curl = curl_init();
        curl_setopt_array($curl, [
            CURLOPT_URL => $fullUrl,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => $this->timeout, // Используем timeout из конфига
            CURLOPT_CUSTOMREQUEST => 'GET',
            CURLOPT_HTTPHEADER => $curlHeaders,
        ]);

        $responseBody = curl_exec($curl);
        $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        $curlError = curl_error($curl);
        curl_close($curl);

        if ($curlError) {
            throw new ParserException("Curl error: " . $curlError);
        }

        return [
            'body' => $responseBody,
            'http_code' => $httpCode
        ];
    }

    /**
     * Создает mock объект Response для совместимости с BaseParser
     * Включает специальную обработку ошибок FeedHouse API
     * 
     * @param string $body Тело ответа
     * @param int $statusCode HTTP статус код
     * @return \Illuminate\Http\Client\Response
     * @throws ParserException Для FeedHouse API ошибок
     */
    private function createMockResponse(string $body, int $statusCode): \Illuminate\Http\Client\Response
    {
        // Создаем mock response используя Laravel HTTP Client структуры
        $response = new \Illuminate\Http\Client\Response(
            new \GuzzleHttp\Psr7\Response($statusCode, [], $body)
        );

        // Специальная обработка ошибок FeedHouse API
        // FeedHouse возвращает ошибки в JSON формате даже при HTTP 200
        if (!empty($body)) {
            $data = json_decode($body, true);

            if (json_last_error() === JSON_ERROR_NONE && is_array($data) && isset($data['error'])) {
                $errorMessage = $data['message'] ?? 'Unknown error';
                $errorCode = 0; // По умолчанию

                // Логируем структуру ошибки для отладки
                Log::debug("FeedHouse API Error structure", [
                    'error_field' => $data['error'],
                    'message_field' => $data['message'] ?? null,
                    'full_response' => $data,
                    'http_status' => $statusCode
                ]);

                // Извлекаем числовой код из строки вида "code=429, message=..."
                if (is_string($data['error']) && preg_match('/code=(\d+)/', $data['error'], $matches)) {
                    $errorCode = (int)$matches[1];
                    Log::debug("FeedHouse: Extracted error code from string", ['code' => $errorCode]);
                } elseif (isset($data['code'])) {
                    // Альтернативный путь - прямое поле code
                    $errorCode = (int)$data['code'];
                    Log::debug("FeedHouse: Found direct error code", ['code' => $errorCode]);
                }

                // Пересоздаем response с корректным HTTP кодом для BaseParser
                if ($errorCode > 0) {
                    $response = new \Illuminate\Http\Client\Response(
                        new \GuzzleHttp\Psr7\Response($errorCode, [], $body)
                    );
                    Log::info("FeedHouse: Updated HTTP status from {$statusCode} to {$errorCode} based on API error");
                }
            }
        }

        return $response;
    }

    /**
     * Parse individual item from FeedHouse API с валидацией через DTO
     *
     * @param array $item Raw item data from API
     * @return array Parsed item data (пустой массив если валидация не прошла)
     */
    public function parseItem(array $item): array
    {
        try {
            // Создаем DTO из данных API
            $dto = FeedHouseCreativeDTO::fromApiResponse($item);

            // ОБЯЗАТЕЛЬНАЯ валидация с проверкой изображений
            if (!$dto->isValid(validateImages: true)) {
                // Дополнительная диагностика причин отклонения
                $rejectionReasons = [];

                if (empty($dto->externalId)) $rejectionReasons[] = 'empty_external_id';
                if (empty($dto->title) && empty($dto->text)) $rejectionReasons[] = 'empty_title_and_text';
                if (empty($dto->iconUrl) && empty($dto->imageUrl)) $rejectionReasons[] = 'no_images';
                if (!in_array($dto->format->value, FeedHouseCreativeDTO::getSupportedFormats(), true)) $rejectionReasons[] = 'unsupported_format';
                if (!empty($dto->targetUrl) && !filter_var($dto->targetUrl, FILTER_VALIDATE_URL)) $rejectionReasons[] = 'invalid_landing_url';

                // Если базовая валидация прошла, значит проблема с изображениями
                if (empty($rejectionReasons)) $rejectionReasons[] = 'image_validation_failed';

                Log::info("FeedHouse: Creative failed validation", [
                    'external_id' => $dto->externalId,
                    'title' => $dto->title,
                    'icon_url' => $dto->iconUrl,
                    'image_url' => $dto->imageUrl,
                    'format' => $dto->format->value,
                    'rejection_reasons' => $rejectionReasons
                ]);
                return []; // Возвращаем пустой массив для невалидных элементов
            }

            // Возвращаем данные для БД если валидация прошла
            return $dto->toBasicDatabase();
        } catch (\Exception $e) {
            Log::error("FeedHouse: Failed to parse item", [
                'item_id' => $item['id'] ?? 'unknown',
                'error' => $e->getMessage(),
                'raw_item' => $item
            ]);
            return [];
        }
    }

    /**
     * Обрабатывает порцию данных и отправляет в очереди (порционная обработка)
     * ГАРАНТИРУЕТ валидацию изображений для всех элементов
     */
    private function processBatchInChunks(array $batch, int $chunkSize, bool $dryRun = false): void
    {
        $chunks = array_chunk($batch, $chunkSize);
        $totalProcessed = 0;
        $totalValidated = 0;
        $totalRejected = 0;

        foreach ($chunks as $chunkIndex => $chunk) {
            // Обрабатываем каждый элемент через DTO с ОБЯЗАТЕЛЬНОЙ валидацией изображений
            $processedItems = [];
            $chunkValidated = 0;
            $chunkRejected = 0;

            foreach ($chunk as $item) {
                $chunkValidated++;
                $parsedItem = $this->parseItem($item); // DTO + валидация изображений

                if (!empty($parsedItem)) {
                    $processedItems[] = $parsedItem;
                } else {
                    $chunkRejected++;
                }
            }

            $totalValidated += $chunkValidated;
            $totalRejected += $chunkRejected;
            $totalProcessed += count($processedItems);

            // Отправляем в очередь для постобработки (только если не dry run)
            if (!$dryRun && !empty($processedItems)) {
                // TODO: ProcessFeedHouseCreativesJob::dispatch($processedItems);
                Log::debug("FeedHouse: Would dispatch to queue", [
                    'items_count' => count($processedItems)
                ]);
            } elseif ($dryRun && !empty($processedItems)) {
                Log::debug("FeedHouse: DRY RUN - skipping queue dispatch", [
                    'items_count' => count($processedItems)
                ]);
            }

            Log::info("FeedHouse: Batch chunk processed with image validation", [
                'chunk_index' => $chunkIndex + 1,
                'chunk_size' => count($chunk),
                'validated_items' => $chunkValidated,
                'passed_validation' => count($processedItems),
                'failed_validation' => $chunkRejected,
                'validation_pass_rate' => $chunkValidated > 0 ? round((count($processedItems) / $chunkValidated) * 100, 2) . '%' : '0%'
            ]);
        }

        Log::info("FeedHouse: Batch processing completed with image validation", [
            'total_items_processed' => $totalValidated,
            'total_passed_validation' => $totalProcessed,
            'total_failed_validation' => $totalRejected,
            'overall_pass_rate' => $totalValidated > 0 ? round(($totalProcessed / $totalValidated) * 100, 2) . '%' : '0%'
        ]);
    }

    /**
     * FeedHouse поддерживает два метода аутентификации:
     * 1. Query parameter: ?key=api_key (по умолчанию)
     * 2. Header: X-Api-Key: api_key
     * 
     * Переопределяем BaseParser getAuthHeaders() для FeedHouse специфики
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

    /**
     * НОВЫЙ МЕТОД: One-shot обработка для Scheduler режима
     * Получает максимум N элементов за один запуск, затем останавливается
     */
    private function performOneShotProcessing(
        AdSource $adSource,
        ?int $lastId,
        int $maxItemsPerRun,
        int $batchSize,
        array $formats,
        array $adNetworks,
        bool $dryRun
    ): array {
        $processedCount = 0;
        $currentLastId = $lastId;
        $batchCount = 0;
        $startTime = microtime(true);

        while ($processedCount < $maxItemsPerRun) {
            $batchCount++;

            // Рассчитываем размер текущей порции
            $remainingItems = $maxItemsPerRun - $processedCount;
            $currentBatchSize = min($batchSize, $remainingItems);

            // Формируем параметры запроса
            $queryParams = [
                'limit' => $currentBatchSize,
                'formats' => implode(',', $formats),
                'adNetworks' => implode(',', $adNetworks)
            ];

            if ($currentLastId !== null) {
                $queryParams['lastId'] = $currentLastId;
            }

            Log::info("FeedHouse: One-shot batch {$batchCount}", [
                'current_batch_size' => $currentBatchSize,
                'processed_so_far' => $processedCount,
                'remaining' => $remainingItems,
                'lastId' => $currentLastId
            ]);

            // Получаем данные
            $batch = $this->fetchData($queryParams);

            // Проверяем наличие данных
            if (empty($batch) || !is_array($batch)) {
                Log::info("FeedHouse: No more data available, ending one-shot cycle", [
                    'processed_total' => $processedCount,
                    'batches_processed' => $batchCount
                ]);
                break;
            }

            // Обрабатываем порцию (валидация выполняется ВСЕГДА, независимо от dry run)
            $this->processBatchInChunks($batch, 50, $dryRun); // Фиксированный chunk size

            // В dry run режиме логируем что НЕ отправляем в очереди
            if ($dryRun) {
                Log::info("FeedHouse: DRY RUN - validation performed but data not queued", [
                    'batch_size' => count($batch)
                ]);
            }

            $batchSize = count($batch);
            $processedCount += $batchSize;

            // Обновляем lastId
            $currentLastId = max(array_column($batch, 'id'));

            // КРИТИЧНО: Сохраняем состояние после каждой порции
            $adSource->parser_state = ['lastId' => $currentLastId];
            $adSource->save();

            Log::info("FeedHouse: One-shot batch processed", [
                'batch_number' => $batchCount,
                'batch_size' => $batchSize,
                'total_processed' => $processedCount,
                'new_lastId' => $currentLastId,
                'max_target' => $maxItemsPerRun
            ]);

            // Если получили меньше запрошенного - достигли конца
            if ($batchSize < $currentBatchSize) {
                Log::info("FeedHouse: Reached end of available data", [
                    'requested' => $currentBatchSize,
                    'received' => $batchSize
                ]);
                break;
            }

            // Rate limiting между запросами
            usleep(500000); // 0.5 сек

            // Освобождаем память
            unset($batch);
        }

        $duration = microtime(true) - $startTime;

        // Успешное завершение
        $adSource->update([
            'parser_status' => 'idle',
            'parser_last_error' => null,
            'parser_last_error_at' => null,
            'parser_last_error_message' => null
        ]);

        $result = [
            'total_processed' => $processedCount,
            'final_last_id' => $currentLastId,
            'batches_processed' => $batchCount,
            'status' => 'completed',
            'mode' => 'one_shot',
            'max_items_per_run' => $maxItemsPerRun,
            'duration_seconds' => round($duration, 2),
            'reached_limit' => $processedCount >= $maxItemsPerRun,
            'reached_end' => $processedCount < $maxItemsPerRun
        ];

        Log::info("FeedHouse: One-shot parsing completed", $result);

        return $result;
    }

    /**
     * СТАРЫЙ МЕТОД: Continuous обработка (backward compatibility)
     * Обрабатывает все доступные данные за один запуск
     */
    private function performContinuousProcessing(AdSource $adSource, ?int $lastId, array $params): array
    {
        Log::info("FeedHouse: Using legacy continuous processing mode");

        // Здесь можно оставить старую логику или упростить
        // Для краткости возвращаем заглушку
        return [
            'total_processed' => 0,
            'status' => 'legacy_mode_not_implemented',
            'mode' => 'continuous'
        ];
    }

    /**
     * Получить активные сети из БД или fallback значения
     */
    private function getActiveNetworks(): array
    {
        try {
            return \App\Models\AdvertismentNetwork::getDefaultNetworksForParser();
        } catch (\Exception $e) {
            // В случае ошибки (например, БД недоступна), используем fallback
            Log::warning("Failed to get active networks from database, using fallback", [
                'error' => $e->getMessage()
            ]);

            return ['rollerads', 'richads'];
        }
    }
}

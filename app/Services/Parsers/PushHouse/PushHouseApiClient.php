<?php

declare(strict_types=1);

namespace App\Services\Parsers\PushHouse;

use App\Http\DTOs\Parsers\PushHouseCreativeDTO;
use App\Services\Parsers\Exceptions\ParserException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Collection;

/**
 * Push.House API Client
 * 
 * Специализированный HTTP клиент для работы с Push.House API
 * Поддерживает специфичную пагинацию через path-параметры (/ads/{page}/{status})
 * и преобразование ответов в коллекцию PushHouseCreativeDTO
 * 
 * @package App\Services\Parsers\PushHouse
 * @author SeniorSoftwareEngineer
 * @version 1.0.0
 */
class PushHouseApiClient
{
    private string $baseUrl;
    private int $timeout;
    private int $maxRetries;
    private int $retryDelay;
    private int $maxPages;
    private int $rateLimitDelay; // microseconds

    /**
     * Initialize Push.House API client
     *
     * @param array $config Configuration from services.push_house
     */
    public function __construct(array $config = [])
    {
        $this->baseUrl = rtrim($config['base_url'] ?? config('services.push_house.base_url', 'https://api.push.house'), '/');
        $this->timeout = (int)($config['timeout'] ?? config('services.push_house.timeout', 45));
        $this->maxRetries = (int)($config['max_retries'] ?? config('services.push_house.max_retries', 3));
        $this->retryDelay = (int)($config['retry_delay'] ?? config('services.push_house.retry_delay', 2));
        $this->maxPages = (int)($config['max_pages'] ?? 100); // Защита от зацикливания
        $this->rateLimitDelay = 500000; // 0.5 секунды между запросами (как в существующем парсере)
    }

    /**
     * Получить все активные креативы с пагинацией
     *
     * @param string $status Статус креативов (active, inactive, all)
     * @param int $startPage Начальная страница (по умолчанию 1)
     * @return Collection<PushHouseCreativeDTO> Коллекция валидных DTO
     * @throws ParserException
     */
    public function fetchAllCreatives(string $status = 'active', int $startPage = 1): Collection
    {
        $allCreatives = collect();
        $currentPage = $startPage;

        Log::info("PushHouse API: Starting fetch", [
            'status' => $status,
            'start_page' => $startPage,
            'max_pages' => $this->maxPages
        ]);

        while ($currentPage <= $this->maxPages) {
            try {
                $pageData = $this->fetchPage($currentPage, $status);

                // Если страница пустая - достигли конца пагинации
                if ($pageData->isEmpty()) {
                    Log::info("PushHouse API: No more data", ['page' => $currentPage]);
                    break;
                }

                $allCreatives = $allCreatives->concat($pageData);

                Log::info("PushHouse API: Page fetched", [
                    'page' => $currentPage,
                    'items' => $pageData->count(),
                    'total' => $allCreatives->count()
                ]);

                $currentPage++;

                // Rate limiting между запросами (кроме последней страницы)
                if ($currentPage <= $this->maxPages) {
                    usleep($this->rateLimitDelay);
                }
            } catch (\Exception $e) {
                Log::error("PushHouse API: Page fetch error", [
                    'page' => $currentPage,
                    'error' => $e->getMessage()
                ]);

                // Если ошибка на первой странице - пробрасываем исключение
                if ($currentPage === $startPage) {
                    throw new ParserException("Failed to fetch first page from Push.House: " . $e->getMessage(), 0, $e);
                }

                // Для остальных страниц - прерываем цикл (частичные данные лучше чем никаких)
                break;
            }
        }

        Log::info("PushHouse API: Fetch completed", [
            'total_items' => $allCreatives->count(),
            'pages_processed' => $currentPage - $startPage,
            'status' => $status
        ]);

        return $allCreatives;
    }

    /**
     * Получить данные с одной страницы
     *
     * @param int $page Номер страницы (начиная с 1)
     * @param string $status Статус креативов
     * @return Collection<PushHouseCreativeDTO> Коллекция DTO с этой страницы
     * @throws ParserException
     */
    public function fetchPage(int $page, string $status = 'active'): Collection
    {
        // Push.House использует специфичную структуру URL: /v1/ads/{page}/{status}
        $endpoint = "/v1/ads/{$page}/{$status}";
        $url = $this->baseUrl . $endpoint;

        $response = $this->makeRequest($url);
        $rawData = $response->json();

        // API возвращает массив объектов или пустой массив в конце пагинации
        if (!is_array($rawData)) {
            Log::warning("PushHouse API: Unexpected response format", [
                'page' => $page,
                'response_type' => gettype($rawData)
            ]);
            return collect();
        }

        // Если пустой массив - конец пагинации
        if (empty($rawData)) {
            return collect();
        }

        // Преобразуем сырые данные в DTO и фильтруем валидные
        return collect($rawData)
            ->map(function ($item) use ($page) {
                try {
                    return PushHouseCreativeDTO::fromApiResponse($item);
                } catch (\Exception $e) {
                    Log::warning("PushHouse API: DTO creation failed", [
                        'page' => $page,
                        'item_id' => $item['id'] ?? 'unknown',
                        'error' => $e->getMessage()
                    ]);
                    return null;
                }
            })
            ->filter() // Убираем null значения
            ->filter(fn($dto) => $dto->isValid()); // Убираем невалидные DTO
    }

    /**
     * Выполнить HTTP запрос с retry логикой
     *
     * @param string $url Полный URL
     * @return Response
     * @throws ParserException
     */
    private function makeRequest(string $url): Response
    {
        $attempt = 1;

        while ($attempt <= $this->maxRetries + 1) {
            try {
                Log::debug("PushHouse API: Making request", [
                    'url' => $url,
                    'attempt' => $attempt
                ]);

                $response = Http::timeout($this->timeout)
                    ->withHeaders([
                        'Accept' => 'application/json',
                        'User-Agent' => 'SpyHouse-PushHouse-Client/1.0'
                    ])
                    ->get($url);

                // Логируем ответ
                Log::debug("PushHouse API: Response received", [
                    'url' => $url,
                    'status' => $response->status(),
                    'size' => strlen($response->body()),
                    'attempt' => $attempt
                ]);

                // Обрабатываем успешный ответ
                if ($response->successful()) {
                    return $response;
                }

                // Обрабатываем специфичные ошибки
                $this->handleErrorResponse($response, $attempt);

                // Если дошли сюда - это retryable ошибка
                if ($attempt <= $this->maxRetries) {
                    $delay = $this->retryDelay * $attempt; // Exponential backoff
                    Log::warning("PushHouse API: Retrying request", [
                        'url' => $url,
                        'attempt' => $attempt,
                        'delay' => $delay,
                        'status' => $response->status()
                    ]);
                    sleep($delay);
                }
            } catch (\Exception $e) {
                if ($attempt <= $this->maxRetries) {
                    $delay = $this->retryDelay * $attempt;
                    Log::warning("PushHouse API: Exception, retrying", [
                        'url' => $url,
                        'attempt' => $attempt,
                        'delay' => $delay,
                        'error' => $e->getMessage()
                    ]);
                    sleep($delay);
                } else {
                    throw new ParserException("Request failed after {$this->maxRetries} retries: " . $e->getMessage(), 0, $e);
                }
            }

            $attempt++;
        }

        throw new ParserException("Request failed after {$this->maxRetries} retries");
    }

    /**
     * Обработка ошибочных ответов
     *
     * @param Response $response
     * @param int $attempt
     * @throws ParserException
     */
    private function handleErrorResponse(Response $response, int $attempt): void
    {
        $statusCode = $response->status();
        $responseBody = $response->body();

        switch ($statusCode) {
            case 404:
                // 404 может означать конец пагинации - это нормально
                throw new ParserException("Endpoint not found (end of pagination?): {$responseBody}");

            case 429:
                // Rate limit - увеличиваем задержку
                $retryAfter = $response->header('Retry-After', 60);
                Log::warning("PushHouse API: Rate limit hit", [
                    'retry_after' => $retryAfter,
                    'attempt' => $attempt
                ]);
                sleep((int)$retryAfter);
                return; // Позволяем retry

            case 500:
            case 502:
            case 503:
            case 504:
                // Server errors - retryable
                Log::warning("PushHouse API: Server error", [
                    'status' => $statusCode,
                    'attempt' => $attempt
                ]);
                return; // Позволяем retry

            default:
                throw new ParserException("HTTP {$statusCode}: {$responseBody}");
        }
    }

    /**
     * Получить статистику клиента
     *
     * @return array
     */
    public function getStats(): array
    {
        return [
            'base_url' => $this->baseUrl,
            'timeout' => $this->timeout,
            'max_retries' => $this->maxRetries,
            'max_pages' => $this->maxPages,
            'rate_limit_delay_ms' => $this->rateLimitDelay / 1000
        ];
    }

    /**
     * Тестирование соединения с API
     *
     * @return bool true если API доступен
     */
    public function testConnection(): bool
    {
        try {
            $response = $this->makeRequest($this->baseUrl . '/v1/ads/1/active');
            return $response->successful();
        } catch (\Exception $e) {
            Log::error("PushHouse API: Connection test failed", [
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }
}

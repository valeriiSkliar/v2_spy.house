<?php

declare(strict_types=1);

namespace App\Services\Parsers\PushHouse;

use App\Services\Parsers\Exceptions\ParserException;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Collection;

/**
 * Push.House Parsing Service
 * 
 * Главный координатор процесса парсинга Push.House
 * Объединяет ApiClient и Synchronizer для выполнения полного workflow:
 * 1. Получение данных от API
 * 2. Преобразование в DTO
 * 3. Синхронизация с БД
 * 4. Подготовка данных для Jobs
 * 
 * @package App\Services\Parsers\PushHouse
 * @author SeniorSoftwareEngineer
 * @version 1.0.0
 */
class PushHouseParsingService
{
    private PushHouseApiClient $apiClient;
    private PushHouseSynchronizer $synchronizer;

    /**
     * Результат последнего парсинга
     */
    private array $lastParsingResult = [];

    /**
     * Инициализация сервиса парсинга
     *
     * @param PushHouseApiClient|null $apiClient
     * @param PushHouseSynchronizer|null $synchronizer
     */
    public function __construct(
        ?PushHouseApiClient $apiClient = null,
        ?PushHouseSynchronizer $synchronizer = null
    ) {
        $this->apiClient = $apiClient ?? new PushHouseApiClient();
        $this->synchronizer = $synchronizer ?? new PushHouseSynchronizer();
    }

    /**
     * Выполнить полный цикл парсинга Push.House
     *
     * @param array $options Опции парсинга
     * @return array Результат парсинга с статистикой
     * @throws ParserException
     */
    public function parseAndSync(array $options = []): array
    {
        $startTime = microtime(true);

        Log::info("PushHouse Parsing: Starting full parsing cycle", $options);

        try {
            // Шаг 1: Получение данных от API
            $apiCreatives = $this->fetchFromApi($options);

            // Шаг 2: Синхронизация с БД
            $syncResult = $this->syncWithDatabase($apiCreatives);

            // Шаг 3: Формирование итогового результата
            $result = $this->buildParsingResult($apiCreatives, $syncResult, $startTime);

            Log::info("PushHouse Parsing: Full cycle completed successfully", [
                'duration_seconds' => $result['duration_seconds'],
                'total_processed' => $result['api_creatives_count'],
                'new_creatives' => $result['sync_result']['new_creatives'],
                'deactivated_creatives' => $result['sync_result']['deactivated_creatives']
            ]);

            return $this->lastParsingResult = $result;
        } catch (\Exception $e) {
            Log::error("PushHouse Parsing: Full cycle failed", [
                'error' => $e->getMessage(),
                'duration_seconds' => round(microtime(true) - $startTime, 2),
                'options' => $options
            ]);

            throw new ParserException("Parsing cycle failed: " . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Получить данные от Push.House API
     *
     * @param array $options Опции запроса
     * @return Collection Коллекция валидных DTO
     * @throws ParserException
     */
    public function fetchFromApi(array $options = []): Collection
    {
        $status = $options['status'] ?? 'active';
        $startPage = $options['start_page'] ?? 1;

        Log::info("PushHouse Parsing: Fetching from API", [
            'status' => $status,
            'start_page' => $startPage
        ]);

        try {
            $apiCreatives = $this->apiClient->fetchAllCreatives($status, $startPage);

            Log::info("PushHouse Parsing: API fetch completed", [
                'creatives_count' => $apiCreatives->count(),
                'status' => $status
            ]);

            return $apiCreatives;
        } catch (\Exception $e) {
            Log::error("PushHouse Parsing: API fetch failed", [
                'error' => $e->getMessage(),
                'status' => $status,
                'start_page' => $startPage
            ]);

            throw new ParserException("Failed to fetch from API: " . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Синхронизировать данные с БД
     *
     * @param Collection $apiCreatives Креативы от API
     * @return array Результат синхронизации
     * @throws ParserException
     */
    public function syncWithDatabase(Collection $apiCreatives): array
    {
        Log::info("PushHouse Parsing: Starting database sync", [
            'creatives_count' => $apiCreatives->count()
        ]);

        try {
            $syncResult = $this->synchronizer->synchronize($apiCreatives);

            Log::info("PushHouse Parsing: Database sync completed", $syncResult);

            return $syncResult;
        } catch (\Exception $e) {
            Log::error("PushHouse Parsing: Database sync failed", [
                'error' => $e->getMessage(),
                'creatives_count' => $apiCreatives->count()
            ]);

            throw new ParserException("Database synchronization failed: " . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Построить итоговый результат парсинга
     *
     * @param Collection $apiCreatives
     * @param array $syncResult
     * @param float $startTime
     * @return array
     */
    private function buildParsingResult(Collection $apiCreatives, array $syncResult, float $startTime): array
    {
        return [
            'timestamp' => now()->toISOString(),
            'duration_seconds' => round(microtime(true) - $startTime, 2),
            'api_creatives_count' => $apiCreatives->count(),
            'sync_result' => $syncResult,
            'job_data' => $this->prepareJobData($syncResult),
            'integrity_check' => $this->synchronizer->validateSyncIntegrity(),
            'api_client_stats' => $this->apiClient->getStats()
        ];
    }

    /**
     * Подготовить данные для Jobs
     *
     * @param array $syncResult
     * @return array
     */
    private function prepareJobData(array $syncResult): array
    {
        return [
            'new_creative_ids' => $syncResult['new_creative_ids'] ?? [],
            'deactivated_creative_ids' => $syncResult['deactivated_creative_ids'] ?? [],
            'should_dispatch_jobs' => !empty($syncResult['new_creative_ids']) || !empty($syncResult['deactivated_creative_ids'])
        ];
    }

    /**
     * Тестирование соединения с API
     *
     * @return array Результат тестирования
     */
    public function testApiConnection(): array
    {
        Log::info("PushHouse Parsing: Testing API connection");

        try {
            $connectionOk = $this->apiClient->testConnection();

            $result = [
                'connection_status' => $connectionOk ? 'success' : 'failed',
                'timestamp' => now()->toISOString(),
                'api_stats' => $this->apiClient->getStats()
            ];

            if ($connectionOk) {
                // Дополнительно попробуем получить одну страницу данных
                try {
                    $testData = $this->apiClient->fetchPage(1, 'active');
                    $result['test_data_count'] = $testData->count();
                    $result['test_status'] = 'success';
                } catch (\Exception $e) {
                    $result['test_status'] = 'connection_ok_but_data_fetch_failed';
                    $result['test_error'] = $e->getMessage();
                }
            }

            Log::info("PushHouse Parsing: API connection test completed", $result);

            return $result;
        } catch (\Exception $e) {
            $result = [
                'connection_status' => 'error',
                'error' => $e->getMessage(),
                'timestamp' => now()->toISOString()
            ];

            Log::error("PushHouse Parsing: API connection test failed", $result);

            return $result;
        }
    }

    /**
     * Получить статистику последнего парсинга
     *
     * @return array
     */
    public function getLastParsingStats(): array
    {
        return $this->lastParsingResult ?: [
            'message' => 'No parsing performed yet'
        ];
    }

    /**
     * Выполнить dry-run парсинга (без записи в БД)
     *
     * @param array $options Опции парсинга
     * @return array Результат dry-run
     */
    public function dryRun(array $options = []): array
    {
        $startTime = microtime(true);

        Log::info("PushHouse Parsing: Starting dry-run", $options);

        try {
            // Получаем данные от API
            $apiCreatives = $this->fetchFromApi($options);

            // Эмулируем синхронизацию без записи в БД
            $dryRunResult = $this->simulateSync($apiCreatives);

            $result = [
                'mode' => 'dry_run',
                'timestamp' => now()->toISOString(),
                'duration_seconds' => round(microtime(true) - $startTime, 2),
                'api_creatives_count' => $apiCreatives->count(),
                'simulated_sync' => $dryRunResult,
                'api_client_stats' => $this->apiClient->getStats()
            ];

            Log::info("PushHouse Parsing: Dry-run completed", $result);

            return $result;
        } catch (\Exception $e) {
            Log::error("PushHouse Parsing: Dry-run failed", [
                'error' => $e->getMessage(),
                'duration_seconds' => round(microtime(true) - $startTime, 2)
            ]);

            throw new ParserException("Dry-run failed: " . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Симулировать синхронизацию без записи в БД
     *
     * @param Collection $apiCreatives
     * @return array
     */
    private function simulateSync(Collection $apiCreatives): array
    {
        // Извлекаем ID из API данных
        $apiIds = $apiCreatives->map(fn($dto) => $dto->externalId)->unique()->values()->toArray();

        // Получаем существующие ID из БД (это безопасная операция чтения)
        $dbIds = $this->synchronizer->getExistingIds();

        // Симулируем определение изменений
        $newIds = array_diff($apiIds, $dbIds);
        $deactivatedIds = array_diff($dbIds, $apiIds);

        return [
            'total_api_ids' => count($apiIds),
            'total_db_ids' => count($dbIds),
            'new_ids_count' => count($newIds),
            'deactivated_ids_count' => count($deactivatedIds),
            'unchanged_ids_count' => count($dbIds) - count($deactivatedIds),
            'sample_new_ids' => array_slice($newIds, 0, 5),
            'sample_deactivated_ids' => array_slice($deactivatedIds, 0, 5)
        ];
    }

    /**
     * Получить статистику производительности
     *
     * @return array
     */
    public function getPerformanceStats(): array
    {
        return [
            'api_client_stats' => $this->apiClient->getStats(),
            'last_sync_stats' => $this->synchronizer->getLastSyncStats(),
            'integrity_check' => $this->synchronizer->validateSyncIntegrity(),
            'last_parsing_result' => $this->getLastParsingStats()
        ];
    }

    /**
     * Очистка старых неактивных креативов
     *
     * @param int $daysOld Количество дней
     * @return array Результат очистки
     */
    public function cleanupOldCreatives(int $daysOld = 30): array
    {
        Log::info("PushHouse Parsing: Starting cleanup", ['days_old' => $daysOld]);

        $deletedCount = $this->synchronizer->cleanupOldCreatives($daysOld);

        $result = [
            'deleted_count' => $deletedCount,
            'days_old' => $daysOld,
            'timestamp' => now()->toISOString()
        ];

        Log::info("PushHouse Parsing: Cleanup completed", $result);

        return $result;
    }
}

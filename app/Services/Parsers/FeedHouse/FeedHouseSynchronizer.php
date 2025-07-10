<?php

declare(strict_types=1);

namespace App\Services\Parsers\FeedHouse;

use App\Http\DTOs\Parsers\FeedHouseCreativeDTO;
use App\Models\AdSource;
use App\Models\Creative;
use App\Services\Parsers\FeedHouseParser;
use App\Services\Parsers\ParserManager;
use Illuminate\Support\Facades\Log;

/**
 * FeedHouse One-Shot Synchronizer
 *
 * Упрощенный синхронизатор для one-shot парсинга FeedHouse
 * Обрабатывает заданное количество элементов и сохраняет их в БД
 * Поддерживает курсорную пагинацию и лимиты
 *
 * @package App\Services\Parsers\FeedHouse
 * @author SeniorSoftwareEngineer
 * @version 2.0.0
 */
class FeedHouseSynchronizer
{
    private ParserManager $parserManager;
    private array $config;
    private array $stats = [
        'total_processed' => 0,
        'total_saved' => 0,
        'duplicates_skipped' => 0,
        'errors' => 0,
        'batches_processed' => 0,
    ];

    public function __construct(ParserManager $parserManager)
    {
        $this->parserManager = $parserManager;
        $this->config = config('services.feedhouse', []);
    }

    /**
     * One-shot синхронизация FeedHouse креативов
     *
     * @param AdSource $adSource Модель источника для state management
     * @param array $options Параметры синхронизации
     * @return array Результаты синхронизации
     */
    public function synchronize(AdSource $adSource, array $options = []): array
    {
        $startTime = microtime(true);
        $memoryStart = memory_get_usage(true);

        Log::info("FeedHouse one-shot synchronization started", [
            'adSource_id' => $adSource->id,
            'options' => $options
        ]);

        try {
            // Инициализация
            $this->initializeSync($adSource);

            // Извлекаем параметры
            $maxItems = $options['max_items_per_run'] ?? 1000;
            $batchSize = $options['batch_size'] ?? 200;

            // One-shot обработка
            $results = $this->performOneShotSync($adSource, $maxItems, $batchSize);

            // Финализация
            $this->finalizeSync($adSource);

            $duration = microtime(true) - $startTime;
            $memoryPeak = memory_get_peak_usage(true);
            $memoryUsed = $memoryPeak - $memoryStart;

            $finalResults = array_merge($this->stats, [
                'status' => 'success',
                'duration' => $duration,
                'duration_seconds' => round($duration, 2),
                'memory_used' => $memoryUsed,
                'memory_peak' => $memoryPeak,
                'final_last_id' => $results['final_last_id'] ?? null,
                'reached_limit' => $this->stats['total_processed'] >= $maxItems,
                'reached_end' => false,
                'mode' => 'one_shot'
            ]);

            Log::info("FeedHouse one-shot synchronization completed", $finalResults);
            return $finalResults;
        } catch (\Exception $e) {
            return $this->handleSyncError($adSource, $e, $startTime);
        }
    }

    /**
     * Выполнить one-shot синхронизацию
     */
    private function performOneShotSync(AdSource $adSource, int $maxItems, int $batchSize): array
    {
        $parser = $this->parserManager->feedHouse();
        $currentLastId = $adSource->parser_state['lastId'] ?? null;
        $totalProcessed = 0;

        Log::info("Starting one-shot processing", [
            'max_items' => $maxItems,
            'batch_size' => $batchSize,
            'starting_lastId' => $currentLastId
        ]);

        while ($totalProcessed < $maxItems) {
            // Определяем размер текущего batch (не больше оставшихся элементов)
            $remainingItems = $maxItems - $totalProcessed;
            $currentBatchSize = min($batchSize, $remainingItems);

            $this->stats['batches_processed']++;

            Log::info("Processing one-shot batch {$this->stats['batches_processed']}", [
                'current_batch_size' => $currentBatchSize,
                'processed_so_far' => $totalProcessed,
                'remaining' => $remainingItems,
                'lastId' => $currentLastId
            ]);

            // Получаем данные от API
            $batch = $this->fetchBatch($parser, $currentLastId, $currentBatchSize);

            if (empty($batch)) {
                Log::info("No more data available, ending one-shot processing");
                break;
            }

            // Обрабатываем batch
            $batchResults = $this->processBatch($batch);

            // Обновляем статистику
            $this->updateStats($batchResults);

            // Обновляем счетчик и lastId
            $totalProcessed += count($batch);
            $currentLastId = max(array_column($batch, 'id'));

            // Сохраняем состояние
            $adSource->parser_state = ['lastId' => $currentLastId];
            $adSource->save();

            Log::info("One-shot batch processed", [
                'batch_number' => $this->stats['batches_processed'],
                'batch_size' => count($batch),
                'total_processed' => $totalProcessed,
                'new_lastId' => $currentLastId,
                'max_target' => $maxItems
            ]);

            // Проверяем лимит
            if ($totalProcessed >= $maxItems) {
                Log::info("Reached max items limit", [
                    'processed' => $totalProcessed,
                    'limit' => $maxItems
                ]);
                break;
            }

            // Если получили меньше чем запрашивали - достигли конца данных
            if (count($batch) < $currentBatchSize) {
                Log::info("Reached end of available data", [
                    'requested' => $currentBatchSize,
                    'received' => count($batch)
                ]);
                break;
            }

            // Rate limiting
            usleep(500000); // 0.5 секунды
        }

        return [
            'final_last_id' => $currentLastId,
            'total_processed' => $totalProcessed
        ];
    }

    /**
     * Получение данных от API
     */
    private function fetchBatch(FeedHouseParser $parser, ?int $lastId, int $batchSize): array
    {
        try {
            $queryParams = [
                'limit' => $batchSize,
                'formats' => implode(',', $this->config['default_formats'] ?? ['push', 'inpage']),
                'adNetworks' => implode(',', $this->config['default_networks'] ?? ['rollerads', 'richads'])
            ];

            if ($lastId !== null) {
                $queryParams['lastId'] = $lastId;
            }

            $response = $parser->fetchData($queryParams);

            if (!is_array($response)) {
                Log::warning("FeedHouse API returned non-array response", [
                    'response_type' => gettype($response)
                ]);
                return [];
            }

            return $response;
        } catch (\Exception $e) {
            Log::error("Failed to fetch batch from FeedHouse API", [
                'error' => $e->getMessage(),
                'lastId' => $lastId,
                'batchSize' => $batchSize
            ]);
            throw $e;
        }
    }

    /**
     * Обработка batch данных
     */
    private function processBatch(array $batch): array
    {
        $results = [
            'processed' => 0,
            'saved' => 0,
            'duplicates' => 0,
            'errors' => 0
        ];

        foreach ($batch as $item) {
            try {
                $results['processed']++;

                // Создаем DTO
                $dto = FeedHouseCreativeDTO::fromApiResponse($item);

                if (!$dto->isValid()) {
                    Log::debug("Invalid creative data from FeedHouse", [
                        'external_id' => $item['id'] ?? 'unknown',
                        'title' => $item['title'] ?? 'empty'
                    ]);
                    $results['errors']++;
                    continue;
                }

                // Проверяем на дубликат
                if ($this->isDuplicate($dto)) {
                    $results['duplicates']++;
                    continue;
                }

                // Сохраняем
                if ($this->saveCreative($dto)) {
                    $results['saved']++;
                }
            } catch (\Exception $e) {
                Log::error("Failed to process FeedHouse creative", [
                    'error' => $e->getMessage(),
                    'item_id' => $item['id'] ?? 'unknown'
                ]);
                $results['errors']++;
            }
        }

        return $results;
    }

    /**
     * Проверка на дубликат
     */
    private function isDuplicate(FeedHouseCreativeDTO $dto): bool
    {
        $hash = $this->generateHashForDto($dto);
        return Creative::where('combined_hash', $hash)->exists();
    }

    /**
     * Сохранение креатива
     */
    private function saveCreative(FeedHouseCreativeDTO $dto): bool
    {
        try {
            $data = $dto->toBasicDatabase();

            Creative::updateOrCreate(
                ['combined_hash' => $data['combined_hash']],
                $data
            );

            return true;
        } catch (\Exception $e) {
            Log::error("Failed to save creative", [
                'error' => $e->getMessage(),
                'external_id' => $dto->externalId
            ]);
            return false;
        }
    }

    /**
     * Генерация хеша для DTO
     */
    private function generateHashForDto(FeedHouseCreativeDTO $dto): string
    {
        $data = [
            'external_id' => $dto->externalId,
            'source' => 'feedhouse',
            'title' => $dto->title,
            'text' => $dto->text,
            'country' => $dto->countryCode,
            'adNetwork' => $dto->adNetwork,
        ];

        return hash('sha256', json_encode($data, JSON_UNESCAPED_UNICODE));
    }

    /**
     * Инициализация синхронизации
     */
    private function initializeSync(AdSource $adSource): void
    {
        $adSource->update([
            'parser_status' => 'running',
            'parser_last_error' => null,
            'parser_last_error_at' => null
        ]);

        $this->stats = [
            'total_processed' => 0,
            'total_saved' => 0,
            'duplicates_skipped' => 0,
            'errors' => 0,
            'batches_processed' => 0,
        ];

        Log::info("FeedHouse one-shot synchronizer initialized", [
            'adSource_id' => $adSource->id
        ]);
    }

    /**
     * Финализация синхронизации
     */
    private function finalizeSync(AdSource $adSource): void
    {
        $adSource->update([
            'parser_status' => 'idle',
            'parser_last_run_at' => now(),
            'parser_last_error' => null,
            'parser_last_error_at' => null
        ]);

        Log::info("FeedHouse one-shot synchronization finalized", [
            'adSource_id' => $adSource->id,
            'final_stats' => $this->stats
        ]);
    }

    /**
     * Обработка ошибок синхронизации
     */
    private function handleSyncError(AdSource $adSource, \Exception $e, float $startTime): array
    {
        $duration = microtime(true) - $startTime;

        $adSource->update([
            'parser_status' => 'failed',
            'parser_last_error' => [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'stats' => $this->stats
            ],
            'parser_last_error_at' => now(),
            'parser_last_error_message' => $e->getMessage()
        ]);

        Log::error("FeedHouse one-shot synchronization failed", [
            'adSource_id' => $adSource->id,
            'error' => $e->getMessage(),
            'duration' => round($duration, 2) . 's',
            'stats' => $this->stats
        ]);

        return array_merge($this->stats, [
            'status' => 'failed',
            'error' => $e->getMessage(),
            'duration' => $duration,
            'mode' => 'one_shot'
        ]);
    }

    /**
     * Обновление статистики
     */
    private function updateStats(array $batchResults): void
    {
        $this->stats['total_processed'] += $batchResults['processed'];
        $this->stats['total_saved'] += $batchResults['saved'];
        $this->stats['duplicates_skipped'] += $batchResults['duplicates'];
        $this->stats['errors'] += $batchResults['errors'];
    }

    /**
     * Получение статистики
     */
    public function getStats(): array
    {
        return $this->stats;
    }
}

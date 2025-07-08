<?php

namespace App\Jobs\Parsers\PushHouse;

use App\Services\Parsers\PushHouse\PushHouseParsingService;
use App\Services\Parsers\Exceptions\ParserException;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Job для асинхронной обработки Push.House парсинга
 * 
 * Выполняет полный цикл парсинга в фоновом режиме:
 * - Получение данных от API
 * - Синхронизация с БД
 * - Обработка новых и деактивированных креативов
 * 
 * @package App\Jobs\Parsers\PushHouse
 * @author SeniorSoftwareEngineer
 * @version 1.0.0
 */
class ProcessPushHouseParsingJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of times the job may be attempted.
     */
    public int $tries = 3;

    /**
     * The maximum number of seconds the job can run.
     */
    public int $timeout = 900; // 15 минут

    /**
     * Delete the job if its models no longer exist.
     */
    public bool $deleteWhenMissingModels = true;

    /**
     * Параметры парсинга
     */
    private array $options;

    /**
     * Режим выполнения (full, test, dry-run)
     */
    private string $mode;

    /**
     * Create a new job instance.
     *
     * @param array $options Параметры парсинга
     * @param string $mode Режим выполнения
     */
    public function __construct(array $options = [], string $mode = 'full')
    {
        $this->options = $options;
        $this->mode = $mode;

        // Настройки очереди
        $this->onQueue('parsers');
        $this->delay(now()->addSeconds(5)); // Небольшая задержка для предотвращения перегрузки
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $startTime = microtime(true);
        $jobId = $this->job->getJobId();

        Log::info('Push.House Parsing Job started', [
            'job_id' => $jobId,
            'mode' => $this->mode,
            'options' => $this->options,
            'attempt' => $this->attempts()
        ]);

        try {
            $parsingService = new PushHouseParsingService();

            switch ($this->mode) {
                case 'test':
                    $result = $this->handleTestMode($parsingService);
                    break;

                case 'dry-run':
                    $result = $this->handleDryRunMode($parsingService);
                    break;

                case 'full':
                default:
                    $result = $this->handleFullParsingMode($parsingService);
                    break;
            }

            $duration = round(microtime(true) - $startTime, 2);

            Log::info('Push.House Parsing Job completed successfully', [
                'job_id' => $jobId,
                'mode' => $this->mode,
                'duration_seconds' => $duration,
                'result' => $result
            ]);

            // Отправляем уведомление об успешном завершении
            $this->notifySuccess($result, $duration);
        } catch (ParserException $e) {
            $duration = round(microtime(true) - $startTime, 2);

            Log::error('Push.House Parsing Job failed with parser error', [
                'job_id' => $jobId,
                'mode' => $this->mode,
                'attempt' => $this->attempts(),
                'duration_seconds' => $duration,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            // Определяем, стоит ли повторять попытку
            if ($this->shouldRetry($e)) {
                $this->release(60 * $this->attempts()); // Увеличиваем задержку с каждой попыткой
                return;
            }

            $this->fail($e);
        } catch (\Exception $e) {
            $duration = round(microtime(true) - $startTime, 2);

            Log::error('Push.House Parsing Job failed with unexpected error', [
                'job_id' => $jobId,
                'mode' => $this->mode,
                'attempt' => $this->attempts(),
                'duration_seconds' => $duration,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            $this->fail($e);
        }
    }

    /**
     * Handle test mode execution
     */
    private function handleTestMode(PushHouseParsingService $parsingService): array
    {
        Log::info('Push.House Job: Running test mode');

        $result = $parsingService->testApiConnection();

        if ($result['connection_status'] !== 'success') {
            throw new ParserException('API connection test failed: ' . ($result['error'] ?? 'Unknown error'));
        }

        return [
            'mode' => 'test',
            'connection_status' => $result['connection_status'],
            'test_data_count' => $result['test_data_count'] ?? 0
        ];
    }

    /**
     * Handle dry-run mode execution
     */
    private function handleDryRunMode(PushHouseParsingService $parsingService): array
    {
        Log::info('Push.House Job: Running dry-run mode');

        $result = $parsingService->dryRun($this->options);

        return [
            'mode' => 'dry-run',
            'api_creatives_count' => $result['api_creatives_count'],
            'simulated_sync' => $result['simulated_sync'],
            'duration_seconds' => $result['duration_seconds']
        ];
    }

    /**
     * Handle full parsing mode execution
     */
    private function handleFullParsingMode(PushHouseParsingService $parsingService): array
    {
        Log::info('Push.House Job: Running full parsing mode');

        $result = $parsingService->parseAndSync($this->options);

        // Обработка результатов для диспетчеризации дополнительных Jobs
        $this->processParsingResults($result);

        return [
            'mode' => 'full',
            'api_creatives_count' => $result['api_creatives_count'],
            'sync_result' => $result['sync_result'],
            'job_data' => $result['job_data'],
            'integrity_check' => $result['integrity_check'],
            'duration_seconds' => $result['duration_seconds']
        ];
    }

    /**
     * Process parsing results and dispatch additional jobs if needed
     */
    private function processParsingResults(array $result): void
    {
        $jobData = $result['job_data'];

        if (!$jobData['should_dispatch_jobs']) {
            Log::info('Push.House Job: No additional jobs to dispatch');
            return;
        }

        $newCreativeIds = $jobData['new_creative_ids'];
        $deactivatedCreativeIds = $jobData['deactivated_creative_ids'];

        // Диспетчеризация Jobs для обработки новых креативов
        if (!empty($newCreativeIds)) {
            $this->dispatchNewCreativesProcessing($newCreativeIds);
        }

        // Диспетчеризация Jobs для обработки деактивированных креативов
        if (!empty($deactivatedCreativeIds)) {
            $this->dispatchDeactivatedCreativesProcessing($deactivatedCreativeIds);
        }

        Log::info('Push.House Job: Dispatched additional processing jobs', [
            'new_creatives_count' => count($newCreativeIds),
            'deactivated_creatives_count' => count($deactivatedCreativeIds)
        ]);
    }

    /**
     * Dispatch jobs for processing new creatives
     */
    private function dispatchNewCreativesProcessing(array $creativeIds): void
    {
        // Разбиваем на пакеты для оптимальной обработки
        $batchSize = config('services.push_house.batch_size', 100);
        $batches = array_chunk($creativeIds, $batchSize);

        foreach ($batches as $batch) {
            // TODO: Создать специализированный Job для обработки новых креативов
            // ProcessNewPushHouseCreativesJob::dispatch($batch)->onQueue('creative-processing');

            Log::info('Push.House Job: Would dispatch new creatives processing', [
                'batch_size' => count($batch),
                'creative_ids' => $batch
            ]);
        }
    }

    /**
     * Dispatch jobs for processing deactivated creatives
     */
    private function dispatchDeactivatedCreativesProcessing(array $creativeIds): void
    {
        // Разбиваем на пакеты для оптимальной обработки
        $batchSize = config('services.push_house.batch_size', 100);
        $batches = array_chunk($creativeIds, $batchSize);

        foreach ($batches as $batch) {
            // TODO: Создать специализированный Job для обработки деактивированных креативов
            // ProcessDeactivatedPushHouseCreativesJob::dispatch($batch)->onQueue('creative-processing');

            Log::info('Push.House Job: Would dispatch deactivated creatives processing', [
                'batch_size' => count($batch),
                'creative_ids' => $batch
            ]);
        }
    }

    /**
     * Determine if the job should be retried based on the exception
     */
    private function shouldRetry(\Exception $e): bool
    {
        // Не повторяем попытки для критических ошибок
        if ($e instanceof ParserException) {
            $message = $e->getMessage();

            // Не повторяем при проблемах с API ключом
            if (str_contains($message, 'API key') || str_contains($message, 'Unauthorized')) {
                return false;
            }

            // Не повторяем при проблемах с конфигурацией
            if (str_contains($message, 'configuration') || str_contains($message, 'config')) {
                return false;
            }
        }

        // Повторяем при временных проблемах (сеть, rate limit, и т.д.)
        return $this->attempts() < $this->tries;
    }

    /**
     * Send success notification
     */
    private function notifySuccess(array $result, float $duration): void
    {
        // TODO: Интеграция с системой уведомлений
        // NotificationService::send('push_house_parsing_success', $result);

        Log::info('Push.House Job: Success notification sent', [
            'result_summary' => [
                'mode' => $result['mode'],
                'duration' => $duration
            ]
        ]);
    }

    /**
     * Handle job failure
     */
    public function failed(\Exception $exception): void
    {
        Log::error('Push.House Parsing Job failed permanently', [
            'job_id' => $this->job?->getJobId(),
            'mode' => $this->mode,
            'options' => $this->options,
            'attempts' => $this->attempts(),
            'error' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString()
        ]);

        // TODO: Отправка уведомления об ошибке
        // NotificationService::send('push_house_parsing_failed', [
        //     'error' => $exception->getMessage(),
        //     'mode' => $this->mode
        // ]);
    }

    /**
     * Get the tags that should be assigned to the job.
     */
    public function tags(): array
    {
        return [
            'parser:push_house',
            'mode:' . $this->mode,
            'source:push_house'
        ];
    }

    /**
     * Calculate the number of seconds to wait before retrying the job.
     */
    public function backoff(): array
    {
        return [60, 120, 300]; // 1 мин, 2 мин, 5 мин
    }
}

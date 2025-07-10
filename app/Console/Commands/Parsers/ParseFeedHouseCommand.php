<?php

namespace App\Console\Commands\Parsers;

use App\Models\AdSource;
use App\Services\Parsers\ParserManager;
use App\Services\Parsers\Exceptions\ParserException;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/**
 * Artisan команда для запуска FeedHouse парсера
 * 
 * Единая точка входа для периодического парсинга FeedHouse API
 * Поддерживает различные режимы работы через флаги и порционную обработку
 * 
 * @package App\Console\Commands\Parsers
 * @author SeniorSoftwareEngineer
 * @version 1.0.0
 */
class ParseFeedHouseCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'parsers:run-feedhouse
                           {--mode=regular : Режим парсинга (regular|initial_scan)}
                           {--source=feed_house : Название источника в базе данных}
                           {--batch-size=200 : Размер порции для API запросов (limit)}
                           {--max-items-per-run=1000 : Максимальное количество элементов за один запуск (для Scheduler)}
                           {--one-shot=true : One-shot режим: обработать N элементов и остановиться}
                           {--continuous : Continuous режим: обработать все доступные данные}
                           {--queue-chunk-size=50 : Размер порции для очередей}
                           {--enhancement-level=full : Уровень обогащения (basic|full|premium)}
                           {--skip-enhancement : Пропустить постобработку}
                           {--dry-run : Запуск без сохранения}
                           {--test : Test API connection without parsing}
                           {--force : Force parsing even if already running}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Run FeedHouse parser with Scheduler-friendly one-shot processing (default: 1000 items per run)';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle(): int
    {
        $startTime = microtime(true);

        $this->info('🚀 FeedHouse Parser Started');
        $this->newLine();

        try {
            // Проверяем режим работы
            if ($this->option('test')) {
                return $this->handleTestMode();
            }

            if ($this->option('dry-run')) {
                return $this->handleDryRunMode();
            }

            // Основной режим парсинга
            return $this->handleParsingMode();
        } catch (ParserException $e) {
            $this->error("❌ Parser Error: {$e->getMessage()}");
            Log::error('FeedHouse Parser Command failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return Command::FAILURE;
        } catch (\Exception $e) {
            $this->error("❌ Unexpected Error: {$e->getMessage()}");
            Log::error('FeedHouse Parser Command unexpected error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return Command::FAILURE;
        } finally {
            $duration = round(microtime(true) - $startTime, 2);
            $this->info("⏱️ Total execution time: {$duration} seconds");
        }
    }

    /**
     * Handle test mode - check API connection
     */
    private function handleTestMode(): int
    {
        $this->info('🔍 Testing FeedHouse API connection...');

        try {
            $service = new \App\Services\Parsers\FeedHouse\FeedHouseParsingService();

            // Простой тест соединения с API
            $testResult = $service->testConnection();

            if ($testResult['status'] === 'success') {
                $this->info('✅ API connection successful');
                $this->table(['Metric', 'Value'], [
                    ['Connection Status', $testResult['status']],
                    ['Test Data Count', $testResult['test_data_count']],
                    ['API Response', $testResult['api_response']]
                ]);
            } else {
                $this->error('❌ API connection failed');
                $this->error("Error: " . $testResult['error']);
                return Command::FAILURE;
            }
            return Command::SUCCESS;
        } catch (\Exception $e) {
            $this->error('❌ API connection failed');
            $this->error("Error: " . $e->getMessage());
            return Command::FAILURE;
        }
    }

    /**
     * Handle dry-run mode - simulate without DB changes
     */
    private function handleDryRunMode(): int
    {
        $sourceName = $this->option('source');
        $mode = $this->option('mode');
        $batchSize = (int) $this->option('batch-size');
        $maxItemsPerRun = (int) $this->option('max-items-per-run');
        $isOneShot = !$this->option('continuous'); // По умолчанию one-shot, кроме случая когда указан --continuous

        $this->info('🧪 Running in dry-run mode (no database changes)...');

        // Находим модель AdSource
        $adSource = AdSource::where('source_name', $sourceName)->first();

        if (!$adSource) {
            $this->error("AdSource with name '{$sourceName}' not found");
            return Command::FAILURE;
        }

        $params = [
            'mode' => $mode,
            'batch_size' => $batchSize,
            'max_items_per_run' => $maxItemsPerRun,
            'one_shot' => $isOneShot,
            'dry_run' => true
        ];

        // Для dry-run используем старый ParserManager
        $parserManager = app(\App\Services\Parsers\ParserManager::class);
        $result = $parserManager->feedHouseWithState($adSource, $params);

        $this->info('✅ Dry-run completed successfully');
        $this->displayResults($result);

        return Command::SUCCESS;
    }

    /**
     * Handle main parsing mode
     */
    private function handleParsingMode(): int
    {
        $sourceName = $this->option('source');
        $mode = $this->option('mode');
        $batchSize = (int) $this->option('batch-size');
        $maxItemsPerRun = (int) $this->option('max-items-per-run');
        $isOneShot = !$this->option('continuous'); // По умолчанию one-shot
        $queueChunkSize = (int) $this->option('queue-chunk-size');
        $enhancementLevel = $this->option('enhancement-level');
        $skipEnhancement = $this->option('skip-enhancement');

        if (!$this->option('force') && $this->isParsingRunning()) {
            $this->warn('⚠️ Parser is already running. Use --force to override.');
            return Command::SUCCESS;
        }

        $this->info('⚙️ Starting FeedHouse parsing cycle...');

        // Находим модель AdSource
        $adSource = AdSource::where('source_name', $sourceName)->first();

        if (!$adSource) {
            $this->error("AdSource with name '{$sourceName}' not found");
            return Command::FAILURE;
        }

        $this->info("Source: {$adSource->source_display_name}");
        $this->info("Mode: " . ($isOneShot ? "One-shot ({$maxItemsPerRun} items max)" : "Continuous (all data)"));
        $this->info("Batch size: {$batchSize}");
        $this->info("Queue chunk size: {$queueChunkSize}");
        $this->info("Enhancement level: {$enhancementLevel}");
        if ($skipEnhancement) {
            $this->warn("Enhancement disabled");
        }

        // Мониторинг памяти
        $memoryStart = memory_get_usage(true);

        $params = [
            'mode' => $mode,
            'batch_size' => $batchSize,
            'max_items_per_run' => $maxItemsPerRun,
            'one_shot' => $isOneShot,
            'queue_chunk_size' => $queueChunkSize,
            'enhancement_level' => $enhancementLevel,
            'skip_enhancement' => $skipEnhancement
        ];

        // ВАЖНО: Используем новый FeedHouseParsingService для реального сохранения в БД
        $service = new \App\Services\Parsers\FeedHouse\FeedHouseParsingService();
        $result = $service->parseAndSync($adSource, $params);

        $memoryPeak = memory_get_peak_usage(true);
        $memoryUsed = $memoryPeak - $memoryStart;

        $this->info('✅ Parsing completed successfully');
        $this->displayResults($result, $params);
        $this->info("Memory used: " . $this->formatBytes($memoryUsed));
        $this->info("Peak memory: " . $this->formatBytes($memoryPeak));

        return Command::SUCCESS;
    }

    /**
     * Display parsing results
     */
    private function displayResults(array $result, array $options = []): void
    {
        $this->table(['Metric', 'Value'], [
            ['Total Processed', $result['total_processed'] ?? 0],
            ['Final Last ID', $result['final_last_id'] ?? 'none'],
            ['Batches Processed', $result['batches_processed'] ?? 0],
            ['Mode', $result['mode'] ?? 'unknown'],
            ['Max Items Per Run', $options['max_items_per_run'] ?? 'not set'],
            ['Duration (seconds)', $result['duration_seconds'] ?? 'unknown'],
            ['Reached Limit', ($result['reached_limit'] ?? false) ? 'Yes' : 'No'],
            ['Reached End', ($result['reached_end'] ?? false) ? 'Yes' : 'No'],
            ['Status', $result['status'] ?? 'unknown']
        ]);

        // Показываем информацию о следующем запуске для Scheduler
        if ($result['mode'] === 'one_shot' && ($result['reached_limit'] ?? false)) {
            $this->info('🔄 Scheduler Information:');
            $this->line("   • Parser stopped after reaching limit ({$options['max_items_per_run']} items)");
            $this->line("   • Next run will continue from Last ID: {$result['final_last_id']}");
            $this->line("   • Ready for next scheduled execution");
        } elseif ($result['mode'] === 'one_shot' && ($result['reached_end'] ?? false)) {
            $this->info('✅ Data Source Information:');
            $this->line("   • Reached end of available data");
            $this->line("   • Next run will check for new data from: {$result['final_last_id']}");
        }

        if (isset($result['memory_stats'])) {
            $this->info('📊 Memory Statistics:');
            $this->line("   Peak Usage: " . $this->formatBytes($result['memory_stats']['peak_usage'] ?? 0));
            $this->line("   Final Usage: " . $this->formatBytes($result['memory_stats']['final_usage'] ?? 0));
        }
    }

    /**
     * Check if parsing is already running
     */
    private function isParsingRunning(): bool
    {
        $sourceName = $this->option('source');
        $adSource = AdSource::where('source_name', $sourceName)->first();

        if (!$adSource) {
            return false;
        }

        return $adSource->parser_status === 'running';
    }

    /**
     * Format bytes to human readable format
     */
    private function formatBytes(int $bytes): string
    {
        if ($bytes >= 1024 * 1024) {
            return round($bytes / (1024 * 1024), 2) . ' MB';
        } elseif ($bytes >= 1024) {
            return round($bytes / 1024, 2) . ' KB';
        }
        return $bytes . ' B';
    }

    /**
     * Get command usage examples
     */
    public static function getUsageExamples(): array
    {
        return [
            'Basic one-shot parsing (Scheduler)' => 'php artisan parsers:feedhouse',
            'Custom one-shot with 500 items' => 'php artisan parsers:feedhouse --max-items-per-run=500',
            'Test connection' => 'php artisan parsers:feedhouse --test',
            'Dry run (one-shot)' => 'php artisan parsers:feedhouse --dry-run',
            'Force parsing' => 'php artisan parsers:feedhouse --force',
            'Initial scan (one-shot)' => 'php artisan parsers:feedhouse --mode=initial_scan',
            'Continuous mode (all data)' => 'php artisan parsers:feedhouse --continuous',
            'Small batch for testing' => 'php artisan parsers:feedhouse --batch-size=50 --max-items-per-run=100',
            'Skip enhancement' => 'php artisan parsers:feedhouse --skip-enhancement',
            'Premium enhancement' => 'php artisan parsers:feedhouse --enhancement-level=premium'
        ];
    }
}

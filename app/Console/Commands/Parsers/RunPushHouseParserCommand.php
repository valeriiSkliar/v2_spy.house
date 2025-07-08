<?php

namespace App\Console\Commands\Parsers;

use App\Services\Parsers\PushHouse\PushHouseParsingService;
use App\Services\Parsers\Exceptions\ParserException;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/**
 * Artisan команда для запуска Push.House парсера
 * 
 * Единая точка входа для периодического парсинга Push.House API
 * Поддерживает различные режимы работы через флаги
 * 
 * @package App\Console\Commands\Parsers
 * @author SeniorSoftwareEngineer
 * @version 1.0.0
 */
class RunPushHouseParserCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'parsers:run-push-house
                            {--test : Test API connection without parsing}
                            {--dry-run : Simulate parsing without database changes}
                            {--force : Force parsing even if already running}
                            {--queue : Run parsing in background queue}
                            {--status=active : Status of creatives to fetch (active, inactive, all)}
                            {--start-page=1 : Starting page for pagination}
                            {--cleanup : Cleanup old inactive creatives after parsing}
                            {--cleanup-days=30 : Days to consider creatives as old for cleanup}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Run Push.House parser to fetch and synchronize creatives';

    /**
     * Push.House parsing service
     */
    private PushHouseParsingService $parsingService;

    /**
     * Create a new command instance.
     */
    public function __construct(?PushHouseParsingService $parsingService = null)
    {
        parent::__construct();
        $this->parsingService = $parsingService ?? new PushHouseParsingService();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle(): int
    {
        $startTime = microtime(true);

        $this->info('🚀 Push.House Parser Started');
        $this->newLine();

        try {
            // Проверяем режим работы
            if ($this->option('test')) {
                return $this->handleTestMode();
            }

            if ($this->option('dry-run')) {
                return $this->handleDryRunMode();
            }

            if ($this->option('queue')) {
                return $this->handleQueueMode();
            }

            // Основной режим парсинга
            return $this->handleParsingMode();
        } catch (ParserException $e) {
            $this->error("❌ Parser Error: {$e->getMessage()}");
            Log::error('Push.House Parser Command failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return Command::FAILURE;
        } catch (\Exception $e) {
            $this->error("❌ Unexpected Error: {$e->getMessage()}");
            Log::error('Push.House Parser Command unexpected error', [
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
        $this->info('🔍 Testing API connection...');

        $result = $this->parsingService->testApiConnection();

        if ($result['connection_status'] === 'success') {
            $this->info('✅ API connection successful');
            $this->table(['Metric', 'Value'], [
                ['Connection Status', $result['connection_status']],
                ['Test Status', $result['test_status']],
                ['Test Data Count', $result['test_data_count'] ?? 'N/A'],
                ['Base URL', $result['api_stats']['base_url'] ?? 'N/A']
            ]);
            return Command::SUCCESS;
        } else {
            $this->error('❌ API connection failed');
            $this->error("Error: " . ($result['error'] ?? 'Unknown error'));
            return Command::FAILURE;
        }
    }

    /**
     * Handle dry-run mode - simulate without DB changes
     */
    private function handleDryRunMode(): int
    {
        $this->info('🧪 Running in dry-run mode (no database changes)...');

        $options = [
            'status' => $this->option('status'),
            'start_page' => (int) $this->option('start-page')
        ];

        $result = $this->parsingService->dryRun($options);

        $this->info('✅ Dry-run completed successfully');
        $this->displayDryRunResults($result);

        return Command::SUCCESS;
    }

    /**
     * Handle queue mode - dispatch to background
     */
    private function handleQueueMode(): int
    {
        $this->info('📤 Dispatching parsing to background queue...');

        $options = [
            'status' => $this->option('status'),
            'start_page' => (int) $this->option('start-page')
        ];

        // Импортируем Job класс
        $jobClass = \App\Jobs\Parsers\PushHouse\ProcessPushHouseParsingJob::class;

        // Диспетчеризуем Job
        $job = $jobClass::dispatch($options, 'full');

        $this->info('✅ Job dispatched successfully');
        $this->table(['Property', 'Value'], [
            ['Job Class', $jobClass],
            ['Queue', 'parsers'],
            ['Mode', 'full'],
            ['Options', json_encode($options)],
            ['Status', 'Dispatched to background queue']
        ]);

        $this->info('🔍 Monitor job status with: php artisan queue:work');

        return Command::SUCCESS;
    }

    /**
     * Handle main parsing mode
     */
    private function handleParsingMode(): int
    {
        if (!$this->option('force') && $this->isParsingRunning()) {
            $this->warn('⚠️ Parser is already running. Use --force to override.');
            return Command::SUCCESS;
        }

        $this->info('⚙️ Starting full parsing cycle...');

        $options = [
            'status' => $this->option('status'),
            'start_page' => (int) $this->option('start-page')
        ];

        $result = $this->parsingService->parseAndSync($options);

        $this->info('✅ Parsing completed successfully');
        $this->displayParsingResults($result);

        // Cleanup если запрошен
        if ($this->option('cleanup')) {
            $this->handleCleanup();
        }

        return Command::SUCCESS;
    }

    /**
     * Handle cleanup of old creatives
     */
    private function handleCleanup(): void
    {
        $days = (int) $this->option('cleanup-days');
        $this->info("🧹 Cleaning up creatives older than {$days} days...");

        $result = $this->parsingService->cleanupOldCreatives($days);

        if ($result['deleted_count'] > 0) {
            $this->info("✅ Cleaned up {$result['deleted_count']} old creatives");
        } else {
            $this->info('ℹ️ No old creatives found for cleanup');
        }
    }

    /**
     * Display dry-run results
     */
    private function displayDryRunResults(array $result): void
    {
        $this->table(['Metric', 'Value'], [
            ['Mode', $result['mode']],
            ['Duration', $result['duration_seconds'] . ' seconds'],
            ['API Creatives Count', $result['api_creatives_count']],
            ['Total API IDs', $result['simulated_sync']['total_api_ids']],
            ['Total DB IDs', $result['simulated_sync']['total_db_ids']],
            ['New IDs Count', $result['simulated_sync']['new_ids_count']],
            ['Deactivated IDs Count', $result['simulated_sync']['deactivated_ids_count']],
            ['Unchanged IDs Count', $result['simulated_sync']['unchanged_ids_count']]
        ]);

        if (!empty($result['simulated_sync']['sample_new_ids'])) {
            $this->info('📋 Sample New IDs: ' . implode(', ', $result['simulated_sync']['sample_new_ids']));
        }

        if (!empty($result['simulated_sync']['sample_deactivated_ids'])) {
            $this->info('📋 Sample Deactivated IDs: ' . implode(', ', $result['simulated_sync']['sample_deactivated_ids']));
        }
    }

    /**
     * Display parsing results
     */
    private function displayParsingResults(array $result): void
    {
        $syncResult = $result['sync_result'];
        $jobData = $result['job_data'];

        $this->table(['Metric', 'Value'], [
            ['Duration', $result['duration_seconds'] . ' seconds'],
            ['API Creatives Count', $result['api_creatives_count']],
            ['New Creatives', $syncResult['new_creatives']],
            ['Deactivated Creatives', $syncResult['deactivated_creatives']],
            ['Unchanged Creatives', $syncResult['unchanged_creatives']],
            ['Should Dispatch Jobs', $jobData['should_dispatch_jobs'] ? 'Yes' : 'No'],
            ['Integrity Check', $result['integrity_check']['integrity_check'] ? 'Passed' : 'Failed']
        ]);

        // Детальная информация о целостности
        $this->info('📊 Database Integrity:');
        $integrity = $result['integrity_check'];
        $this->line("   Total: {$integrity['total_creatives']}");
        $this->line("   Active: {$integrity['active_creatives']}");
        $this->line("   Inactive: {$integrity['inactive_creatives']}");

        // Информация о Jobs
        if ($jobData['should_dispatch_jobs']) {
            $newCount = count($jobData['new_creative_ids']);
            $deactivatedCount = count($jobData['deactivated_creative_ids']);
            $this->info("📤 Jobs to dispatch: {$newCount} new + {$deactivatedCount} deactivated");
        }
    }

    /**
     * Check if parsing is already running
     */
    private function isParsingRunning(): bool
    {
        // TODO: Implement lock mechanism (file lock, cache, etc.)
        // Пока возвращаем false для простоты
        return false;
    }

    /**
     * Display performance statistics
     */
    private function displayPerformanceStats(): void
    {
        $this->info('📈 Performance Statistics:');

        $stats = $this->parsingService->getPerformanceStats();

        $this->table(['Component', 'Metric', 'Value'], [
            ['API Client', 'Base URL', $stats['api_client_stats']['base_url']],
            ['API Client', 'Timeout', $stats['api_client_stats']['timeout'] . 's'],
            ['API Client', 'Max Retries', $stats['api_client_stats']['max_retries']],
            ['Database', 'Total Creatives', $stats['integrity_check']['total_creatives']],
            ['Database', 'Active Creatives', $stats['integrity_check']['active_creatives']],
            ['Database', 'Inactive Creatives', $stats['integrity_check']['inactive_creatives']]
        ]);
    }

    /**
     * Get command usage examples
     */
    public static function getUsageExamples(): array
    {
        return [
            'Basic parsing' => 'php artisan parsers:run-push-house',
            'Test connection' => 'php artisan parsers:run-push-house --test',
            'Dry run' => 'php artisan parsers:run-push-house --dry-run',
            'Force parsing' => 'php artisan parsers:run-push-house --force',
            'Parse inactive' => 'php artisan parsers:run-push-house --status=inactive',
            'Start from page 5' => 'php artisan parsers:run-push-house --start-page=5',
            'With cleanup' => 'php artisan parsers:run-push-house --cleanup --cleanup-days=15',
            'Background queue' => 'php artisan parsers:run-push-house --queue'
        ];
    }
}

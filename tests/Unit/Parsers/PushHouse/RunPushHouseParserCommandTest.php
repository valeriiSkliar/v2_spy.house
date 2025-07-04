<?php

namespace Tests\Unit\Parsers\PushHouse;

use App\Console\Commands\Parsers\RunPushHouseParserCommand;
use App\Services\Parsers\PushHouse\PushHouseParsingService;
use App\Services\Parsers\Exceptions\ParserException;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;
use Mockery;

/**
 * Unit тесты для команды Push.House парсера
 * 
 * @package Tests\Unit\Parsers\PushHouse
 * @author SeniorSoftwareEngineer
 * @version 1.0.0
 */
class RunPushHouseParserCommandTest extends TestCase
{
    private $mockParsingService;

    protected function setUp(): void
    {
        parent::setUp();

        // Создаем мок сервиса
        $this->mockParsingService = Mockery::mock(PushHouseParsingService::class);

        // Биндим мок в Service Container
        $this->app->instance(PushHouseParsingService::class, $this->mockParsingService);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    /**
     * Test successful test mode execution
     */
    public function test_handles_test_mode_successfully(): void
    {
        // Arrange
        $this->mockParsingService
            ->shouldReceive('testApiConnection')
            ->once()
            ->andReturn([
                'connection_status' => 'success',
                'test_status' => 'passed',
                'test_data_count' => 5,
                'api_stats' => [
                    'base_url' => 'https://api.push.house/v1'
                ]
            ]);

        // Act & Assert
        $this->artisan('parsers:run-push-house', ['--test' => true])
            ->expectsOutput('🚀 Push.House Parser Started')
            ->expectsOutput('🔍 Testing API connection...')
            ->expectsOutput('✅ API connection successful')
            ->assertExitCode(Command::SUCCESS);
    }

    /**
     * Test failed test mode execution
     */
    public function test_handles_test_mode_failure(): void
    {
        // Arrange
        $this->mockParsingService
            ->shouldReceive('testApiConnection')
            ->once()
            ->andReturn([
                'connection_status' => 'failed',
                'error' => 'Connection timeout'
            ]);

        // Act & Assert
        $this->artisan('parsers:run-push-house', ['--test' => true])
            ->expectsOutput('❌ API connection failed')
            ->expectsOutput('Error: Connection timeout')
            ->assertExitCode(Command::FAILURE);
    }

    /**
     * Test successful dry-run mode execution
     */
    public function test_handles_dry_run_mode_successfully(): void
    {
        // Arrange
        $dryRunResult = [
            'mode' => 'dry-run',
            'duration_seconds' => 2.5,
            'api_creatives_count' => 150,
            'simulated_sync' => [
                'total_api_ids' => 150,
                'total_db_ids' => 120,
                'new_ids_count' => 30,
                'deactivated_ids_count' => 0,
                'unchanged_ids_count' => 120,
                'sample_new_ids' => [1001, 1002, 1003],
                'sample_deactivated_ids' => []
            ]
        ];

        $this->mockParsingService
            ->shouldReceive('dryRun')
            ->once()
            ->with([
                'status' => 'active',
                'start_page' => 1
            ])
            ->andReturn($dryRunResult);

        // Act & Assert
        $this->artisan('parsers:run-push-house', ['--dry-run' => true])
            ->expectsOutput('🧪 Running in dry-run mode (no database changes)...')
            ->expectsOutput('✅ Dry-run completed successfully')
            ->assertExitCode(Command::SUCCESS);
    }

    /**
     * Test successful full parsing mode execution
     */
    public function test_handles_full_parsing_mode_successfully(): void
    {
        // Arrange
        $parsingResult = [
            'duration_seconds' => 15.3,
            'api_creatives_count' => 200,
            'sync_result' => [
                'new_creatives' => 25,
                'deactivated_creatives' => 5,
                'unchanged_creatives' => 170
            ],
            'job_data' => [
                'should_dispatch_jobs' => true,
                'new_creative_ids' => [101, 102, 103],
                'deactivated_creative_ids' => [201, 202]
            ],
            'integrity_check' => [
                'integrity_check' => true,
                'total_creatives' => 195,
                'active_creatives' => 190,
                'inactive_creatives' => 5
            ]
        ];

        $this->mockParsingService
            ->shouldReceive('parseAndSync')
            ->once()
            ->with([
                'status' => 'active',
                'start_page' => 1
            ])
            ->andReturn($parsingResult);

        // Act & Assert
        $this->artisan('parsers:run-push-house')
            ->expectsOutput('⚙️ Starting full parsing cycle...')
            ->expectsOutput('✅ Parsing completed successfully')
            ->assertExitCode(Command::SUCCESS);
    }

    /**
     * Test parsing with cleanup option
     */
    public function test_handles_cleanup_option(): void
    {
        // Arrange
        $parsingResult = [
            'duration_seconds' => 10.0,
            'api_creatives_count' => 100,
            'sync_result' => [
                'new_creatives' => 10,
                'deactivated_creatives' => 0,
                'unchanged_creatives' => 90
            ],
            'job_data' => [
                'should_dispatch_jobs' => false,
                'new_creative_ids' => [],
                'deactivated_creative_ids' => []
            ],
            'integrity_check' => [
                'integrity_check' => true,
                'total_creatives' => 100,
                'active_creatives' => 100,
                'inactive_creatives' => 0
            ]
        ];

        $cleanupResult = [
            'deleted_count' => 15
        ];

        $this->mockParsingService
            ->shouldReceive('parseAndSync')
            ->once()
            ->andReturn($parsingResult);

        $this->mockParsingService
            ->shouldReceive('cleanupOldCreatives')
            ->once()
            ->with(30)
            ->andReturn($cleanupResult);

        // Act & Assert
        $this->artisan('parsers:run-push-house', [
            '--cleanup' => true,
            '--cleanup-days' => 30
        ])
            ->expectsOutput('🧹 Cleaning up creatives older than 30 days...')
            ->expectsOutput('✅ Cleaned up 15 old creatives')
            ->assertExitCode(Command::SUCCESS);
    }

    /**
     * Test parsing with custom options
     */
    public function test_handles_custom_options(): void
    {
        // Arrange
        $this->mockParsingService
            ->shouldReceive('parseAndSync')
            ->once()
            ->with([
                'status' => 'inactive',
                'start_page' => 5
            ])
            ->andReturn([
                'duration_seconds' => 8.0,
                'api_creatives_count' => 50,
                'sync_result' => [
                    'new_creatives' => 0,
                    'deactivated_creatives' => 50,
                    'unchanged_creatives' => 0
                ],
                'job_data' => [
                    'should_dispatch_jobs' => false,
                    'new_creative_ids' => [],
                    'deactivated_creative_ids' => []
                ],
                'integrity_check' => [
                    'integrity_check' => true,
                    'total_creatives' => 50,
                    'active_creatives' => 0,
                    'inactive_creatives' => 50
                ]
            ]);

        // Act & Assert
        $this->artisan('parsers:run-push-house', [
            '--status' => 'inactive',
            '--start-page' => 5
        ])
            ->assertExitCode(Command::SUCCESS);
    }

    /**
     * Test parser exception handling
     */
    public function test_handles_parser_exception(): void
    {
        // Arrange
        Log::shouldReceive('error')
            ->once()
            ->with('Push.House Parser Command failed', Mockery::type('array'));

        $this->mockParsingService
            ->shouldReceive('parseAndSync')
            ->once()
            ->andThrow(new ParserException('API connection failed'));

        // Act & Assert
        $this->artisan('parsers:run-push-house')
            ->expectsOutput('❌ Parser Error: API connection failed')
            ->assertExitCode(Command::FAILURE);
    }

    /**
     * Test unexpected exception handling
     */
    public function test_handles_unexpected_exception(): void
    {
        // Arrange
        Log::shouldReceive('error')
            ->once()
            ->with('Push.House Parser Command unexpected error', Mockery::type('array'));

        $this->mockParsingService
            ->shouldReceive('parseAndSync')
            ->once()
            ->andThrow(new \RuntimeException('Unexpected error'));

        // Act & Assert
        $this->artisan('parsers:run-push-house')
            ->expectsOutput('❌ Unexpected Error: Unexpected error')
            ->assertExitCode(Command::FAILURE);
    }

    /**
     * Test usage examples static method
     */
    public function test_provides_usage_examples(): void
    {
        // Act
        $examples = RunPushHouseParserCommand::getUsageExamples();

        // Assert
        $this->assertIsArray($examples);
        $this->assertArrayHasKey('Basic parsing', $examples);
        $this->assertArrayHasKey('Test connection', $examples);
        $this->assertArrayHasKey('Dry run', $examples);
        $this->assertArrayHasKey('Background queue', $examples);

        $this->assertEquals('php artisan parsers:run-push-house', $examples['Basic parsing']);
        $this->assertEquals('php artisan parsers:run-push-house --test', $examples['Test connection']);
        $this->assertEquals('php artisan parsers:run-push-house --dry-run', $examples['Dry run']);
    }

    /**
     * Test command signature and description
     */
    public function test_command_has_correct_signature_and_description(): void
    {
        // Act
        $command = new RunPushHouseParserCommand();

        // Assert
        $this->assertStringContainsString('parsers:run-push-house', $command->getName());
        $this->assertStringContainsString('Run Push.House parser', $command->getDescription());
    }

    /**
     * Test queue mode dispatching (without actual queue execution)
     */
    public function test_handles_queue_mode_dispatching(): void
    {
        // Note: Этот тест проверяет только что команда не падает при --queue
        // Полное тестирование queue dispatching требует дополнительной настройки

        // Act & Assert
        $this->artisan('parsers:run-push-house', ['--queue' => true])
            ->expectsOutput('📤 Dispatching parsing to background queue...')
            ->expectsOutput('✅ Job dispatched successfully')
            ->assertExitCode(Command::SUCCESS);
    }
}

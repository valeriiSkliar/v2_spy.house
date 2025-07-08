<?php

namespace Tests\Feature\Parsers\PushHouse;

use App\Http\DTOs\Parsers\PushHouseCreativeDTO;
use App\Services\Parsers\PushHouse\PushHouseApiClient;
use App\Services\Parsers\PushHouse\PushHouseParsingService;
use App\Services\Parsers\PushHouse\PushHouseSynchronizer;
use App\Services\Parsers\Exceptions\ParserException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use Tests\TestCase;
use Mockery;

/**
 * Feature тесты для PushHouseParsingService
 */
class PushHouseParsingServiceTest extends TestCase
{
    use RefreshDatabase;

    private PushHouseParsingService $parsingService;
    private $mockApiClient;
    private $mockSynchronizer;
    private Collection $mockCreatives;

    protected function setUp(): void
    {
        parent::setUp();

        $this->mockApiClient = Mockery::mock(PushHouseApiClient::class);
        $this->mockSynchronizer = Mockery::mock(PushHouseSynchronizer::class);

        $this->parsingService = new PushHouseParsingService(
            $this->mockApiClient,
            $this->mockSynchronizer
        );

        // Создаем тестовые DTO
        $this->mockCreatives = collect([
            new PushHouseCreativeDTO(
                externalId: 1001,
                title: 'Test Creative 1',
                text: 'Description 1',
                iconUrl: 'https://example.com/icon1.png',
                imageUrl: 'https://example.com/img1.png',
                targetUrl: 'https://example.com/landing1',
                cpc: 0.05,
                countryCode: 'US',
                platform: \App\Enums\Frontend\Platform::MOBILE,
                isAdult: false,
                isActive: true,
                createdAt: now()
            )
        ]);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    /** @test */
    public function it_can_perform_full_parsing_cycle()
    {
        $syncResult = [
            'new_creatives' => 1,
            'deactivated_creatives' => 0,
            'unchanged_creatives' => 5,
            'new_creative_ids' => [101],
            'deactivated_creative_ids' => []
        ];

        $integrityCheck = [
            'total_creatives' => 6,
            'active_creatives' => 6,
            'inactive_creatives' => 0,
            'integrity_check' => true
        ];

        $apiStats = [
            'base_url' => 'https://api.push.house',
            'timeout' => 45,
            'max_retries' => 3
        ];

        // Настраиваем моки
        $this->mockApiClient
            ->shouldReceive('fetchAllCreatives')
            ->with('active', 1)
            ->once()
            ->andReturn($this->mockCreatives);

        $this->mockSynchronizer
            ->shouldReceive('synchronize')
            ->with($this->mockCreatives)
            ->once()
            ->andReturn($syncResult);

        $this->mockSynchronizer
            ->shouldReceive('validateSyncIntegrity')
            ->once()
            ->andReturn($integrityCheck);

        $this->mockApiClient
            ->shouldReceive('getStats')
            ->once()
            ->andReturn($apiStats);

        // Выполняем парсинг
        $result = $this->parsingService->parseAndSync();

        // Проверяем результат
        $this->assertArrayHasKey('timestamp', $result);
        $this->assertArrayHasKey('duration_seconds', $result);
        $this->assertArrayHasKey('api_creatives_count', $result);
        $this->assertArrayHasKey('sync_result', $result);
        $this->assertArrayHasKey('job_data', $result);
        $this->assertArrayHasKey('integrity_check', $result);
        $this->assertArrayHasKey('api_client_stats', $result);

        $this->assertEquals(1, $result['api_creatives_count']);
        $this->assertEquals($syncResult, $result['sync_result']);
        $this->assertEquals($integrityCheck, $result['integrity_check']);
        $this->assertEquals($apiStats, $result['api_client_stats']);

        // Проверяем job_data
        $this->assertTrue($result['job_data']['should_dispatch_jobs']);
        $this->assertEquals([101], $result['job_data']['new_creative_ids']);
        $this->assertEquals([], $result['job_data']['deactivated_creative_ids']);
    }

    /** @test */
    public function it_handles_api_fetch_failure()
    {
        $this->mockApiClient
            ->shouldReceive('fetchAllCreatives')
            ->once()
            ->andThrow(new ParserException('API connection failed'));

        $this->expectException(ParserException::class);
        $this->expectExceptionMessageMatches('/Parsing cycle failed: Failed to fetch from API: API connection failed/');

        $this->parsingService->parseAndSync();
    }

    /** @test */
    public function it_can_test_api_connection()
    {
        $this->mockApiClient
            ->shouldReceive('testConnection')
            ->once()
            ->andReturn(true);

        $this->mockApiClient
            ->shouldReceive('getStats')
            ->once()
            ->andReturn(['base_url' => 'https://api.push.house']);

        $this->mockApiClient
            ->shouldReceive('fetchPage')
            ->with(1, 'active')
            ->once()
            ->andReturn(collect([new PushHouseCreativeDTO(
                externalId: 1,
                title: 'Test',
                text: 'Test',
                iconUrl: '',
                imageUrl: '',
                targetUrl: '',
                cpc: 0.01,
                countryCode: 'US',
                platform: \App\Enums\Frontend\Platform::MOBILE,
                isAdult: false,
                isActive: true,
                createdAt: now()
            )]));

        $result = $this->parsingService->testApiConnection();

        $this->assertEquals('success', $result['connection_status']);
        $this->assertEquals('success', $result['test_status']);
        $this->assertEquals(1, $result['test_data_count']);
    }

    /** @test */
    public function it_can_perform_dry_run()
    {
        $this->mockApiClient
            ->shouldReceive('fetchAllCreatives')
            ->with('active', 1)
            ->once()
            ->andReturn($this->mockCreatives);

        $this->mockSynchronizer
            ->shouldReceive('getExistingIds')
            ->once()
            ->andReturn([1001]); // ID уже существует

        $this->mockApiClient
            ->shouldReceive('getStats')
            ->once()
            ->andReturn(['base_url' => 'https://api.push.house']);

        $result = $this->parsingService->dryRun();

        $this->assertEquals('dry_run', $result['mode']);
        $this->assertEquals(1, $result['api_creatives_count']);
        $this->assertArrayHasKey('simulated_sync', $result);

        $simulatedSync = $result['simulated_sync'];
        $this->assertEquals(1, $simulatedSync['total_api_ids']);
        $this->assertEquals(1, $simulatedSync['total_db_ids']);
        $this->assertEquals(0, $simulatedSync['new_ids_count']); // Нет новых
        $this->assertEquals(0, $simulatedSync['deactivated_ids_count']);
        $this->assertEquals(1, $simulatedSync['unchanged_ids_count']);
    }
}

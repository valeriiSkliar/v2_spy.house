<?php

namespace Tests\Feature;

use App\Jobs\SyncAdvertismentNetworksJob;
use App\Models\AdvertismentNetwork;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class SyncAdvertismentNetworksTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Очищаем логи перед каждым тестом
        Log::spy();
    }

    /** @test */
    public function it_can_sync_new_networks_from_api()
    {
        // Подготавливаем существующие сети
        AdvertismentNetwork::factory()->create([
            'network_name' => 'existing_network',
            'network_display_name' => 'Existing Network',
        ]);

        // Мокаем API ответ
        Http::fake([
            'https://api.feed.house/internal/v1/ad-networks*' => Http::response([
                [
                    'id' => 1,
                    'code' => 'existing_network',
                    'name' => 'Existing Network'
                ],
                [
                    'id' => 2,
                    'code' => 'new_network',
                    'name' => 'New Network'
                ],
                [
                    'id' => 3,
                    'code' => 'another_new_network',
                    'name' => 'Another New Network'
                ]
            ], 200)
        ]);

        // Запускаем синхронизацию
        $job = new SyncAdvertismentNetworksJob();
        $job->handle();

        // Проверяем, что новые сети НЕ добавлены в БД (только заглушка)
        $this->assertDatabaseMissing('advertisment_networks', [
            'network_name' => 'new_network',
        ]);

        $this->assertDatabaseMissing('advertisment_networks', [
            'network_name' => 'another_new_network',
        ]);

        // Проверяем, что количество сетей не изменилось (только существующая)
        $this->assertEquals(1, AdvertismentNetwork::count());

        // Проверяем логирование новых сетей
        Log::shouldHaveReceived('info')
            ->with('NEW ADVERTISEMENT NETWORKS DETECTED! Administrator notification required.', \Mockery::type('array'));

        // Проверяем предупреждение для администратора
        Log::shouldHaveReceived('warning')
            ->with('ADMIN ACTION REQUIRED: New advertisement networks need manual review and approval.');
    }

    /** @test */
    public function it_handles_no_new_networks_correctly()
    {
        // Подготавливаем существующие сети
        AdvertismentNetwork::factory()->create([
            'network_name' => 'existing_network',
            'network_display_name' => 'Existing Network',
        ]);

        // Мокаем API ответ с только существующими сетями
        Http::fake([
            'https://api.feed.house/internal/v1/ad-networks*' => Http::response([
                [
                    'id' => 1,
                    'code' => 'existing_network',
                    'name' => 'Existing Network'
                ]
            ], 200)
        ]);

        // Запускаем синхронизацию
        $job = new SyncAdvertismentNetworksJob();
        $job->handle();

        // Проверяем, что количество сетей не изменилось
        $this->assertEquals(1, AdvertismentNetwork::count());

        // Проверяем логирование
        Log::shouldHaveReceived('info')
            ->with('No new advertisement networks found. Synchronization completed successfully.');
    }

    /** @test */
    public function it_handles_api_errors_correctly()
    {
        // Мокаем неуспешный API ответ
        Http::fake([
            'https://api.feed.house/internal/v1/ad-networks*' => Http::response([], 500)
        ]);

        $this->expectException(\Illuminate\Http\Client\RequestException::class);

        // Запускаем синхронизацию
        $job = new SyncAdvertismentNetworksJob();
        $job->handle();
    }

    /** @test */
    public function it_handles_empty_api_response()
    {
        // Мокаем пустой API ответ
        Http::fake([
            'https://api.feed.house/internal/v1/ad-networks*' => Http::response([], 200)
        ]);

        // Запускаем синхронизацию
        $job = new SyncAdvertismentNetworksJob();
        $job->handle();

        // Проверяем логирование
        Log::shouldHaveReceived('warning')
            ->with('No networks received from API');
    }

    /** @test */
    public function command_can_run_synchronously()
    {
        // Мокаем API ответ
        Http::fake([
            'https://api.feed.house/internal/v1/ad-networks*' => Http::response([
                [
                    'id' => 1,
                    'code' => 'test_network',
                    'name' => 'Test Network'
                ]
            ], 200)
        ]);

        // Запускаем команду
        $this->artisan('advertisment-networks:sync')
            ->expectsOutput('Starting advertisement networks synchronization...')
            ->expectsOutput('Running synchronization synchronously...')
            ->expectsOutput('Synchronization completed successfully!')
            ->assertExitCode(0);

        // Проверяем, что сеть НЕ добавлена в БД (только заглушка)
        $this->assertDatabaseMissing('advertisment_networks', [
            'network_name' => 'test_network',
        ]);
    }

    /** @test */
    public function command_can_dispatch_to_queue()
    {
        Queue::fake();

        // Запускаем команду с опцией --queue
        $this->artisan('advertisment-networks:sync --queue')
            ->expectsOutput('Starting advertisement networks synchronization...')
            ->expectsOutput('Synchronization job has been dispatched to the queue.')
            ->assertExitCode(0);

        // Проверяем, что job добавлен в очередь
        Queue::assertPushed(SyncAdvertismentNetworksJob::class);
    }

    /** @test */
    public function it_logs_new_networks_information_correctly()
    {
        // Мокаем API ответ
        Http::fake([
            'https://api.feed.house/internal/v1/ad-networks*' => Http::response([
                [
                    'id' => 1,
                    'code' => 'test_network',
                    'name' => 'Test Network'
                ]
            ], 200)
        ]);

        // Запускаем синхронизацию
        $job = new SyncAdvertismentNetworksJob();
        $job->handle();

        // Проверяем, что сеть НЕ создана в БД
        $network = AdvertismentNetwork::where('network_name', 'test_network')->first();
        $this->assertNull($network);

        // Проверяем правильное логирование
        Log::shouldHaveReceived('info')
            ->with('NEW ADVERTISEMENT NETWORKS DETECTED! Administrator notification required.', \Mockery::on(function ($arg) {
                return $arg['count'] === 1
                    && in_array('Test Network', $arg['networks'])
                    && in_array('test_network', $arg['codes'])
                    && isset($arg['details'])
                    && isset($arg['message']);
            }));

        Log::shouldHaveReceived('warning')
            ->with('ADMIN ACTION REQUIRED: New advertisement networks need manual review and approval.');
    }
}

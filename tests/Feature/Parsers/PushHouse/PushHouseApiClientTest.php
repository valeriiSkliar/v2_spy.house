<?php

namespace Tests\Feature\Parsers\PushHouse;

use App\Http\DTOs\Parsers\PushHouseCreativeDTO;
use App\Services\Parsers\PushHouse\PushHouseApiClient;
use App\Services\Parsers\Exceptions\ParserException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * Feature тесты для PushHouseApiClient
 * 
 * Тестирует интеграцию с реальным API, пагинацию,
 * обработку ошибок и преобразование в DTO
 * 
 * @package Tests\Feature\Parsers\PushHouse
 */
class PushHouseApiClientTest extends TestCase
{
    use RefreshDatabase;

    private PushHouseApiClient $apiClient;
    private array $mockApiResponse;

    protected function setUp(): void
    {
        parent::setUp();

        $this->apiClient = new PushHouseApiClient([
            'base_url' => 'https://api.push.house',
            'timeout' => 30,
            'max_retries' => 2,
            'max_pages' => 5
        ]);

        $this->mockApiResponse = [
            [
                'id' => 1393905,
                'title' => 'Test Title',
                'text' => 'Test Description',
                'icon' => 'https://example.com/icon.png',
                'img' => 'https://example.com/image.png',
                'url' => 'https://example.com/landing',
                'cpc' => '0.00770000',
                'country' => 'BR',
                'platform' => 'Mob',
                'isAdult' => false,
                'isActive' => true,
                'created_at' => '2025-01-01'
            ],
            [
                'id' => 1393906,
                'title' => 'Another Test',
                'text' => 'Another Description',
                'icon' => 'https://example.com/icon2.png',
                'img' => 'https://example.com/image2.png',
                'url' => 'https://example.com/landing2',
                'cpc' => '0.05000000',
                'country' => 'US',
                'platform' => 'Desktop',
                'isAdult' => true,
                'isActive' => true,
                'created_at' => '2025-01-02'
            ]
        ];
    }

    /** @test */
    public function it_can_fetch_single_page_successfully()
    {
        Http::fake([
            'https://api.push.house/v1/ads/1/active' => Http::response($this->mockApiResponse, 200)
        ]);

        $result = $this->apiClient->fetchPage(1, 'active');

        $this->assertCount(2, $result);
        $this->assertInstanceOf(PushHouseCreativeDTO::class, $result->first());
        $this->assertEquals(1393905, $result->first()->externalId);
        $this->assertEquals('Test Title', $result->first()->title);
    }

    /** @test */
    public function it_handles_empty_page_response()
    {
        Http::fake([
            'https://api.push.house/v1/ads/1/active' => Http::response([], 200)
        ]);

        $result = $this->apiClient->fetchPage(1, 'active');

        $this->assertTrue($result->isEmpty());
    }

    /** @test */
    public function it_handles_non_array_response()
    {
        Http::fake([
            'https://api.push.house/v1/ads/1/active' => Http::response('invalid response', 200)
        ]);

        $result = $this->apiClient->fetchPage(1, 'active');

        $this->assertTrue($result->isEmpty());
    }

    /** @test */
    public function it_filters_invalid_dto_items()
    {
        $invalidResponse = [
            [
                'id' => 1393905,
                'title' => 'Valid Item',
                'country' => 'BR',
                // Все необходимые поля присутствуют
                'text' => 'Test',
                'icon' => '',
                'img' => '',
                'url' => '',
                'cpc' => '0.01',
                'platform' => 'Mob',
                'isAdult' => false,
                'isActive' => true,
                'created_at' => '2025-01-01'
            ],
            [
                // Невалидный элемент - отсутствует id и country
                'title' => 'Invalid Item',
                'text' => 'Test'
            ]
        ];

        Http::fake([
            'https://api.push.house/v1/ads/1/active' => Http::response($invalidResponse, 200)
        ]);

        $result = $this->apiClient->fetchPage(1, 'active');

        $this->assertCount(1, $result); // Только валидный элемент
        $this->assertEquals(1393905, $result->first()->externalId);
    }

    /** @test */
    public function it_can_fetch_all_creatives_with_pagination()
    {
        Http::fake([
            'https://api.push.house/v1/ads/1/active' => Http::response($this->mockApiResponse, 200),
            'https://api.push.house/v1/ads/2/active' => Http::response([$this->mockApiResponse[0]], 200), // Одна запись на второй странице
            'https://api.push.house/v1/ads/3/active' => Http::response([], 200) // Пустая третья страница
        ]);

        $result = $this->apiClient->fetchAllCreatives('active', 1);

        $this->assertCount(3, $result); // 2 + 1 + 0
        $this->assertInstanceOf(PushHouseCreativeDTO::class, $result->first());
    }

    /** @test */
    public function it_stops_pagination_on_empty_page()
    {
        Http::fake([
            'https://api.push.house/v1/ads/1/active' => Http::response($this->mockApiResponse, 200),
            'https://api.push.house/v1/ads/2/active' => Http::response([], 200), // Пустая страница
            'https://api.push.house/v1/ads/3/active' => Http::response($this->mockApiResponse, 200) // Эта страница не должна быть запрошена
        ]);

        $result = $this->apiClient->fetchAllCreatives('active', 1);

        $this->assertCount(2, $result);

        // Проверяем, что третья страница не была запрошена
        Http::assertSentCount(2);
    }

    /** @test */
    public function it_handles_404_error_gracefully()
    {
        Http::fake([
            'https://api.push.house/v1/ads/1/active' => Http::response('Not Found', 404)
        ]);

        $this->expectException(ParserException::class);
        $this->expectExceptionMessage('Endpoint not found');

        $this->apiClient->fetchPage(1, 'active');
    }

    /** @test */
    public function it_retries_on_server_errors()
    {
        Http::fake([
            'https://api.push.house/v1/ads/1/active' => Http::sequence()
                ->push('Server Error', 500)
                ->push('Server Error', 500)
                ->push($this->mockApiResponse, 200)
        ]);

        $result = $this->apiClient->fetchPage(1, 'active');

        $this->assertCount(2, $result);

        // Проверяем, что было 3 запроса (2 неудачных + 1 успешный)
        Http::assertSentCount(3);
    }

    /** @test */
    public function it_handles_rate_limiting()
    {
        Http::fake([
            'https://api.push.house/v1/ads/1/active' => Http::sequence()
                ->push('Rate Limited', 429, ['Retry-After' => '1'])
                ->push($this->mockApiResponse, 200)
        ]);

        $result = $this->apiClient->fetchPage(1, 'active');

        $this->assertCount(2, $result);
        Http::assertSentCount(2);
    }

    /** @test */
    public function it_fails_after_max_retries()
    {
        Http::fake([
            'https://api.push.house/v1/ads/1/active' => Http::response('Server Error', 500)
        ]);

        $this->expectException(ParserException::class);
        $this->expectExceptionMessage('Request failed after 2 retries');

        $this->apiClient->fetchPage(1, 'active');
    }

    /** @test */
    public function it_continues_fetching_after_page_error()
    {
        Http::fake([
            'https://api.push.house/v1/ads/1/active' => Http::response($this->mockApiResponse, 200),
            'https://api.push.house/v1/ads/2/active' => Http::response('Server Error', 500), // Ошибка на второй странице
            'https://api.push.house/v1/ads/3/active' => Http::response($this->mockApiResponse, 200) // Эта страница не должна быть запрошена
        ]);

        $result = $this->apiClient->fetchAllCreatives('active', 1);

        // Должны получить только данные с первой страницы
        $this->assertCount(2, $result);

        // Проверяем, что было сделано несколько запросов (включая retry для второй страницы)
        // 1 запрос к первой странице + 3 retry к второй странице (max_retries = 2)
        Http::assertSentCount(4);
    }

    /** @test */
    public function it_respects_max_pages_limit()
    {
        // Настраиваем клиент с лимитом в 2 страницы
        $limitedClient = new PushHouseApiClient([
            'max_pages' => 2
        ]);

        Http::fake([
            'https://api.push.house/v1/ads/1/active' => Http::response($this->mockApiResponse, 200),
            'https://api.push.house/v1/ads/2/active' => Http::response($this->mockApiResponse, 200),
            'https://api.push.house/v1/ads/3/active' => Http::response($this->mockApiResponse, 200) // Эта страница не должна быть запрошена
        ]);

        $result = $limitedClient->fetchAllCreatives('active', 1);

        // Должно быть запрошено максимум 2 страницы
        Http::assertSentCount(2);
        $this->assertCount(4, $result); // 2 страницы * 2 элемента
    }

    /** @test */
    public function it_can_test_connection()
    {
        Http::fake([
            'https://api.push.house/v1/ads/1/active' => Http::response($this->mockApiResponse, 200)
        ]);

        $result = $this->apiClient->testConnection();

        $this->assertTrue($result);
    }

    /** @test */
    public function it_fails_connection_test_on_error()
    {
        Http::fake([
            'https://api.push.house/v1/ads/1/active' => Http::response('Error', 500)
        ]);

        $result = $this->apiClient->testConnection();

        $this->assertFalse($result);
    }

    /** @test */
    public function it_returns_correct_stats()
    {
        $stats = $this->apiClient->getStats();

        $this->assertArrayHasKey('base_url', $stats);
        $this->assertArrayHasKey('timeout', $stats);
        $this->assertArrayHasKey('max_retries', $stats);
        $this->assertArrayHasKey('max_pages', $stats);
        $this->assertArrayHasKey('rate_limit_delay_ms', $stats);

        $this->assertEquals('https://api.push.house', $stats['base_url']);
        $this->assertEquals(30, $stats['timeout']);
        $this->assertEquals(2, $stats['max_retries']);
        $this->assertEquals(5, $stats['max_pages']);
    }

    /** @test */
    public function it_handles_different_statuses()
    {
        Http::fake([
            'https://api.push.house/v1/ads/1/inactive' => Http::response($this->mockApiResponse, 200)
        ]);

        $result = $this->apiClient->fetchPage(1, 'inactive');

        $this->assertCount(2, $result);

        Http::assertSent(function ($request) {
            return str_contains($request->url(), '/ads/1/inactive');
        });
    }
}

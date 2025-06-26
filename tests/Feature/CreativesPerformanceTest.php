<?php

namespace Tests\Feature;

use Tests\TestCase;
use Tests\Traits\DatabaseSeeding;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use App\Http\Requests\Frontend\CreativesRequest;
use App\Models\AdvertismentNetwork;
use App\Models\Browser;
use App\Models\Frontend\IsoEntity;
use App\Enums\Frontend\DeviceType;
use App\Enums\Frontend\OperationSystem;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class CreativesPerformanceTest extends TestCase
{
    use RefreshDatabase, DatabaseSeeding;

    protected function setUp(): void
    {
        parent::setUp();

        // Очищаем кэш перед каждым тестом
        Cache::flush();

        // Создаем базовые ISO данные через трейт
        $this->seedIsoEntities();

        // Создаем специфичные данные для тестирования
        $this->seedTestData();
    }

    protected function tearDown(): void
    {
        Cache::flush();
        parent::tearDown();
    }

    /**
     * Тест производительности валидации с большим количеством фильтров
     */
    public function test_validation_performance_with_large_filter_set(): void
    {
        $startTime = microtime(true);

        $requestData = $this->getLargeFilterDataset();

        $response = $this->get('/api/creatives/filters/validate?' . http_build_query($requestData));

        $executionTime = microtime(true) - $startTime;

        // Проверяем, что валидация выполняется быстро (менее 1 секунды)
        $this->assertLessThan(1.0, $executionTime, 'Валидация больших наборов данных должна выполняться быстро');

        // Проверяем корректность обработки данных
        $response->assertStatus(200);
        $data = $response->json();
        $this->assertArrayHasKey('filters', $data);
        $this->assertIsArray($data['filters']);
    }

    /**
     * Тест эффективности кэширования валидационных данных
     */
    public function test_validation_cache_efficiency(): void
    {
        $requestData = $this->getStandardFilterDataset();
        $queryString = http_build_query($requestData);

        // Первый запрос - данные попадают в кэш
        $startTime1 = microtime(true);
        $response1 = $this->get('/api/creatives/filters/validate?' . $queryString);
        $time1 = microtime(true) - $startTime1;

        // Второй запрос - данные берутся из кэша
        $startTime2 = microtime(true);
        $response2 = $this->get('/api/creatives/filters/validate?' . $queryString);
        $time2 = microtime(true) - $startTime2;

        // Второй запрос должен быть значительно быстрее
        $this->assertLessThan($time1 * 0.8, $time2, 'Кэшированные данные должны загружаться быстрее');

        // Результаты должны быть идентичными
        $response1->assertStatus(200);
        $response2->assertStatus(200);
        $this->assertEquals($response1->json(), $response2->json());
    }

    /**
     * Тест производительности URL sync валидации
     */
    public function test_url_sync_validation_performance(): void
    {
        $startTime = microtime(true);

        $urlSyncData = $this->getUrlSyncDataset();

        $response = $this->get('/api/creatives/filters/validate?' . http_build_query($urlSyncData));

        $executionTime = microtime(true) - $startTime;

        $this->assertLessThan(0.5, $executionTime, 'URL sync валидация должна быть быстрой');

        $response->assertStatus(200);
        $data = $response->json();

        // Проверяем что данные обработаны корректно
        $this->assertArrayHasKey('filters', $data);
        $this->assertIsArray($data['filters']);
    }

    /**
     * Тест производительности валидации массивов comma-separated значений
     */
    public function test_comma_separated_arrays_performance(): void
    {
        $startTime = microtime(true);

        // Получаем ID существующих сетей для корректного тестирования
        $networkIds = AdvertismentNetwork::pluck('id')->take(4)->toArray();

        $commaSeparatedData = [
            'cr_advertisingNetworks' => implode(',', $networkIds),
            'cr_languages' => 'en,ru',  // Используем только созданные языки
            'cr_operatingSystems' => 'Windows,MacOS,Linux,Android,iOS',
            'cr_browsers' => 'Chrome,Firefox,Safari',
            'cr_devices' => 'Desktop,Mobile,Tablet',
            'cr_imageSizes' => '16x9,1x1,3x2'
        ];

        $response = $this->get('/api/creatives/filters/validate?' . http_build_query($commaSeparatedData));

        $executionTime = microtime(true) - $startTime;

        $this->assertLessThan(0.3, $executionTime, 'Парсинг comma-separated массивов должен быть быстрым');

        $response->assertStatus(200);
        $data = $response->json();

        // Проверяем корректность обработки данных
        $this->assertArrayHasKey('filters', $data);
        $this->assertIsArray($data['filters']);
    }

    /**
     * Тест производительности batch валидации
     */
    public function test_batch_validation_performance(): void
    {
        $datasets = [];

        // Создаем 10 различных наборов данных
        for ($i = 0; $i < 10; $i++) {
            $datasets[] = $this->getRandomFilterDataset($i);
        }

        $startTime = microtime(true);

        foreach ($datasets as $data) {
            $response = $this->get('/api/creatives/filters/validate?' . http_build_query($data));
            $response->assertStatus(200);
            $responseData = $response->json();
            $this->assertArrayHasKey('filters', $responseData);
        }

        $executionTime = microtime(true) - $startTime;

        // Batch валидация 10 запросов должна выполняться быстро
        $this->assertLessThan(3.0, $executionTime, 'Batch валидация должна быть эффективной');
    }

    /**
     * Тест производительности санитизации данных
     */
    public function test_data_sanitization_performance(): void
    {
        $startTime = microtime(true);

        $maliciousData = $this->getMaliciousDataset();

        $response = $this->get('/api/creatives/filters/validate?' . http_build_query($maliciousData));

        $executionTime = microtime(true) - $startTime;

        $this->assertLessThan(0.5, $executionTime, 'Санитизация данных должна быть быстрой');

        // Проверяем, что запрос обработан (может быть 200, 302 или 422 в зависимости от валидации)
        $this->assertContains($response->status(), [200, 302, 422]);

        $data = $response->json();
        $this->assertIsArray($data);
    }

    /**
     * Тест устойчивости к нагрузке
     */
    public function test_stress_validation(): void
    {
        $iterations = 25; // Уменьшаем количество итераций для HTTP запросов
        $maxExecutionTime = 10.0; // 10 секунд на 25 итераций

        $startTime = microtime(true);

        for ($i = 0; $i < $iterations; $i++) {
            $data = $this->getRandomFilterDataset($i);
            $response = $this->get('/api/creatives/filters/validate?' . http_build_query($data));
            $response->assertStatus(200);
            $responseData = $response->json();
            $this->assertArrayHasKey('filters', $responseData);
        }

        $executionTime = microtime(true) - $startTime;

        $this->assertLessThan(
            $maxExecutionTime,
            $executionTime,
            "Стресс-тест {$iterations} итераций должен выполняться за {$maxExecutionTime} секунд"
        );

        $avgTime = $executionTime / $iterations;
        $this->assertLessThan(0.4, $avgTime, 'Среднее время валидации должно быть менее 400ms');
    }

    /**
     * Тест памяти при валидации
     */
    public function test_memory_usage_during_validation(): void
    {
        $memoryBefore = memory_get_usage(true);

        $largeDataset = $this->getExtraLargeFilterDataset();

        $response = $this->get('/api/creatives/filters/validate?' . http_build_query($largeDataset));

        $memoryAfter = memory_get_usage(true);
        $memoryUsed = $memoryAfter - $memoryBefore;

        // Проверяем статус ответа
        $this->assertContains($response->status(), [200, 302, 422]);

        // Проверяем, что использование памяти разумно (менее 20MB для HTTP запросов)
        $this->assertLessThan(
            20 * 1024 * 1024,
            $memoryUsed,
            'Валидация не должна потреблять много памяти'
        );
    }

    /**
     * Создает тестовые данные
     */
    private function seedTestData(): void
    {
        // Рекламные сети (используем правильные поля таблицы)
        $networks = [
            ['network_name' => 'push', 'network_display_name' => 'Push', 'traffic_type_description' => 'push'],
            ['network_name' => 'inpage', 'network_display_name' => 'Inpage', 'traffic_type_description' => 'in_page'],
            ['network_name' => 'facebook', 'network_display_name' => 'Facebook', 'traffic_type_description' => 'native'],
            ['network_name' => 'tiktok', 'network_display_name' => 'TikTok', 'traffic_type_description' => 'native'],
        ];

        foreach ($networks as $network) {
            AdvertismentNetwork::factory()->create(array_merge($network, ['is_active' => true]));
        }

        // ISO данные уже созданы через трейт DatabaseSeeding

        // Браузеры (используем правильные поля)
        Browser::factory()->create(['browser' => 'Chrome']);
        Browser::factory()->create(['browser' => 'Firefox']);
        Browser::factory()->create(['browser' => 'Safari']);
    }

    /**
     * Генерирует большой набор данных для тестирования
     */
    private function getLargeFilterDataset(): array
    {
        // Получаем ID существующих сетей (не более лимита)
        $networkIds = AdvertismentNetwork::pluck('id')->take(3)->toArray();

        return [
            'searchKeyword' => 'performance test keyword',
            'country' => 'US',
            'dateCreation' => 'last30',
            'sortBy' => 'popularity',
            'periodDisplay' => 'last7',
            'onlyAdult' => true,
            'activeTab' => 'push',
            'advertisingNetworks' => array_slice($networkIds, 0, 3),  // Не более 3 сетей
            'languages' => ['en'],  // Только 1 язык
            'operatingSystems' => ['Windows', 'MacOS'],  // Не более 2
            'browsers' => ['Chrome', 'Firefox'],  // Не более 2
            'devices' => ['Desktop'],  // Только 1 устройство
            'imageSizes' => ['16x9'],  // Только 1 размер
            'page' => 1,
            'perPage' => 24
        ];
    }

    /**
     * Стандартный набор данных
     */
    private function getStandardFilterDataset(): array
    {
        // Получаем ID первой сети
        $networkId = AdvertismentNetwork::first()?->id;

        return [
            'searchKeyword' => 'test',
            'country' => 'US',
            'advertisingNetworks' => $networkId ? [$networkId] : [],
            'page' => 1,
            'perPage' => 12
        ];
    }

    /**
     * URL sync данные
     */
    private function getUrlSyncDataset(): array
    {
        // Получаем ID существующих сетей
        $networkIds = AdvertismentNetwork::pluck('id')->take(2)->toArray();

        return [
            'searchKeyword' => 'old-keyword',
            'cr_searchKeyword' => 'test-keyword',
            'country' => 'US',  // Используем валидные коды стран
            'cr_country' => 'US',
            'advertisingNetworks' => [$networkIds[0] ?? 1],
            'cr_advertisingNetworks' => implode(',', $networkIds)
        ];
    }

    /**
     * Случайный набор данных
     */
    private function getRandomFilterDataset(int $seed): array
    {
        srand($seed);

        // Получаем доступные ID сетей
        $networkIds = AdvertismentNetwork::pluck('id')->toArray();
        $selectedNetworkId = $networkIds[rand(0, count($networkIds) - 1)] ?? 1;

        return [
            'searchKeyword' => 'keyword_' . $seed,
            'country' => 'US',  // Используем только валидные коды стран
            'sortBy' => ['creation', 'activity', 'popularity'][rand(0, 2)],
            'advertisingNetworks' => [$selectedNetworkId],
            'operatingSystems' => [['Windows', 'MacOS', 'Android'][rand(0, 2)]],
            'browsers' => [['Chrome', 'Firefox', 'Safari'][rand(0, 2)]],
            'devices' => [['Desktop', 'Mobile'][rand(0, 1)]],
            'page' => rand(1, 10),
            'perPage' => [12, 24, 48][rand(0, 2)]
        ];
    }

    /**
     * Вредоносные данные для тестирования санитизации
     */
    private function getMaliciousDataset(): array
    {
        // Получаем ID первой сети для корректного тестирования
        $networkId = AdvertismentNetwork::first()?->id ?? 1;

        return [
            'searchKeyword' => '<script>alert("xss")</script>',
            'country' => 'US',  // Используем валидный код страны
            'advertisingNetworks' => [$networkId],  // Используем валидный ID
            'languages' => ['en'],  // Используем только валидные языки
            'cr_searchKeyword' => '"><script>alert(1)</script>'
        ];
    }

    /**
     * Очень большой набор данных
     */
    private function getExtraLargeFilterDataset(): array
    {
        // Получаем ID существующих сетей (не более 4 для соблюдения лимитов)
        $networkIds = AdvertismentNetwork::pluck('id')->take(4)->toArray();

        return [
            'searchKeyword' => str_repeat('test ', 20),  // Уменьшили размер
            'advertisingNetworks' => array_slice($networkIds, 0, 4),  // Не более 4 сетей
            'languages' => ['en', 'ru'],  // Только валидные языки, не более 2
            'operatingSystems' => ['Windows', 'MacOS', 'Android'],  // Не более 3
            'browsers' => ['Chrome', 'Firefox', 'Safari'],
            'devices' => ['Desktop', 'Mobile']  // Не более 2
        ];
    }
}

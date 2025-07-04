<?php

namespace Tests\Unit\Services\Parsers;

use App\Models\AdSource;
use App\Models\Frontend\IsoEntity;
use App\Services\Parsers\CountryCodeNormalizer;
use App\Services\Parsers\SourceNormalizer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;
use Tests\Traits\DatabaseSeeding;

class NormalizersIntegrationTest extends TestCase
{
    use RefreshDatabase, DatabaseSeeding;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedTestData();
        $this->clearAllCaches();
    }

    protected function tearDown(): void
    {
        $this->clearAllCaches();
        parent::tearDown();
    }

    /**
     * Создание тестовых данных
     */
    protected function seedTestData(): void
    {
        // Создаем тестовые страны
        $this->seedTestCountries();

        // Создаем тестовые источники
        $sources = [
            ['push_house', 'Push House'],
            ['tiktok', 'TikTok Ads'],
            ['facebook', 'Facebook Ads'],
        ];

        foreach ($sources as [$sourceName, $displayName]) {
            AdSource::create([
                'source_name' => $sourceName,
                'source_display_name' => $displayName,
            ]);
        }
    }

    /**
     * Очистка всех кешей нормализаторов
     */
    protected function clearAllCaches(): void
    {
        CountryCodeNormalizer::clearCache();
        SourceNormalizer::clearCache();
    }

    /**
     * Тест совместной работы нормализаторов
     */
    public function test_normalizers_work_together(): void
    {
        // Данные как из Push.House API
        $apiData = [
            'country' => 'US',
            'source' => 'push_house',
        ];

        $countryId = CountryCodeNormalizer::normalizeCountryCode($apiData['country']);
        $sourceId = SourceNormalizer::normalizeSourceName($apiData['source']);

        $this->assertNotNull($countryId);
        $this->assertNotNull($sourceId);
        $this->assertIsInt($countryId);
        $this->assertIsInt($sourceId);

        // Проверяем, что данные корректны
        $country = IsoEntity::find($countryId);
        $source = AdSource::find($sourceId);

        $this->assertEquals('US', $country->iso_code_2);
        $this->assertEquals('country', $country->type);
        $this->assertEquals('push_house', $source->source_name);
    }

    /**
     * Тест пакетной обработки с обоими нормализаторами
     */
    public function test_batch_normalization_with_both_normalizers(): void
    {
        $apiDataBatch = [
            ['country' => 'US', 'source' => 'push_house'],
            ['country' => 'CA', 'source' => 'tiktok'],
            ['country' => 'GB', 'source' => 'facebook'],
            ['country' => 'INVALID', 'source' => 'unknown_source'],
        ];

        $countries = array_column($apiDataBatch, 'country');
        $sources = array_column($apiDataBatch, 'source');

        $normalizedCountries = CountryCodeNormalizer::normalizeBatch($countries);
        $normalizedSources = SourceNormalizer::normalizeBatch($sources);

        // Проверяем валидные данные
        $this->assertNotNull($normalizedCountries['US']);
        $this->assertNotNull($normalizedCountries['CA']);
        $this->assertNotNull($normalizedCountries['GB']);
        $this->assertNull($normalizedCountries['INVALID']);

        $this->assertNotNull($normalizedSources['push_house']);
        $this->assertNotNull($normalizedSources['tiktok']);
        $this->assertNotNull($normalizedSources['facebook']);
        $this->assertNull($normalizedSources['unknown_source']);
    }

    /**
     * Тест производительности кеширования обоих нормализаторов
     */
    public function test_caching_performance_for_both_normalizers(): void
    {
        $testData = [
            ['US', 'push_house'],
            ['CA', 'tiktok'],
            ['GB', 'facebook'],
            ['US', 'push_house'], // Повторяющиеся данные
            ['CA', 'tiktok'],
        ];

        // Первый проход - загрузка в кеш
        $start = microtime(true);
        foreach ($testData as [$country, $source]) {
            CountryCodeNormalizer::normalizeCountryCode($country);
            SourceNormalizer::normalizeSourceName($source);
        }
        $firstPassTime = microtime(true) - $start;

        // Проверяем, что кеши созданы
        $this->assertTrue(Cache::has('country_normalizer.iso_to_id_map'));
        $this->assertTrue(Cache::has('source_normalizer.name_to_id_map'));

        // Второй проход - использование кеша
        $start = microtime(true);
        foreach ($testData as [$country, $source]) {
            CountryCodeNormalizer::normalizeCountryCode($country);
            SourceNormalizer::normalizeSourceName($source);
        }
        $secondPassTime = microtime(true) - $start;

        // Второй проход должен быть быстрее (используется кеш)
        $this->assertLessThan($firstPassTime, $secondPassTime, 'Cached operations should be faster');
        $this->assertLessThan(0.01, $secondPassTime, 'Cached operations should be very fast');
    }

    /**
     * Тест обработки смешанных валидных и невалидных данных
     */
    public function test_handles_mixed_valid_invalid_data(): void
    {
        $mixedData = [
            ['country' => 'US', 'source' => 'push_house'],      // Валидные
            ['country' => 'INVALID', 'source' => 'unknown'],    // Невалидные
            ['country' => 'CA', 'source' => 'tiktok'],          // Валидные
            ['country' => '', 'source' => ''],                  // Пустые
            ['country' => 'GB', 'source' => 'facebook'],        // Валидные
        ];

        $results = [];
        foreach ($mixedData as $data) {
            $countryId = CountryCodeNormalizer::normalizeCountryCode($data['country']);
            $sourceId = SourceNormalizer::normalizeSourceName($data['source']);

            $results[] = [
                'country_id' => $countryId,
                'source_id' => $sourceId,
                'is_valid' => $countryId !== null && $sourceId !== null,
            ];
        }

        // Проверяем результаты
        $this->assertTrue($results[0]['is_valid']);   // US + push_house
        $this->assertFalse($results[1]['is_valid']);  // INVALID + unknown
        $this->assertTrue($results[2]['is_valid']);   // CA + tiktok
        $this->assertFalse($results[3]['is_valid']);  // пустые
        $this->assertTrue($results[4]['is_valid']);   // GB + facebook

        $validCount = array_sum(array_column($results, 'is_valid'));
        $this->assertEquals(3, $validCount);
    }

    /**
     * Тест автосоздания источника при нормализации
     */
    public function test_auto_create_source_during_normalization(): void
    {
        // Проверяем, что источника не существует
        $this->assertDatabaseMissing('ad_sources', ['source_name' => 'new_auto_source']);

        // Используем handleUnknownSource с автосозданием
        $sourceId = SourceNormalizer::handleUnknownSource('new_auto_source', true);

        $this->assertNotNull($sourceId);
        $this->assertIsInt($sourceId);
        $this->assertDatabaseHas('ad_sources', [
            'id' => $sourceId,
            'source_name' => 'new_auto_source',
            'source_display_name' => 'New Auto Source',
        ]);

        // Проверяем, что кеш очистился и новый источник доступен
        $newSourceId = SourceNormalizer::normalizeSourceName('new_auto_source');
        $this->assertEquals($sourceId, $newSourceId);
    }

    /**
     * Тест получения статистики от обоих нормализаторов
     */
    public function test_gets_combined_statistics(): void
    {
        // Инициализируем нормализаторы
        CountryCodeNormalizer::normalizeCountryCode('US');
        SourceNormalizer::normalizeSourceName('push_house');

        $countryStats = CountryCodeNormalizer::getStats();
        $sourceStats = SourceNormalizer::getStats();

        // Проверяем статистику стран
        $this->assertArrayHasKey('total_mappings', $countryStats);
        $this->assertArrayHasKey('iso2_codes', $countryStats);
        $this->assertArrayHasKey('iso3_codes', $countryStats);
        $this->assertTrue($countryStats['cache_initialized']);

        // Проверяем статистику источников
        $this->assertArrayHasKey('total_mappings', $sourceStats);
        $this->assertArrayHasKey('available_sources', $sourceStats);
        $this->assertTrue($sourceStats['cache_initialized']);

        // Проверяем содержимое
        $this->assertGreaterThan(0, $countryStats['total_mappings']);
        $this->assertEquals(3, $sourceStats['total_mappings']); // 3 тестовых источника
        $this->assertContains('push_house', $sourceStats['available_sources']);
    }

    /**
     * Тест очистки кешей нормализаторов
     */
    public function test_clears_all_normalizer_caches(): void
    {
        // Инициализируем кеши
        CountryCodeNormalizer::normalizeCountryCode('US');
        SourceNormalizer::normalizeSourceName('push_house');

        $this->assertTrue(Cache::has('country_normalizer.iso_to_id_map'));
        $this->assertTrue(Cache::has('source_normalizer.name_to_id_map'));

        // Очищаем все кеши
        $this->clearAllCaches();

        $this->assertFalse(Cache::has('country_normalizer.iso_to_id_map'));
        $this->assertFalse(Cache::has('source_normalizer.name_to_id_map'));
    }

    /**
     * Тест работы нормализаторов с реальными данными Push.House
     */
    public function test_real_push_house_data_normalization(): void
    {
        // Реальный пример данных от Push.House API
        $pushHouseData = [
            [
                'id' => 1393905,
                'country' => 'BR',
                'source' => 'push_house',
            ],
            [
                'id' => 1393904,
                'country' => 'JP',
                'source' => 'push_house',
            ],
        ];

        // Создаем недостающие страны
        IsoEntity::create([
            'type' => 'country',
            'iso_code_2' => 'BR',
            'iso_code_3' => 'BRA',
            'name' => 'Brazil',
            'is_active' => true,
        ]);

        IsoEntity::create([
            'type' => 'country',
            'iso_code_2' => 'JP',
            'iso_code_3' => 'JPN',
            'name' => 'Japan',
            'is_active' => true,
        ]);

        CountryCodeNormalizer::clearCache();

        $normalizedData = [];
        foreach ($pushHouseData as $item) {
            $countryId = CountryCodeNormalizer::normalizeCountryCode($item['country']);
            $sourceId = SourceNormalizer::normalizeSourceName($item['source']);

            $normalizedData[] = [
                'external_id' => $item['id'],
                'country_id' => $countryId,
                'source_id' => $sourceId,
            ];
        }

        // Проверяем результаты
        $this->assertCount(2, $normalizedData);

        foreach ($normalizedData as $data) {
            $this->assertNotNull($data['country_id']);
            $this->assertNotNull($data['source_id']);
            $this->assertIsInt($data['country_id']);
            $this->assertIsInt($data['source_id']);
        }

        // Проверяем конкретные значения
        $this->assertNotEquals($normalizedData[0]['country_id'], $normalizedData[1]['country_id']); // BR != JP
        $this->assertEquals($normalizedData[0]['source_id'], $normalizedData[1]['source_id']); // Оба push_house
    }
}

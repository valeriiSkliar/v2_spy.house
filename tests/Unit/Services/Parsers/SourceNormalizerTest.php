<?php

namespace Tests\Unit\Services\Parsers;

use App\Models\AdSource;
use App\Services\Parsers\SourceNormalizer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class SourceNormalizerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedTestSources();
        SourceNormalizer::clearCache(); // Очищаем кеш перед каждым тестом
    }

    protected function tearDown(): void
    {
        SourceNormalizer::clearCache();
        parent::tearDown();
    }

    /**
     * Создание тестовых источников
     */
    protected function seedTestSources(): void
    {
        $sources = [
            ['push_house', 'Push House'],
            ['tiktok', 'TikTok Ads'],
            ['facebook', 'Facebook Ads'],
            ['feed_house', 'Feed House'],
            ['google_ads', 'Google Ads'],
        ];

        foreach ($sources as [$sourceName, $displayName]) {
            AdSource::create([
                'source_name' => $sourceName,
                'source_display_name' => $displayName,
            ]);
        }
    }

    /**
     * Тест нормализации валидного источника
     */
    public function test_normalizes_valid_source_name(): void
    {
        $sourceId = SourceNormalizer::normalizeSourceName('push_house');

        $this->assertNotNull($sourceId);
        $this->assertIsInt($sourceId);

        $source = AdSource::find($sourceId);
        $this->assertEquals('push_house', $source->source_name);
        $this->assertEquals('Push House', $source->source_display_name);
    }

    /**
     * Тест нормализации с верхним регистром
     */
    public function test_normalizes_uppercase_source_name(): void
    {
        $sourceId = SourceNormalizer::normalizeSourceName('PUSH_HOUSE');

        $this->assertNotNull($sourceId);

        $source = AdSource::find($sourceId);
        $this->assertEquals('push_house', $source->source_name);
    }

    /**
     * Тест нормализации со смешанным регистром
     */
    public function test_normalizes_mixed_case_source_name(): void
    {
        $sourceId = SourceNormalizer::normalizeSourceName('TikTok');

        $this->assertNotNull($sourceId);

        $source = AdSource::find($sourceId);
        $this->assertEquals('tiktok', $source->source_name);
    }

    /**
     * Тест нормализации с пробелами
     */
    public function test_normalizes_source_name_with_whitespace(): void
    {
        $sourceId = SourceNormalizer::normalizeSourceName('  facebook  ');

        $this->assertNotNull($sourceId);

        $source = AdSource::find($sourceId);
        $this->assertEquals('facebook', $source->source_name);
    }

    /**
     * Тест обработки невалидного источника
     */
    public function test_returns_null_for_invalid_source_name(): void
    {
        $sourceId = SourceNormalizer::normalizeSourceName('non_existent_source');

        $this->assertNull($sourceId);
    }

    /**
     * Тест обработки пустой строки
     */
    public function test_returns_null_for_empty_string(): void
    {
        $this->assertNull(SourceNormalizer::normalizeSourceName(''));
        $this->assertNull(SourceNormalizer::normalizeSourceName('   '));
    }

    /**
     * Тест пакетной нормализации
     */
    public function test_normalizes_batch_source_names(): void
    {
        $sources = ['push_house', 'TIKTOK', 'Facebook', 'non_existent', ''];
        $result = SourceNormalizer::normalizeBatch($sources);

        $this->assertIsArray($result);
        $this->assertCount(5, $result);

        $this->assertNotNull($result['push_house']);
        $this->assertNotNull($result['TIKTOK']);
        $this->assertNotNull($result['Facebook']);
        $this->assertNull($result['non_existent']);
        $this->assertNull($result['']);
    }

    /**
     * Тест пакетной нормализации с пустым массивом
     */
    public function test_normalizes_batch_empty_array(): void
    {
        $result = SourceNormalizer::normalizeBatch([]);

        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }

    /**
     * Тест валидации источника
     */
    public function test_validates_source_name(): void
    {
        $this->assertTrue(SourceNormalizer::isValidSourceName('push_house'));
        $this->assertTrue(SourceNormalizer::isValidSourceName('TIKTOK'));
        $this->assertTrue(SourceNormalizer::isValidSourceName('  facebook  '));
        $this->assertFalse(SourceNormalizer::isValidSourceName('non_existent'));
        $this->assertFalse(SourceNormalizer::isValidSourceName(''));
    }

    /**
     * Тест получения информации об источнике
     */
    public function test_gets_source_info(): void
    {
        $info = SourceNormalizer::getSourceInfo('push_house');

        $this->assertIsArray($info);
        $this->assertArrayHasKey('id', $info);
        $this->assertArrayHasKey('source_name', $info);
        $this->assertArrayHasKey('source_display_name', $info);

        $this->assertEquals('push_house', $info['source_name']);
        $this->assertEquals('Push House', $info['source_display_name']);
    }

    /**
     * Тест получения информации о несуществующем источнике
     */
    public function test_gets_null_info_for_non_existent_source(): void
    {
        $info = SourceNormalizer::getSourceInfo('non_existent');

        $this->assertNull($info);
    }

    /**
     * Тест создания источника если не существует
     */
    public function test_creates_source_if_not_exists(): void
    {
        $this->assertDatabaseMissing('ad_sources', ['source_name' => 'new_source']);

        $sourceId = SourceNormalizer::createIfNotExists('new_source', 'New Source');

        $this->assertIsInt($sourceId);
        $this->assertDatabaseHas('ad_sources', [
            'id' => $sourceId,
            'source_name' => 'new_source',
            'source_display_name' => 'New Source',
        ]);
    }

    /**
     * Тест создания источника с автогенерацией display name
     */
    public function test_creates_source_with_auto_generated_display_name(): void
    {
        $sourceId = SourceNormalizer::createIfNotExists('auto_generated_source');

        $this->assertDatabaseHas('ad_sources', [
            'id' => $sourceId,
            'source_name' => 'auto_generated_source',
            'source_display_name' => 'Auto Generated Source',
        ]);
    }

    /**
     * Тест создания источника с нормализацией названия
     */
    public function test_creates_source_with_normalized_name(): void
    {
        $sourceId = SourceNormalizer::createIfNotExists('  NEW_SOURCE  ', 'New Source');

        $this->assertDatabaseHas('ad_sources', [
            'id' => $sourceId,
            'source_name' => 'new_source',
            'source_display_name' => 'New Source',
        ]);
    }

    /**
     * Тест возврата существующего источника при попытке создания
     */
    public function test_returns_existing_source_when_creating_duplicate(): void
    {
        $existingSource = AdSource::where('source_name', 'push_house')->first();

        $sourceId = SourceNormalizer::createIfNotExists('push_house', 'Different Display Name');

        $this->assertEquals($existingSource->id, $sourceId);

        // Проверяем, что display name не изменилось
        $source = AdSource::find($sourceId);
        $this->assertEquals('Push House', $source->source_display_name);
    }

    /**
     * Тест кеширования
     */
    public function test_caches_source_map(): void
    {
        // Первый вызов должен загрузить данные из БД
        $sourceId1 = SourceNormalizer::normalizeSourceName('push_house');

        // Проверяем, что данные закешированы
        $this->assertTrue(Cache::has('source_normalizer.name_to_id_map'));

        // Второй вызов должен использовать кеш
        $sourceId2 = SourceNormalizer::normalizeSourceName('push_house');

        $this->assertEquals($sourceId1, $sourceId2);
    }

    /**
     * Тест очистки кеша
     */
    public function test_clears_cache(): void
    {
        // Загружаем данные в кеш
        SourceNormalizer::normalizeSourceName('push_house');
        $this->assertTrue(Cache::has('source_normalizer.name_to_id_map'));

        // Очищаем кеш
        SourceNormalizer::clearCache();
        $this->assertFalse(Cache::has('source_normalizer.name_to_id_map'));
    }

    /**
     * Тест очистки кеша при создании нового источника
     */
    public function test_clears_cache_when_creating_new_source(): void
    {
        // Загружаем данные в кеш
        SourceNormalizer::normalizeSourceName('push_house');
        $this->assertTrue(Cache::has('source_normalizer.name_to_id_map'));

        // Создаем новый источник
        SourceNormalizer::createIfNotExists('cache_test_source');

        // Кеш должен быть очищен
        $this->assertFalse(Cache::has('source_normalizer.name_to_id_map'));
    }

    /**
     * Тест получения статистики
     */
    public function test_gets_stats(): void
    {
        $stats = SourceNormalizer::getStats();

        $this->assertIsArray($stats);
        $this->assertArrayHasKey('total_mappings', $stats);
        $this->assertArrayHasKey('cache_initialized', $stats);
        $this->assertArrayHasKey('available_sources', $stats);

        $this->assertEquals(5, $stats['total_mappings']); // 5 тестовых источников
        $this->assertTrue($stats['cache_initialized']);
        $this->assertIsArray($stats['available_sources']);
        $this->assertContains('push_house', $stats['available_sources']);
    }

    /**
     * Тест получения доступных источников
     */
    public function test_gets_available_sources(): void
    {
        $sources = SourceNormalizer::getAvailableSources();

        $this->assertIsArray($sources);
        $this->assertCount(5, $sources);
        $this->assertContains('push_house', $sources);
        $this->assertContains('tiktok', $sources);
        $this->assertContains('facebook', $sources);
        $this->assertContains('feed_house', $sources);
        $this->assertContains('google_ads', $sources);
    }

    /**
     * Тест обработки неизвестного источника без автосоздания
     */
    public function test_handles_unknown_source_without_auto_create(): void
    {
        $result = SourceNormalizer::handleUnknownSource('unknown_source', false);

        $this->assertNull($result);
        $this->assertDatabaseMissing('ad_sources', ['source_name' => 'unknown_source']);
    }

    /**
     * Тест обработки неизвестного источника с автосозданием
     */
    public function test_handles_unknown_source_with_auto_create(): void
    {
        $result = SourceNormalizer::handleUnknownSource('auto_created_source', true);

        $this->assertIsInt($result);
        $this->assertDatabaseHas('ad_sources', [
            'id' => $result,
            'source_name' => 'auto_created_source',
            'source_display_name' => 'Auto Created Source',
        ]);
    }

    /**
     * Тест производительности с большим количеством источников
     */
    public function test_performance_with_many_sources(): void
    {
        // Создаем дополнительные источники
        for ($i = 1; $i <= 50; $i++) {
            AdSource::create([
                'source_name' => "test_source_{$i}",
                'source_display_name' => "Test Source {$i}",
            ]);
        }

        SourceNormalizer::clearCache();

        $start = microtime(true);

        // Тестируем множественные вызовы
        for ($i = 1; $i <= 10; $i++) {
            SourceNormalizer::normalizeSourceName("test_source_{$i}");
        }

        $end = microtime(true);
        $duration = $end - $start;

        // Проверяем, что операции выполняются быстро (менее 100мс)
        $this->assertLessThan(0.1, $duration, 'Normalization should be fast with caching');

        $stats = SourceNormalizer::getStats();
        $this->assertEquals(55, $stats['total_mappings']); // 5 + 50 источников
    }

    /**
     * Тест работы с источниками содержащими спецсимволы
     */
    public function test_handles_source_names_with_special_characters(): void
    {
        AdSource::create([
            'source_name' => 'source-with-dash',
            'source_display_name' => 'Source With Dash',
        ]);

        AdSource::create([
            'source_name' => 'source.with.dot',
            'source_display_name' => 'Source With Dot',
        ]);

        SourceNormalizer::clearCache();

        $dashId = SourceNormalizer::normalizeSourceName('source-with-dash');
        $dotId = SourceNormalizer::normalizeSourceName('source.with.dot');

        $this->assertNotNull($dashId);
        $this->assertNotNull($dotId);
        $this->assertNotEquals($dashId, $dotId);
    }
}

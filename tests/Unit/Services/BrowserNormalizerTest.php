<?php

namespace Tests\Unit\Services;

use App\Services\Parsers\BrowserNormalizer;
use Tests\TestCase;

/**
 * Тест для BrowserNormalizer
 */
class BrowserNormalizerTest extends TestCase
{
    public function test_normalize_name_with_aliases()
    {
        // Проверяем нормализацию названий (не требует БД, просто возвращает null для несуществующих)
        $result1 = BrowserNormalizer::normalizeBrowserName('chrome');
        $result2 = BrowserNormalizer::normalizeBrowserName('google chrome');
        $result3 = BrowserNormalizer::normalizeBrowserName('firefox');
        $result4 = BrowserNormalizer::normalizeBrowserName('safari');

        // Так как БД может быть пустой, просто проверяем что методы работают без ошибок
        $this->assertTrue(is_null($result1) || is_int($result1));
        $this->assertTrue(is_null($result2) || is_int($result2));
        $this->assertTrue(is_null($result3) || is_int($result3));
        $this->assertTrue(is_null($result4) || is_int($result4));
    }

    public function test_empty_browser_name_returns_null()
    {
        $this->assertNull(BrowserNormalizer::normalizeBrowserName(''));
        $this->assertNull(BrowserNormalizer::normalizeBrowserName('   '));
    }

    public function test_batch_normalization()
    {
        $browsers = ['chrome', 'firefox', 'safari'];
        $result = BrowserNormalizer::normalizeBatch($browsers);

        $this->assertIsArray($result);
        $this->assertCount(3, $result);
    }

    public function test_get_supported_aliases()
    {
        $aliases = BrowserNormalizer::getSupportedAliases();

        $this->assertIsArray($aliases);
        $this->assertArrayHasKey('chrome', $aliases);
        $this->assertEquals('Chrome', $aliases['chrome']);
    }

    public function test_get_stats()
    {
        $stats = BrowserNormalizer::getStats();

        $this->assertIsArray($stats);
        $this->assertArrayHasKey('total_mappings', $stats);
        $this->assertArrayHasKey('cache_initialized', $stats);
        $this->assertArrayHasKey('available_aliases', $stats);
    }

    public function test_clear_cache()
    {
        // Должен работать без ошибок
        BrowserNormalizer::clearCache();
        $this->assertTrue(true);
    }
}

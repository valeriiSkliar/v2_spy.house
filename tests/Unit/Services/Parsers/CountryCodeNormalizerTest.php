<?php

namespace Tests\Unit\Services\Parsers;

use App\Models\Frontend\IsoEntity;
use App\Services\Parsers\CountryCodeNormalizer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;
use Tests\Traits\DatabaseSeeding;

class CountryCodeNormalizerTest extends TestCase
{
    use RefreshDatabase, DatabaseSeeding;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedTestCountries();
        CountryCodeNormalizer::clearCache(); // Очищаем кеш перед каждым тестом
    }

    protected function tearDown(): void
    {
        CountryCodeNormalizer::clearCache();
        parent::tearDown();
    }

    /**
     * Тест нормализации валидного ISO2 кода
     */
    public function test_normalizes_valid_iso2_country_code(): void
    {
        $countryId = CountryCodeNormalizer::normalizeCountryCode('US');

        $this->assertNotNull($countryId);
        $this->assertIsInt($countryId);

        $country = IsoEntity::find($countryId);
        $this->assertEquals('US', $country->iso_code_2);
        $this->assertEquals('country', $country->type);
    }

    /**
     * Тест нормализации валидного ISO3 кода
     */
    public function test_normalizes_valid_iso3_country_code(): void
    {
        $countryId = CountryCodeNormalizer::normalizeCountryCode('USA');

        $this->assertNotNull($countryId);
        $this->assertIsInt($countryId);

        $country = IsoEntity::find($countryId);
        $this->assertEquals('USA', $country->iso_code_3);
        $this->assertEquals('country', $country->type);
    }

    /**
     * Тест нормализации с нижним регистром
     */
    public function test_normalizes_lowercase_country_code(): void
    {
        $countryId = CountryCodeNormalizer::normalizeCountryCode('us');

        $this->assertNotNull($countryId);

        $country = IsoEntity::find($countryId);
        $this->assertEquals('US', $country->iso_code_2);
    }

    /**
     * Тест нормализации с пробелами
     */
    public function test_normalizes_country_code_with_whitespace(): void
    {
        $countryId = CountryCodeNormalizer::normalizeCountryCode('  CA  ');

        $this->assertNotNull($countryId);

        $country = IsoEntity::find($countryId);
        $this->assertEquals('CA', $country->iso_code_2);
    }

    /**
     * Тест обработки невалидного кода
     */
    public function test_returns_null_for_invalid_country_code(): void
    {
        $countryId = CountryCodeNormalizer::normalizeCountryCode('INVALID');

        $this->assertNull($countryId);
    }

    /**
     * Тест обработки пустой строки
     */
    public function test_returns_null_for_empty_string(): void
    {
        $this->assertNull(CountryCodeNormalizer::normalizeCountryCode(''));
        $this->assertNull(CountryCodeNormalizer::normalizeCountryCode('   '));
    }

    /**
     * Тест обработки неактивной страны
     */
    public function test_returns_null_for_inactive_country(): void
    {
        $countryId = CountryCodeNormalizer::normalizeCountryCode('XX'); // Неактивная страна из сидера

        $this->assertNull($countryId);
    }

    /**
     * Тест пакетной нормализации
     */
    public function test_normalizes_batch_country_codes(): void
    {
        $codes = ['US', 'CA', 'GB', 'INVALID', ''];
        $result = CountryCodeNormalizer::normalizeBatch($codes);

        $this->assertIsArray($result);
        $this->assertCount(5, $result);

        $this->assertNotNull($result['US']);
        $this->assertNotNull($result['CA']);
        $this->assertNotNull($result['GB']);
        $this->assertNull($result['INVALID']);
        $this->assertNull($result['']);
    }

    /**
     * Тест валидации кода страны
     */
    public function test_validates_country_code(): void
    {
        $this->assertTrue(CountryCodeNormalizer::isValidCountryCode('US'));
        $this->assertTrue(CountryCodeNormalizer::isValidCountryCode('usa'));
        $this->assertFalse(CountryCodeNormalizer::isValidCountryCode('INVALID'));
        $this->assertFalse(CountryCodeNormalizer::isValidCountryCode(''));
    }

    /**
     * Тест получения информации о стране
     */
    public function test_gets_country_info(): void
    {
        $info = CountryCodeNormalizer::getCountryInfo('US');

        $this->assertIsArray($info);
        $this->assertArrayHasKey('id', $info);
        $this->assertArrayHasKey('iso_code_2', $info);
        $this->assertArrayHasKey('iso_code_3', $info);
        $this->assertArrayHasKey('name', $info);
        $this->assertArrayHasKey('numeric_code', $info);

        $this->assertEquals('US', $info['iso_code_2']);
        $this->assertEquals('USA', $info['iso_code_3']);
        $this->assertEquals('United States', $info['name']);
    }

    /**
     * Тест кеширования
     */
    public function test_caches_country_map(): void
    {
        // Первый вызов должен загрузить данные из БД
        $countryId1 = CountryCodeNormalizer::normalizeCountryCode('US');

        // Проверяем, что данные закешированы
        $this->assertTrue(Cache::has('country_normalizer.iso_to_id_map'));

        // Второй вызов должен использовать кеш
        $countryId2 = CountryCodeNormalizer::normalizeCountryCode('US');

        $this->assertEquals($countryId1, $countryId2);
    }

    /**
     * Тест очистки кеша
     */
    public function test_clears_cache(): void
    {
        // Загружаем данные в кеш
        CountryCodeNormalizer::normalizeCountryCode('US');
        $this->assertTrue(Cache::has('country_normalizer.iso_to_id_map'));

        // Очищаем кеш
        CountryCodeNormalizer::clearCache();
        $this->assertFalse(Cache::has('country_normalizer.iso_to_id_map'));
    }

    /**
     * Тест получения статистики
     */
    public function test_gets_stats(): void
    {
        $stats = CountryCodeNormalizer::getStats();

        $this->assertIsArray($stats);
        $this->assertArrayHasKey('total_mappings', $stats);
        $this->assertArrayHasKey('iso2_codes', $stats);
        $this->assertArrayHasKey('iso3_codes', $stats);
        $this->assertArrayHasKey('cache_initialized', $stats);

        $this->assertGreaterThan(0, $stats['total_mappings']);
        $this->assertTrue($stats['cache_initialized']);
    }

    /**
     * Тест получения доступных кодов
     */
    public function test_gets_available_codes(): void
    {
        $codes = CountryCodeNormalizer::getAvailableCodes();

        $this->assertIsArray($codes);
        $this->assertContains('US', $codes);
        $this->assertContains('USA', $codes);
        $this->assertContains('CA', $codes);
        $this->assertNotContains('XX', $codes); // Неактивная страна
    }

    /**
     * Тест обработки неизвестного кода
     */
    public function test_handles_unknown_code(): void
    {
        $result = CountryCodeNormalizer::handleUnknownCode('UNKNOWN');

        $this->assertNull($result);
    }
}

<?php

namespace Tests\Unit\DTOs;

use App\Http\DTOs\CreativesFiltersDTO;
use Tests\TestCase;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Contracts\Support\Jsonable;
use App\Models\Frontend\IsoEntity;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;

class CreativesFiltersDTOTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Очищаем кеш перед каждым тестом
        Cache::flush();

        // Создаем основные страны для тестов
        $basicCountries = [
            ['iso_code_2' => 'US', 'iso_code_3' => 'USA', 'numeric_code' => '840', 'name' => 'United States'],
            ['iso_code_2' => 'GB', 'iso_code_3' => 'GBR', 'numeric_code' => '826', 'name' => 'United Kingdom'],
            ['iso_code_2' => 'DE', 'iso_code_3' => 'DEU', 'numeric_code' => '276', 'name' => 'Germany'],
            ['iso_code_2' => 'FR', 'iso_code_3' => 'FRA', 'numeric_code' => '250', 'name' => 'France'],
            ['iso_code_2' => 'CA', 'iso_code_3' => 'CAN', 'numeric_code' => '124', 'name' => 'Canada'],
            ['iso_code_2' => 'AU', 'iso_code_3' => 'AUS', 'numeric_code' => '036', 'name' => 'Australia'],
            ['iso_code_2' => 'RU', 'iso_code_3' => 'RUS', 'numeric_code' => '643', 'name' => 'Russia'],
            ['iso_code_2' => 'UA', 'iso_code_3' => 'UKR', 'numeric_code' => '804', 'name' => 'Ukraine'],
        ];

        foreach ($basicCountries as $country) {
            IsoEntity::create([
                'type' => 'country',
                'iso_code_2' => $country['iso_code_2'],
                'iso_code_3' => $country['iso_code_3'],
                'numeric_code' => $country['numeric_code'],
                'name' => $country['name'],
                'is_active' => true,
            ]);
        }
    }

    public function test_dto_can_be_created_with_defaults()
    {
        $dto = new CreativesFiltersDTO();

        $this->assertEquals('', $dto->searchKeyword);
        $this->assertEquals('default', $dto->country);
        $this->assertEquals('default', $dto->dateCreation);
        $this->assertEquals('default', $dto->sortBy);
        $this->assertEquals('default', $dto->periodDisplay);
        $this->assertFalse($dto->onlyAdult);
        $this->assertFalse($dto->isDetailedVisible);
        $this->assertEquals(1, $dto->page);
        $this->assertEquals(12, $dto->perPage);
        $this->assertEquals('push', $dto->activeTab);
        $this->assertEquals([], $dto->advertisingNetworks);
        $this->assertEquals([], $dto->languages);
    }

    public function test_dto_can_be_created_from_array()
    {
        $data = [
            'searchKeyword' => 'test search',
            'country' => 'US',
            'dateCreation' => 'last7',
            'sortBy' => 'byCreationDate',
            'periodDisplay' => 'last30',
            'onlyAdult' => true,
            'isDetailedVisible' => true,
            'page' => 2,
            'perPage' => 24,
            'activeTab' => 'facebook',
            'advertisingNetworks' => ['facebook', 'google'],
            'languages' => ['en', 'ru'],
            'operatingSystems' => ['windows', 'android'],
            'browsers' => ['chrome'],
            'devices' => ['desktop'],
            'imageSizes' => ['1x1', '16x9'],
        ];

        $dto = CreativesFiltersDTO::fromArray($data);

        $this->assertEquals('test search', $dto->searchKeyword);
        $this->assertEquals('US', $dto->country);
        $this->assertEquals('last7', $dto->dateCreation);
        $this->assertEquals('byCreationDate', $dto->sortBy);
        $this->assertEquals('last30', $dto->periodDisplay);
        $this->assertTrue($dto->onlyAdult);
        $this->assertTrue($dto->isDetailedVisible);
        $this->assertEquals(2, $dto->page);
        $this->assertEquals(24, $dto->perPage);
        $this->assertEquals('facebook', $dto->activeTab);
        $this->assertEquals(['facebook', 'google'], $dto->advertisingNetworks);
        $this->assertEquals(['en', 'ru'], $dto->languages);
    }

    public function test_dto_validates_required_fields()
    {
        $invalidData = [
            'searchKeyword' => str_repeat('a', 300), // Слишком длинная строка
            'country' => 'INVALID_COUNTRY',
            'page' => 0, // Меньше минимума
            'perPage' => 200, // Больше максимума
            'activeTab' => 'invalid_tab',
        ];

        $errors = CreativesFiltersDTO::validate($invalidData);

        $this->assertNotEmpty($errors);
        $this->assertContains('searchKeyword must be less than 255 characters', $errors);
        $this->assertContains('Invalid country: INVALID_COUNTRY', $errors);
        $this->assertContains('page must be between 1 and 10000', $errors);
        $this->assertContains('perPage must be between 6 and 100', $errors);
        $this->assertContains('Invalid activeTab value', $errors);
    }

    public function test_dto_validates_boolean_fields()
    {
        $invalidData = [
            'onlyAdult' => 'invalid_boolean',
            'isDetailedVisible' => 'not_a_boolean',
        ];

        $errors = CreativesFiltersDTO::validate($invalidData);

        $this->assertNotEmpty($errors);
        $this->assertContains('onlyAdult must be boolean', $errors);
        $this->assertContains('isDetailedVisible must be boolean', $errors);
    }

    public function test_dto_validates_array_fields()
    {
        $invalidData = [
            'advertisingNetworks' => 'not_an_array',
            'languages' => 'not_an_array',
            'operatingSystems' => 123,
        ];

        $errors = CreativesFiltersDTO::validate($invalidData);

        $this->assertNotEmpty($errors);
        $this->assertContains('advertisingNetworks must be an array', $errors);
        $this->assertContains('languages must be an array', $errors);
        $this->assertContains('operatingSystems must be an array', $errors);
    }

    public function test_dto_sanitizes_string_values()
    {
        $data = [
            'searchKeyword' => '  <script>test</script>  ',
            'country' => 'US',
        ];

        $dto = CreativesFiltersDTO::fromArraySafe($data);

        $this->assertEquals('test', $dto->searchKeyword); // Убрал теги и пробелы
    }

    public function test_dto_sanitizes_boolean_values()
    {
        $testCases = [
            ['onlyAdult' => 'true', 'expected' => true],
            ['onlyAdult' => 'false', 'expected' => false],
            ['onlyAdult' => '1', 'expected' => true],
            ['onlyAdult' => '0', 'expected' => false],
            ['onlyAdult' => 1, 'expected' => true],
            ['onlyAdult' => 0, 'expected' => false],
            ['onlyAdult' => 'yes', 'expected' => true],
            ['onlyAdult' => 'no', 'expected' => false],
        ];

        foreach ($testCases as $testCase) {
            $dto = CreativesFiltersDTO::fromArraySafe($testCase);
            $this->assertEquals(
                $testCase['expected'],
                $dto->onlyAdult,
                "Failed for input: " . json_encode($testCase['onlyAdult'])
            );
        }
    }

    public function test_dto_sanitizes_array_values()
    {
        $data = [
            'advertisingNetworks' => ['facebook', '', 'google', null, 'tiktok'],
            'languages' => ['en', '', 'ru', 123], // Содержит пустые и не-строковые значения
        ];

        $dto = CreativesFiltersDTO::fromArraySafe($data);

        $this->assertEquals(['facebook', 'google', 'tiktok'], $dto->advertisingNetworks);
        $this->assertEquals(['en', 'ru'], $dto->languages);
    }

    public function test_dto_handles_invalid_values_safely()
    {
        $invalidData = [
            'country' => 'INVALID_COUNTRY',
            'dateCreation' => 'invalid_date',
            'sortBy' => 'invalid_sort',
            'periodDisplay' => 'invalid_period',
            'page' => -5,
            'perPage' => 300,
            'activeTab' => 'invalid_tab',
        ];

        $dto = CreativesFiltersDTO::fromArraySafe($invalidData);

        // Должны использоваться дефолтные значения
        $this->assertEquals('default', $dto->country);
        $this->assertEquals('default', $dto->dateCreation);
        $this->assertEquals('default', $dto->sortBy);
        $this->assertEquals('default', $dto->periodDisplay);
        $this->assertEquals(1, $dto->page); // Ближайшее валидное значение
        $this->assertEquals(96, $dto->perPage); // Ближайшее допустимое значение к 300
        $this->assertEquals('push', $dto->activeTab);
    }

    public function test_dto_validates_country_codes()
    {
        $validCountries = ['default', 'US', 'GB', 'DE', 'FR', 'CA', 'AU', 'RU', 'UA'];

        foreach ($validCountries as $country) {
            $dto = CreativesFiltersDTO::fromArraySafe(['country' => $country]);
            $this->assertEquals($country, $dto->country);
        }

        $dto = CreativesFiltersDTO::fromArraySafe(['country' => 'INVALID']);
        $this->assertEquals('default', $dto->country);
    }

    public function test_dto_validates_active_tabs()
    {
        $validTabs = ['push', 'inpage', 'facebook', 'tiktok'];

        foreach ($validTabs as $tab) {
            $dto = CreativesFiltersDTO::fromArraySafe(['activeTab' => $tab]);
            $this->assertEquals($tab, $dto->activeTab);
        }

        $dto = CreativesFiltersDTO::fromArraySafe(['activeTab' => 'invalid']);
        $this->assertEquals('push', $dto->activeTab);
    }

    public function test_dto_validates_per_page_values()
    {
        $validPerPage = [6, 12, 24, 48, 96];

        foreach ($validPerPage as $perPage) {
            $dto = CreativesFiltersDTO::fromArraySafe(['perPage' => $perPage]);
            $this->assertEquals($perPage, $dto->perPage);
        }

        // Тестируем поиск ближайшего значения
        $testCases = [
            [5, 6],     // Ближайшее к 5 это 6
            [10, 12],   // Ближайшее к 10 это 12
            [30, 24],   // Ближайшее к 30 это 24
            [60, 48],   // Ближайшее к 60 это 48
            [100, 96],  // Ближайшее к 100 это 96
        ];

        foreach ($testCases as [$input, $expected]) {
            $dto = CreativesFiltersDTO::fromArraySafe(['perPage' => $input]);
            $this->assertEquals(
                $expected,
                $dto->perPage,
                "Failed for input {$input}, expected {$expected}"
            );
        }
    }

    public function test_dto_converts_to_array_correctly()
    {
        $data = [
            'searchKeyword' => 'test',
            'country' => 'US',
            'onlyAdult' => true,
            'page' => 2,
            'advertisingNetworks' => ['facebook'],
        ];

        $dto = CreativesFiltersDTO::fromArraySafe($data);
        $result = $dto->toArray();

        $this->assertEquals('test', $result['searchKeyword']);
        $this->assertEquals('US', $result['country']);
        $this->assertTrue($result['onlyAdult']);
        $this->assertEquals(2, $result['page']);
        $this->assertEquals(['facebook'], $result['advertisingNetworks']);

        // Проверяем что все поля присутствуют
        $this->assertArrayHasKey('dateCreation', $result);
        $this->assertArrayHasKey('sortBy', $result);
        $this->assertArrayHasKey('languages', $result);
    }

    public function test_dto_detects_active_filters()
    {
        // Дефолтные значения - нет активных фильтров
        $dto = new CreativesFiltersDTO();
        $this->assertFalse($dto->hasActiveFilters());
        $this->assertEquals(0, $dto->getActiveFiltersCount());

        // С активными фильтрами
        $dto = CreativesFiltersDTO::fromArraySafe([
            'searchKeyword' => 'test',
            'country' => 'US',
            'onlyAdult' => true,
        ]);

        $this->assertTrue($dto->hasActiveFilters());
        $this->assertEquals(3, $dto->getActiveFiltersCount());

        // page, perPage, activeTab, isDetailedVisible не считаются активными фильтрами
        $dto = CreativesFiltersDTO::fromArraySafe([
            'page' => 2,
            'perPage' => 24,
            'activeTab' => 'facebook',
            'isDetailedVisible' => true,
        ]);

        $this->assertFalse($dto->hasActiveFilters());
        $this->assertEquals(0, $dto->getActiveFiltersCount());
    }

    public function test_dto_gets_active_filters()
    {
        $dto = CreativesFiltersDTO::fromArraySafe([
            'searchKeyword' => 'test',
            'country' => 'US',
            'onlyAdult' => true,
            'page' => 2, // Не активный фильтр
            'activeTab' => 'facebook', // Не активный фильтр
        ]);

        $activeFilters = $dto->getActiveFilters();

        $this->assertCount(3, $activeFilters);
        $this->assertEquals('test', $activeFilters['searchKeyword']);
        $this->assertEquals('US', $activeFilters['country']);
        $this->assertTrue($activeFilters['onlyAdult']);
        $this->assertArrayNotHasKey('page', $activeFilters);
        $this->assertArrayNotHasKey('activeTab', $activeFilters);
    }

    public function test_dto_can_reset_filters()
    {
        $dto = CreativesFiltersDTO::fromArraySafe([
            'searchKeyword' => 'test',
            'country' => 'US',
            'onlyAdult' => true,
            'page' => 2,
        ]);

        $this->assertTrue($dto->hasActiveFilters());

        $resetDto = $dto->reset();

        $this->assertFalse($resetDto->hasActiveFilters());
        $this->assertEquals('', $resetDto->searchKeyword);
        $this->assertEquals('default', $resetDto->country);
        $this->assertFalse($resetDto->onlyAdult);
        $this->assertEquals(1, $resetDto->page);
    }

    public function test_dto_generates_cache_key()
    {
        $dto1 = CreativesFiltersDTO::fromArraySafe(['searchKeyword' => 'test']);
        $dto2 = CreativesFiltersDTO::fromArraySafe(['searchKeyword' => 'test']);
        $dto3 = CreativesFiltersDTO::fromArraySafe(['searchKeyword' => 'different']);

        $this->assertEquals($dto1->getCacheKey(), $dto2->getCacheKey());
        $this->assertNotEquals($dto1->getCacheKey(), $dto3->getCacheKey());
        $this->assertEquals(32, strlen($dto1->getCacheKey())); // MD5 длина
    }

    public function test_dto_pagination_methods()
    {
        $dto = CreativesFiltersDTO::fromArraySafe([
            'page' => 3,
            'perPage' => 12,
        ]);

        $totalCount = 100;

        $this->assertTrue($dto->needsPagination($totalCount));
        $this->assertEquals(24, $dto->getOffset()); // (3-1) * 12
        $this->assertEquals(25, $dto->getFromNumber($totalCount)); // 24 + 1
        $this->assertEquals(36, $dto->getToNumber($totalCount)); // min(3 * 12, 100)
        $this->assertEquals(9, $dto->getLastPage($totalCount)); // ceil(100 / 12)

        // Тест с пустым результатом
        $this->assertEquals(0, $dto->getFromNumber(0));
        $this->assertEquals(0, $dto->getToNumber(0));
        $this->assertEquals(1, $dto->getLastPage(0));
    }

    public function test_dto_compact_array_removes_empty_arrays()
    {
        $dto = CreativesFiltersDTO::fromArraySafe([
            'searchKeyword' => 'test',
            'advertisingNetworks' => [], // Пустой массив
            'languages' => ['en'], // Не пустой массив
        ]);

        $compact = $dto->toCompactArray();

        $this->assertArrayHasKey('searchKeyword', $compact);
        $this->assertArrayHasKey('languages', $compact);
        $this->assertArrayNotHasKey('advertisingNetworks', $compact); // Удален пустой массив
        $this->assertArrayNotHasKey('operatingSystems', $compact); // Удален пустой массив
    }

    public function test_dto_implements_contracts()
    {
        $dto = new CreativesFiltersDTO();

        $this->assertInstanceOf(Arrayable::class, $dto);
        $this->assertInstanceOf(Jsonable::class, $dto);

        $json = $dto->toJson();
        $this->assertIsString($json);
        $this->assertJson($json);

        $decoded = json_decode($json, true);
        $this->assertIsArray($decoded);
        $this->assertArrayHasKey('searchKeyword', $decoded);
    }

    public function test_dto_handles_edge_cases()
    {
        // Тест с null значениями
        $dto = CreativesFiltersDTO::fromArraySafe([
            'searchKeyword' => null,
            'advertisingNetworks' => null,
            'onlyAdult' => null,
        ]);

        $this->assertEquals('', $dto->searchKeyword);
        $this->assertEquals([], $dto->advertisingNetworks);
        $this->assertFalse($dto->onlyAdult);

        // Тест с очень большими значениями
        $dto = CreativesFiltersDTO::fromArraySafe([
            'page' => 999999,
            'perPage' => 999999,
        ]);

        $this->assertEquals(10000, $dto->page); // Максимум
        $this->assertEquals(96, $dto->perPage); // Ближайшее допустимое
    }

    public function test_dto_strict_validation_throws_exceptions()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Validation failed');

        CreativesFiltersDTO::fromArray([
            'country' => 'INVALID_COUNTRY',
        ]);
    }

    public function test_dto_get_defaults_returns_correct_structure()
    {
        $defaults = CreativesFiltersDTO::getDefaults();

        $this->assertIsArray($defaults);
        $this->assertArrayHasKey('searchKeyword', $defaults);
        $this->assertArrayHasKey('country', $defaults);
        $this->assertArrayHasKey('page', $defaults);
        $this->assertArrayHasKey('advertisingNetworks', $defaults);

        $this->assertEquals('', $defaults['searchKeyword']);
        $this->assertEquals('default', $defaults['country']);
        $this->assertEquals(1, $defaults['page']);
        $this->assertEquals(12, $defaults['perPage']);
        $this->assertEquals([], $defaults['advertisingNetworks']);
    }

    /** @test */
    public function it_validates_countries_from_database()
    {
        // Создаем тестовую страну в базе данных
        $testCountry = IsoEntity::create([
            'type' => 'country',
            'iso_code_2' => 'TE',
            'iso_code_3' => 'TES',
            'numeric_code' => '999',
            'name' => 'Test Country',
            'is_active' => true,
        ]);

        // Проверяем, что специальное значение 'default' валидно
        $dto = CreativesFiltersDTO::fromArraySafe(['country' => 'default']);
        $this->assertEquals('default', $dto->country);

        // Проверяем, что созданная в базе страна валидна
        $dto = CreativesFiltersDTO::fromArraySafe(['country' => 'TE']);
        $this->assertEquals('TE', $dto->country);

        // Проверяем, что несуществующая страна возвращается к default в safe режиме
        $dto = CreativesFiltersDTO::fromArraySafe(['country' => 'XX']);
        $this->assertEquals('default', $dto->country);
    }

    /** @test */
    public function it_throws_exception_for_invalid_country_in_strict_mode()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid country: XX');

        CreativesFiltersDTO::fromArray(['country' => 'XX']);
    }

    /** @test */
    public function it_caches_valid_countries()
    {
        // Создаем тестовую страну
        IsoEntity::create([
            'type' => 'country',
            'iso_code_2' => 'TE',
            'iso_code_3' => 'TES',
            'numeric_code' => '999',
            'name' => 'Test Country',
            'is_active' => true,
        ]);

        // Первый вызов должен создать кеш
        $dto1 = CreativesFiltersDTO::fromArraySafe(['country' => 'TE']);
        $this->assertEquals('TE', $dto1->country);

        // Проверяем, что кеш создался
        $this->assertTrue(Cache::has('creatives_filters.valid_countries'));

        // Второй вызов должен использовать кеш
        $dto2 = CreativesFiltersDTO::fromArraySafe(['country' => 'TE']);
        $this->assertEquals('TE', $dto2->country);
    }

    /** @test */
    public function it_clears_cache_when_iso_entity_changes()
    {
        // Создаем страну и проверяем кеширование
        $country = IsoEntity::create([
            'type' => 'country',
            'iso_code_2' => 'TE',
            'iso_code_3' => 'TES',
            'numeric_code' => '999',
            'name' => 'Test Country',
            'is_active' => true,
        ]);

        // Создаем кеш
        CreativesFiltersDTO::fromArraySafe(['country' => 'TE']);
        $this->assertTrue(Cache::has('creatives_filters.valid_countries'));

        // Обновляем страну - это должно очистить кеш
        $country->update(['name' => 'Updated Test Country']);
        $this->assertFalse(Cache::has('creatives_filters.valid_countries'));
    }

    /** @test */
    public function it_ignores_inactive_countries()
    {
        // Создаем неактивную страну
        IsoEntity::create([
            'type' => 'country',
            'iso_code_2' => 'IN',
            'iso_code_3' => 'INA',
            'numeric_code' => '998',
            'name' => 'Inactive Country',
            'is_active' => false,
        ]);

        // Неактивная страна не должна быть валидной
        $dto = CreativesFiltersDTO::fromArraySafe(['country' => 'IN']);
        $this->assertEquals('default', $dto->country);
    }

    /** @test */
    public function it_handles_case_insensitive_country_codes()
    {
        // Создаем тестовую страну
        IsoEntity::create([
            'type' => 'country',
            'iso_code_2' => 'TE',
            'iso_code_3' => 'TES',
            'numeric_code' => '999',
            'name' => 'Test Country',
            'is_active' => true,
        ]);

        // Проверяем, что нижний регистр работает
        $dto = CreativesFiltersDTO::fromArraySafe(['country' => 'te']);
        $this->assertEquals('te', $dto->country);

        // Проверяем, что смешанный регистр работает
        $dto = CreativesFiltersDTO::fromArraySafe(['country' => 'Te']);
        $this->assertEquals('Te', $dto->country);
    }
}

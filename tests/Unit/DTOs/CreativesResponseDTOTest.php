<?php

namespace Tests\Unit\DTOs;

use App\Http\DTOs\CreativesResponseDTO;
use App\Http\DTOs\CreativesFiltersDTO;
use App\Http\DTOs\PaginationDTO;
use Tests\TestCase;
use App\Models\Frontend\IsoEntity;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;

class CreativesResponseDTOTest extends TestCase
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

        // Очищаем кеш стран после создания тестовых данных
        CreativesFiltersDTO::clearCountriesCache();
    }

    public function test_can_create_successful_response()
    {
        $items = [
            ['id' => 1, 'name' => 'Creative 1'],
            ['id' => 2, 'name' => 'Creative 2'],
        ];

        $filtersDTO = CreativesFiltersDTO::fromArraySafe([
            'searchKeyword' => 'test',
            'page' => 1,
            'perPage' => 12,
        ]);

        $response = CreativesResponseDTO::success($items, $filtersDTO, 25);

        $this->assertEquals('success', $response->status);
        $this->assertEquals($items, $response->items);
        $this->assertTrue($response->hasSearch);
        $this->assertEquals(1, $response->activeFiltersCount);
        $this->assertTrue($response->hasActiveFilters);
        $this->assertEquals(25, $response->pagination->total);
        $this->assertFalse($response->hasError());
        $this->assertTrue($response->isSuccess());
    }

    public function test_can_create_error_response()
    {
        $error = 'Database connection failed';
        $filters = ['searchKeyword' => 'test'];

        $response = CreativesResponseDTO::error($error, $filters);

        $this->assertEquals('error', $response->status);
        $this->assertEquals($error, $response->error);
        $this->assertEquals($filters, $response->appliedFilters);
        $this->assertEmpty($response->items);
        $this->assertTrue($response->hasError());
        $this->assertFalse($response->isSuccess());
    }

    public function test_can_create_loading_response()
    {
        $filters = ['countries' => ['US']];

        $response = CreativesResponseDTO::loading($filters);

        $this->assertEquals('loading', $response->status);
        $this->assertTrue($response->isLoading);
        $this->assertEquals($filters, $response->appliedFilters);
        $this->assertEmpty($response->items);
        $this->assertFalse($response->hasError());
    }

    public function test_can_create_empty_response()
    {
        $filtersDTO = CreativesFiltersDTO::fromArraySafe([
            'searchKeyword' => 'nonexistent',
            'countries' => ['US'],
        ]);

        $response = CreativesResponseDTO::empty($filtersDTO);

        $this->assertEquals('empty', $response->status);
        $this->assertTrue($response->hasSearch);
        $this->assertEquals(2, $response->activeFiltersCount);
        $this->assertTrue($response->hasActiveFilters);
        $this->assertEmpty($response->items);
        $this->assertTrue($response->isEmpty());
        $this->assertEquals(0, $response->pagination->total);
    }

    public function test_can_create_from_controller_data()
    {
        $items = [
            ['id' => 1, 'name' => 'Test Creative'],
        ];

        $filtersDTO = CreativesFiltersDTO::fromArraySafe([
            'searchKeyword' => 'test',
            'countries' => ['US'],
            'page' => 2,
            'perPage' => 24,
        ]);

        $response = CreativesResponseDTO::fromControllerData($items, $filtersDTO, 100);

        $this->assertEquals($items, $response->items);
        $this->assertTrue($response->hasSearch);
        $this->assertEquals(2, $response->activeFiltersCount);
        $this->assertTrue($response->hasActiveFilters);
        $this->assertEquals(100, $response->pagination->total);
        $this->assertEquals(2, $response->pagination->currentPage);
        $this->assertEquals(24, $response->pagination->perPage);
        $this->assertNotEmpty($response->cacheKey);
    }

    public function test_fluent_interface_methods()
    {
        $response = new CreativesResponseDTO();

        $filterOptions = ['countries' => ['US', 'GB']];
        $availableFilters = ['country', 'category'];
        $meta = ['testKey' => 'testValue'];

        $response
            ->withFilterOptions($filterOptions)
            ->withAvailableFilters($availableFilters)
            ->withStatus('success')
            ->withLoading(false)
            ->withMeta($meta);

        $this->assertEquals($filterOptions, $response->filterOptions);
        $this->assertEquals($availableFilters, $response->availableFilters);
        $this->assertEquals('success', $response->status);
        $this->assertFalse($response->isLoading);
    }

    public function test_error_handling_methods()
    {
        $response = new CreativesResponseDTO();

        $response->withError('Test error');

        $this->assertEquals('error', $response->status);
        $this->assertEquals('Test error', $response->error);
        $this->assertTrue($response->hasError());
        $this->assertFalse($response->isSuccess());
    }

    public function test_get_stats_method()
    {
        $items = [
            ['id' => 1],
            ['id' => 2],
            ['id' => 3]
        ];

        $filtersDTO = CreativesFiltersDTO::fromArraySafe([
            'searchKeyword' => 'test',
            'countries' => ['US'],
        ]);

        $response = CreativesResponseDTO::success($items, $filtersDTO, 50);

        $stats = $response->getStats();

        $this->assertEquals('success', $stats['status']);
        $this->assertEquals(3, $stats['itemsCount']);
        $this->assertEquals(50, $stats['totalCount']);
        $this->assertEquals(1, $stats['currentPage']);
        $this->assertTrue($stats['hasFilters']);
        $this->assertEquals(2, $stats['filtersCount']);
        $this->assertTrue($stats['hasSearch']);
        $this->assertNotEmpty($stats['cacheKey']);
    }

    public function test_to_api_response_structure()
    {
        $items = [['id' => 1, 'name' => 'Test']];
        $filtersDTO = CreativesFiltersDTO::fromArraySafe(['searchKeyword' => 'test']);
        $response = CreativesResponseDTO::success($items, $filtersDTO, 25);

        $apiResponse = $response->toApiResponse();

        // Проверяем основную структуру
        $this->assertArrayHasKey('status', $apiResponse);
        $this->assertArrayHasKey('data', $apiResponse);

        // Проверяем структуру data
        $this->assertArrayHasKey('items', $apiResponse['data']);
        $this->assertArrayHasKey('pagination', $apiResponse['data']);
        $this->assertArrayHasKey('meta', $apiResponse['data']);

        // Проверяем структуру meta
        $meta = $apiResponse['data']['meta'];
        $this->assertArrayHasKey('hasSearch', $meta);
        $this->assertArrayHasKey('activeFiltersCount', $meta);
        $this->assertArrayHasKey('hasActiveFilters', $meta);
        $this->assertArrayHasKey('cacheKey', $meta);
        $this->assertArrayHasKey('timestamp', $meta);

        $this->assertEquals('success', $apiResponse['status']);
        $this->assertEquals($items, $apiResponse['data']['items']);
        $this->assertTrue($meta['hasSearch']);
    }

    public function test_to_api_response_with_error()
    {
        $response = CreativesResponseDTO::error('Test error');

        $apiResponse = $response->toApiResponse();

        $this->assertArrayHasKey('error', $apiResponse);
        $this->assertEquals('Test error', $apiResponse['error']);
        $this->assertEquals('error', $apiResponse['status']);
    }

    public function test_to_api_response_with_loading()
    {
        $response = CreativesResponseDTO::loading(['test' => 'value']);

        $apiResponse = $response->toApiResponse();

        $this->assertTrue($apiResponse['data']['meta']['isLoading']);
        $this->assertEquals('loading', $apiResponse['status']);
    }

    public function test_to_api_response_with_filter_options()
    {
        $filterOptions = ['countries' => ['US', 'GB']];
        $availableFilters = ['country', 'category'];

        $response = (new CreativesResponseDTO())
            ->withFilterOptions($filterOptions)
            ->withAvailableFilters($availableFilters);

        $apiResponse = $response->toApiResponse();

        $this->assertArrayHasKey('filterOptions', $apiResponse['data']);
        $this->assertArrayHasKey('availableFilters', $apiResponse['data']);
        $this->assertEquals($filterOptions, $apiResponse['data']['filterOptions']);
        $this->assertEquals($availableFilters, $apiResponse['data']['availableFilters']);
    }

    public function test_to_compact_array()
    {
        $items = [['id' => 1]];
        $filtersDTO = CreativesFiltersDTO::fromArraySafe(['searchKeyword' => 'test']);
        $response = CreativesResponseDTO::success($items, $filtersDTO, 25);

        $compact = $response->toCompactArray();

        // Проверяем что есть только основные поля
        $this->assertArrayHasKey('status', $compact);
        $this->assertArrayHasKey('items', $compact);
        $this->assertArrayHasKey('pagination', $compact);
        $this->assertArrayHasKey('meta', $compact);

        // Проверяем что meta содержит только основные поля
        $meta = $compact['meta'];
        $this->assertArrayHasKey('hasSearch', $meta);
        $this->assertArrayHasKey('hasFilters', $meta);
        $this->assertArrayHasKey('timestamp', $meta);

        // Проверяем что нет лишних полей в meta
        $this->assertArrayNotHasKey('appliedFilters', $meta);
        $this->assertArrayNotHasKey('activeFilters', $meta);
    }

    public function test_validation()
    {
        // Валидные данные
        $validData = [
            'items' => [['id' => 1]],
            'hasSearch' => true,
            'activeFiltersCount' => 5,
            'status' => 'success',
            'appliedFilters' => ['test' => 'value'],
        ];

        $errors = CreativesResponseDTO::validate($validData);
        $this->assertEmpty($errors);

        // Невалидные данные
        $invalidData = [
            'items' => 'not-an-array',
            'hasSearch' => 'not-a-boolean',
            'activeFiltersCount' => -1,
            'status' => 'invalid-status',
            'appliedFilters' => 'not-an-array',
        ];

        $errors = CreativesResponseDTO::validate($invalidData);
        $this->assertNotEmpty($errors);
        $this->assertContains('items must be an array', $errors);
        $this->assertContains('hasSearch must be boolean', $errors);
        $this->assertContains('activeFiltersCount must be non-negative integer', $errors);
        $this->assertContains('status must be one of: success, error, loading, empty', $errors);
        $this->assertContains('appliedFilters must be an array', $errors);
    }

    public function test_from_array_with_validation()
    {
        $validData = [
            'items' => [['id' => 1]],
            'hasSearch' => true,
            'activeFiltersCount' => 2,
            'status' => 'success',
        ];

        $dto = CreativesResponseDTO::fromArray($validData);

        $this->assertEquals($validData['items'], $dto->items);
        $this->assertEquals($validData['hasSearch'], $dto->hasSearch);
        $this->assertEquals($validData['activeFiltersCount'], $dto->activeFiltersCount);
        $this->assertEquals($validData['status'], $dto->status);

        // Проверяем дефолтные значения
        $this->assertInstanceOf(PaginationDTO::class, $dto->pagination);
        $this->assertFalse($dto->hasActiveFilters);
        $this->assertEquals('', $dto->cacheKey);
    }

    public function test_from_array_with_validation_failure()
    {
        $invalidData = [
            'items' => 'not-an-array',
            'status' => 'invalid-status',
        ];

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Validation failed:');

        CreativesResponseDTO::fromArray($invalidData);
    }

    public function test_utility_methods()
    {
        // Тест isEmpty
        $emptyResponse = new CreativesResponseDTO();
        $this->assertTrue($emptyResponse->isEmpty());

        $nonEmptyResponse = new CreativesResponseDTO(items: [['id' => 1]]);
        $this->assertFalse($nonEmptyResponse->isEmpty());

        // Тест getItemsCount
        $this->assertEquals(0, $emptyResponse->getItemsCount());
        $this->assertEquals(1, $nonEmptyResponse->getItemsCount());

        // Тест с error статусом
        $errorResponse = CreativesResponseDTO::error('Test error');
        $this->assertFalse($errorResponse->isEmpty()); // error не считается empty

        // Тест с loading статусом
        $loadingResponse = CreativesResponseDTO::loading();
        $this->assertFalse($loadingResponse->isEmpty()); // loading не считается empty
    }

    public function test_to_json()
    {
        $items = [['id' => 1, 'name' => 'Test']];
        $filtersDTO = CreativesFiltersDTO::fromArraySafe([]);
        $response = CreativesResponseDTO::success($items, $filtersDTO, 1);

        $json = $response->toJson();

        $this->assertIsString($json);
        $this->assertJson($json);

        $decoded = json_decode($json, true);
        $this->assertArrayHasKey('status', $decoded);
        $this->assertArrayHasKey('data', $decoded);
        $this->assertEquals('success', $decoded['status']);
    }

    public function test_timestamp_auto_generation()
    {
        $response = new CreativesResponseDTO();

        $this->assertNotNull($response->timestamp);
        $this->assertIsString($response->timestamp);

        // Проверяем что это валидная ISO строка
        $date = \DateTime::createFromFormat('Y-m-d\TH:i:s.u\Z', $response->timestamp);
        if (!$date) {
            // Пробуем альтернативный формат
            $date = \DateTime::createFromFormat(\DateTime::ATOM, $response->timestamp);
        }
        $this->assertNotFalse($date, 'Timestamp should be in valid ISO format');
    }

    public function test_custom_timestamp()
    {
        $customTimestamp = '2024-01-15T10:30:00Z';
        $response = new CreativesResponseDTO(timestamp: $customTimestamp);

        $this->assertEquals($customTimestamp, $response->timestamp);
    }
}

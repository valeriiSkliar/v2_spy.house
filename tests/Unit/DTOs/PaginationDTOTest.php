<?php

namespace Tests\Unit\DTOs;

use App\Http\DTOs\PaginationDTO;
use App\Http\DTOs\CreativesFiltersDTO;
use Tests\TestCase;

class PaginationDTOTest extends TestCase
{
    public function test_can_create_basic_pagination()
    {
        $pagination = new PaginationDTO(
            total: 100,
            perPage: 10,
            currentPage: 3
        );

        $this->assertEquals(100, $pagination->total);
        $this->assertEquals(10, $pagination->perPage);
        $this->assertEquals(3, $pagination->currentPage);
        $this->assertEquals(10, $pagination->lastPage); // ceil(100/10)
        $this->assertEquals(21, $pagination->from); // (3-1)*10 + 1
        $this->assertEquals(30, $pagination->to); // min(3*10, 100)
        $this->assertTrue($pagination->hasPages);
        $this->assertTrue($pagination->hasMorePages);
    }

    public function test_auto_calculation_of_derived_values()
    {
        $pagination = new PaginationDTO(total: 25, perPage: 10, currentPage: 1);

        $this->assertEquals(3, $pagination->lastPage); // ceil(25/10)
        $this->assertEquals(1, $pagination->from);
        $this->assertEquals(10, $pagination->to);
        $this->assertTrue($pagination->hasPages);
        $this->assertTrue($pagination->hasMorePages);

        // Последняя страница
        $pagination = new PaginationDTO(total: 25, perPage: 10, currentPage: 3);
        $this->assertEquals(21, $pagination->from);
        $this->assertEquals(25, $pagination->to); // min(3*10, 25)
        $this->assertFalse($pagination->hasMorePages);
    }

    public function test_empty_pagination()
    {
        $pagination = PaginationDTO::empty();

        $this->assertEquals(0, $pagination->total);
        $this->assertEquals(12, $pagination->perPage); // дефолтное значение
        $this->assertEquals(1, $pagination->currentPage);
        $this->assertEquals(1, $pagination->lastPage);
        $this->assertEquals(0, $pagination->from);
        $this->assertEquals(0, $pagination->to);
        $this->assertFalse($pagination->hasPages);
        $this->assertFalse($pagination->hasMorePages);
    }

    public function test_from_filters_and_total()
    {
        $filtersDTO = CreativesFiltersDTO::fromArraySafe([
            'page' => 2,
            'perPage' => 24,
        ]);

        $pagination = PaginationDTO::fromFiltersAndTotal($filtersDTO, 100);

        $this->assertEquals(100, $pagination->total);
        $this->assertEquals(24, $pagination->perPage);
        $this->assertEquals(2, $pagination->currentPage);
        $this->assertEquals(5, $pagination->lastPage); // ceil(100/24)
        $this->assertEquals(25, $pagination->from); // (2-1)*24 + 1
        $this->assertEquals(48, $pagination->to); // min(2*24, 100)
    }

    public function test_from_array()
    {
        $data = [
            'total' => 150,
            'perPage' => 20,
            'currentPage' => 4,
        ];

        $pagination = PaginationDTO::fromArray($data);

        $this->assertEquals(150, $pagination->total);
        $this->assertEquals(20, $pagination->perPage);
        $this->assertEquals(4, $pagination->currentPage);
        $this->assertEquals(8, $pagination->lastPage); // ceil(150/20)
    }

    public function test_correction_of_invalid_values()
    {
        // Негативные значения
        $pagination = new PaginationDTO(total: -10, perPage: -5, currentPage: -1);
        $this->assertEquals(0, $pagination->total);
        $this->assertEquals(1, $pagination->perPage); // минимум 1
        $this->assertEquals(1, $pagination->currentPage); // минимум 1

        // currentPage больше lastPage
        $pagination = new PaginationDTO(total: 50, perPage: 10, currentPage: 10);
        $this->assertEquals(5, $pagination->currentPage); // скорректировано до lastPage
        $this->assertEquals(5, $pagination->lastPage);
    }

    public function test_utility_methods()
    {
        $pagination = new PaginationDTO(total: 100, perPage: 10, currentPage: 5);

        // Методы проверки страниц
        $this->assertFalse($pagination->isFirstPage());
        $this->assertFalse($pagination->isLastPage());
        $this->assertTrue($pagination->hasData());
        $this->assertFalse($pagination->isEmpty());

        // Первая страница
        $firstPage = new PaginationDTO(total: 100, perPage: 10, currentPage: 1);
        $this->assertTrue($firstPage->isFirstPage());
        $this->assertFalse($firstPage->isLastPage());

        // Последняя страница
        $lastPage = new PaginationDTO(total: 100, perPage: 10, currentPage: 10);
        $this->assertFalse($lastPage->isFirstPage());
        $this->assertTrue($lastPage->isLastPage());

        // Пустые данные
        $empty = new PaginationDTO(total: 0);
        $this->assertFalse($empty->hasData());
        $this->assertTrue($empty->isEmpty());
    }

    public function test_showing_text()
    {
        // Нет результатов
        $empty = new PaginationDTO(total: 0);
        $this->assertEquals('Результатов не найдено', $empty->getShowingText());

        // Один результат
        $single = new PaginationDTO(total: 1, perPage: 10, currentPage: 1);
        $this->assertEquals('Показан 1 результат', $single->getShowingText());

        // Множество результатов
        $multiple = new PaginationDTO(total: 100, perPage: 10, currentPage: 3);
        $this->assertEquals('Показано 21-30 из 100 результатов', $multiple->getShowingText());

        // Последняя неполная страница
        $partial = new PaginationDTO(total: 25, perPage: 10, currentPage: 3);
        $this->assertEquals('Показано 21-25 из 25 результатов', $partial->getShowingText());
    }

    public function test_page_numbers_generation()
    {
        // Мало страниц (меньше лимита)
        $pagination = new PaginationDTO(total: 50, perPage: 10, currentPage: 3);
        $pageNumbers = $pagination->getPageNumbers(7);
        $this->assertEquals([1, 2, 3, 4, 5], $pageNumbers);

        // Много страниц - начало
        $pagination = new PaginationDTO(total: 200, perPage: 10, currentPage: 3);
        $pageNumbers = $pagination->getPageNumbers(5);
        $this->assertEquals([1, 2, 3, 4, 5], $pageNumbers);

        // Много страниц - середина
        $pagination = new PaginationDTO(total: 200, perPage: 10, currentPage: 10);
        $pageNumbers = $pagination->getPageNumbers(5);
        $this->assertEquals([8, 9, 10, 11, 12], $pageNumbers);

        // Много страниц - конец
        $pagination = new PaginationDTO(total: 200, perPage: 10, currentPage: 18);
        $pageNumbers = $pagination->getPageNumbers(5);
        $this->assertEquals([16, 17, 18, 19, 20], $pageNumbers);
    }

    public function test_component_props()
    {
        $pagination = new PaginationDTO(total: 100, perPage: 10, currentPage: 5);
        $props = $pagination->getComponentProps();

        $this->assertArrayHasKey('total', $props);
        $this->assertArrayHasKey('perPage', $props);
        $this->assertArrayHasKey('currentPage', $props);
        $this->assertArrayHasKey('lastPage', $props);
        $this->assertArrayHasKey('hasPages', $props);
        $this->assertArrayHasKey('hasMorePages', $props);
        $this->assertArrayHasKey('hasPrevPage', $props);
        $this->assertArrayHasKey('hasNextPage', $props);
        $this->assertArrayHasKey('pageNumbers', $props);
        $this->assertArrayHasKey('showingText', $props);
        $this->assertArrayHasKey('info', $props);

        $this->assertEquals(100, $props['total']);
        $this->assertTrue($props['hasPrevPage']);
        $this->assertTrue($props['hasNextPage']);
        $this->assertEquals('Показано 41-50 из 100 результатов', $props['showingText']);
    }

    public function test_with_urls()
    {
        $pagination = new PaginationDTO(total: 100, perPage: 10, currentPage: 5);
        $baseUrl = 'https://example.com/creatives';
        $params = ['search' => 'test', 'category' => 'push'];

        $pagination->withUrls($baseUrl, $params);

        $this->assertStringContainsString('page=4', $pagination->prevPageUrl);
        $this->assertStringContainsString('page=6', $pagination->nextPageUrl);
        $this->assertStringContainsString('search=test', $pagination->prevPageUrl);
        $this->assertStringContainsString('category=push', $pagination->nextPageUrl);

        // Первая страница - нет prevPageUrl
        $firstPage = new PaginationDTO(total: 100, perPage: 10, currentPage: 1);
        $firstPage->withUrls($baseUrl, $params);
        $this->assertNull($firstPage->prevPageUrl);
        $this->assertNotNull($firstPage->nextPageUrl);

        // Последняя страница - нет nextPageUrl
        $lastPage = new PaginationDTO(total: 100, perPage: 10, currentPage: 10);
        $lastPage->withUrls($baseUrl, $params);
        $this->assertNotNull($lastPage->prevPageUrl);
        $this->assertNull($lastPage->nextPageUrl);
    }

    public function test_compact_array()
    {
        $pagination = new PaginationDTO(total: 100, perPage: 10, currentPage: 5);
        $compact = $pagination->toCompactArray();

        $expectedKeys = ['total', 'perPage', 'currentPage', 'lastPage', 'hasMorePages'];
        foreach ($expectedKeys as $key) {
            $this->assertArrayHasKey($key, $compact);
        }

        // Проверяем что нет лишних ключей
        $this->assertArrayNotHasKey('from', $compact);
        $this->assertArrayNotHasKey('to', $compact);
        $this->assertArrayNotHasKey('nextPageUrl', $compact);
        $this->assertArrayNotHasKey('prevPageUrl', $compact);
    }

    public function test_validation()
    {
        // Валидные данные
        $validData = [
            'total' => 100,
            'perPage' => 10,
            'currentPage' => 5,
            'hasPages' => true,
            'nextPageUrl' => 'https://example.com?page=6',
        ];

        $errors = PaginationDTO::validate($validData);
        $this->assertEmpty($errors);

        // Невалидные данные
        $invalidData = [
            'total' => -10,
            'perPage' => 0,
            'currentPage' => 0,
            'hasPages' => 'not-boolean',
            'nextPageUrl' => 123,
        ];

        $errors = PaginationDTO::validate($invalidData);
        $this->assertNotEmpty($errors);
        $this->assertContains('total must be non-negative integer', $errors);
        $this->assertContains('perPage must be at least 1', $errors);
        $this->assertContains('currentPage must be at least 1', $errors);
        $this->assertContains('hasPages must be boolean', $errors);
        $this->assertContains('nextPageUrl must be string or null', $errors);
    }

    public function test_from_array_with_validation()
    {
        $validData = [
            'total' => 100,
            'perPage' => 10,
            'currentPage' => 5,
        ];

        $pagination = PaginationDTO::fromArrayWithValidation($validData);
        $this->assertEquals(100, $pagination->total);
        $this->assertEquals(10, $pagination->perPage);
        $this->assertEquals(5, $pagination->currentPage);

        // Невалидные данные должны вызвать исключение
        $invalidData = ['total' => -10];

        $this->expectException(\InvalidArgumentException::class);
        PaginationDTO::fromArrayWithValidation($invalidData);
    }

    public function test_clone_and_modifications()
    {
        $original = new PaginationDTO(total: 100, perPage: 10, currentPage: 5);
        
        // Клонирование с изменениями
        $modified = $original->clone(['currentPage' => 3]);
        $this->assertEquals(5, $original->currentPage); // оригинал не изменился
        $this->assertEquals(3, $modified->currentPage); // клон изменился

        // goToPage
        $newPage = $original->goToPage(8);
        $this->assertEquals(5, $original->currentPage);
        $this->assertEquals(8, $newPage->currentPage);

        // changePerPage
        $newPerPage = $original->changePerPage(20);
        $this->assertEquals(10, $original->perPage);
        $this->assertEquals(5, $original->currentPage);
        $this->assertEquals(20, $newPerPage->perPage);
        $this->assertEquals(1, $newPerPage->currentPage); // сброс на первую страницу
    }

    public function test_to_array_and_to_json()
    {
        $pagination = new PaginationDTO(total: 100, perPage: 10, currentPage: 5);
        
        $array = $pagination->toArray();
        $expectedKeys = [
            'total', 'perPage', 'currentPage', 'lastPage', 'from', 'to',
            'hasPages', 'hasMorePages', 'nextPageUrl', 'prevPageUrl'
        ];

        foreach ($expectedKeys as $key) {
            $this->assertArrayHasKey($key, $array);
        }

        // Тест JSON
        $json = $pagination->toJson();
        $this->assertIsString($json);
        $this->assertJson($json);

        $decoded = json_decode($json, true);
        $this->assertEquals($array, $decoded);
    }

    public function test_get_info()
    {
        $pagination = new PaginationDTO(total: 100, perPage: 10, currentPage: 5);
        $info = $pagination->getInfo();

        $this->assertArrayHasKey('showing', $info);
        $this->assertArrayHasKey('isFirstPage', $info);
        $this->assertArrayHasKey('isLastPage', $info);
        $this->assertArrayHasKey('hasData', $info);
        $this->assertArrayHasKey('isEmpty', $info);

        $this->assertEquals('Показано 41-50 из 100 результатов', $info['showing']);
        $this->assertFalse($info['isFirstPage']);
        $this->assertFalse($info['isLastPage']);
        $this->assertTrue($info['hasData']);
        $this->assertFalse($info['isEmpty']);
    }

    public function test_edge_cases()
    {
        // Один элемент
        $single = new PaginationDTO(total: 1, perPage: 10, currentPage: 1);
        $this->assertEquals(1, $single->from);
        $this->assertEquals(1, $single->to);
        $this->assertEquals(1, $single->lastPage);
        $this->assertFalse($single->hasPages);
        $this->assertFalse($single->hasMorePages);

        // Очень большие числа
        $large = new PaginationDTO(total: 1000000, perPage: 100, currentPage: 5000);
        $this->assertEquals(10000, $large->lastPage);
        $this->assertEquals(499901, $large->from);
        $this->assertEquals(500000, $large->to);

        // Ноль элементов с большой страницей
        $zero = new PaginationDTO(total: 0, perPage: 10, currentPage: 100);
        $this->assertEquals(1, $zero->currentPage); // скорректировано
        $this->assertEquals(0, $zero->from);
        $this->assertEquals(0, $zero->to);
    }
}
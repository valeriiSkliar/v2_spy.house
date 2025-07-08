<?php

namespace Tests\Feature;

use Tests\TestCase;
use Tests\Traits\DatabaseSeeding;
use Illuminate\Foundation\Testing\RefreshDatabase;

class CreativesValidationTest extends TestCase
{
    use RefreshDatabase, DatabaseSeeding;

    protected function setUp(): void
    {
        parent::setUp();

        // Создаем базовые тестовые данные один раз для всех тестов
        $this->seedIsoEntities();
    }

    /**
     * Тест валидации корректных фильтров.
     */
    public function test_validates_correct_filters()
    {
        $response = $this->withHeaders([
            'Accept' => 'application/json',
        ])->get('/api/creatives/filters/validate?' . http_build_query([
            'searchKeyword' => 'test search',
            'countries' => ['US'],
            'sortBy' => 'creation',
            'onlyAdult' => false,
            'page' => 1,
            'perPage' => 12,
            'activeTab' => 'push'
        ]));

        $response->assertStatus(200)
            ->assertJsonStructure([
                'status',
                'filters' => [
                    'searchKeyword',
                    'countries',
                    'sortBy',
                    'onlyAdult',
                    'page',
                    'perPage',
                    'activeTab'
                ],
                'validation' => [
                    'rejectedValues',
                    'sanitizedCount',
                    'originalCount',
                    'validatedCount'
                ]
            ]);
    }

    /**
     * Тест отсечения некорректных значений.
     */
    public function test_rejects_invalid_filter_values()
    {
        // Тест с невалидными данными должен возвращать 422
        $response = $this->withHeaders([
            'Accept' => 'application/json',
        ])->get('/api/creatives/filters/validate?' . http_build_query([
            'searchKeyword' => str_repeat('a', 300), // Слишком длинное
            'country' => 'INVALID_COUNTRY',
            'sortBy' => 'invalid_sort',
            'onlyAdult' => 'maybe', // Некорректное булево значение
            'page' => -5, // Отрицательная страница
            'perPage' => 500, // Слишком много элементов
            'activeTab' => 'invalid_tab',
            'maliciousScript' => '<script>alert("xss")</script>'
        ]));

        $response->assertStatus(422)
            ->assertJsonStructure([
                'status',
                'message',
                'errors'
            ]);

        $data = $response->json();
        $this->assertEquals('error', $data['status']);
        $this->assertArrayHasKey('errors', $data);
    }

    /**
     * Тест санитизации строковых значений.
     */
    public function test_sanitizes_string_inputs()
    {
        $response = $this->withHeaders([
            'Accept' => 'application/json',
        ])->get('/api/creatives/filters/validate?' . http_build_query([
            'searchKeyword' => '  <script>alert("xss")</script>  ',
            'country' => '  US  '
        ]));

        $response->assertStatus(200);
        $data = $response->json();

        // Проверяем что строки были санитизированы
        if (isset($data['filters']['searchKeyword'])) {
            $this->assertStringNotContainsString('<script>', $data['filters']['searchKeyword']);
        }

        if (isset($data['filters']['country'])) {
            $this->assertEquals('US', $data['filters']['country']);
        }
    }

    /**
     * Тест валидации массивов из URL параметров.
     */
    public function test_validates_comma_separated_arrays()
    {
        // Тест с невалидными языками должен возвращать ошибку валидации
        $response = $this->withHeaders([
            'Accept' => 'application/json',
        ])->get('/api/creatives/filters/validate?' . http_build_query([
            'cr_languages' => 'en,ru,fr,invalid_lang',
            'cr_devices' => 'desktop,mobile,invalid_device'
        ]));

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['cr_languages']);
    }

    /**
     * Тест приоритета URL параметров над обычными.
     */
    public function test_url_params_priority_over_regular_params()
    {
        $response = $this->withHeaders([
            'Accept' => 'application/json',
        ])->get('/api/creatives/filters/validate?' . http_build_query([
            'searchKeyword' => 'regular search',
            'cr_searchKeyword' => 'url search',
            'country' => 'US',
            'cr_country' => 'CA'
        ]));

        $response->assertStatus(200);
        $data = $response->json();

        // URL параметры должны иметь приоритет
        if (isset($data['filters']['searchKeyword'])) {
            $this->assertEquals('url search', $data['filters']['searchKeyword']);
        }

        if (isset($data['filters']['country'])) {
            $this->assertEquals('CA', $data['filters']['country']);
        }
    }

    /**
     * Тест обработки булевых значений.
     */
    public function test_handles_boolean_values()
    {
        // Тест различных форматов булевых значений
        $testCases = [
            ['onlyAdult' => 'true', 'expected' => true],
            ['onlyAdult' => '1', 'expected' => true],
            ['onlyAdult' => 'false', 'expected' => false],
            ['onlyAdult' => '0', 'expected' => false],
            ['cr_onlyAdult' => '1', 'expected' => true],
            ['cr_onlyAdult' => '0', 'expected' => false]
        ];

        foreach ($testCases as $testCase) {
            $response = $this->withHeaders([
                'Accept' => 'application/json',
            ])->get('/api/creatives/filters/validate?' . http_build_query($testCase));
            $response->assertStatus(200);

            $data = $response->json();
            if (isset($data['filters']['onlyAdult'])) {
                $this->assertEquals($testCase['expected'], $data['filters']['onlyAdult']);
            }
        }
    }

    /** @test */
    public function test_country_validation_with_valid_iso_codes()
    {
        // Тест с валидным ISO2 кодом
        $response = $this->withHeaders([
            'Accept' => 'application/json',
        ])->get('/api/creatives/filters/validate?' . http_build_query([
            'country' => 'US'
        ]));

        $response->assertStatus(200);

        // Тест с валидным ISO3 кодом
        $response = $this->withHeaders([
            'Accept' => 'application/json',
        ])->get('/api/creatives/filters/validate?' . http_build_query([
            'country' => 'CAN'
        ]));

        $response->assertStatus(200);

        // Тест со специальными значениями
        $response = $this->withHeaders([
            'Accept' => 'application/json',
        ])->get('/api/creatives/filters/validate?' . http_build_query([
            'country' => 'default'
        ]));

        $response->assertStatus(200);

        $response = $this->withHeaders([
            'Accept' => 'application/json',
        ])->get('/api/creatives/filters/validate?' . http_build_query([
            'country' => 'all'
        ]));

        $response->assertStatus(200);
    }

    /** @test */
    public function test_country_validation_with_invalid_iso_codes()
    {
        // Тест с несуществующим кодом страны
        $response = $this->withHeaders([
            'Accept' => 'application/json',
        ])->get('/api/creatives/filters/validate?' . http_build_query([
            'country' => 'ZZ'
        ]));

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['country']);

        // Тест с невалидным форматом
        $response = $this->withHeaders([
            'Accept' => 'application/json',
        ])->get('/api/creatives/filters/validate?' . http_build_query([
            'country' => 'INVALID'
        ]));

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['country']);
    }

    /** @test */
    public function test_cr_country_url_parameter_validation()
    {
        // Тест с валидным URL параметром
        $response = $this->withHeaders([
            'Accept' => 'application/json',
        ])->get('/api/creatives/filters/validate?' . http_build_query([
            'cr_country' => 'GB'
        ]));

        $response->assertStatus(200);

        // Тест с невалидным URL параметром
        $response = $this->withHeaders([
            'Accept' => 'application/json',
        ])->get('/api/creatives/filters/validate?' . http_build_query([
            'cr_country' => 'INVALID'
        ]));

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['cr_country']);
    }

    /** @test */
    public function test_inactive_countries_are_not_valid()
    {
        $response = $this->withHeaders([
            'Accept' => 'application/json',
        ])->get('/api/creatives/filters/validate?' . http_build_query([
            'country' => 'XX'
        ]));

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['country']);
    }

    /** @test */
    public function test_language_validation_with_valid_iso_codes()
    {
        // Тест с валидными ISO2 кодами языков
        $response = $this->withHeaders([
            'Accept' => 'application/json',
        ])->get('/api/creatives/filters/validate?' . http_build_query([
            'languages' => ['en', 'ru']
        ]));

        $response->assertStatus(200);

        // Тест с одним валидным языком
        $response = $this->withHeaders([
            'Accept' => 'application/json',
        ])->get('/api/creatives/filters/validate?' . http_build_query([
            'languages' => ['en']
        ]));

        $response->assertStatus(200);
    }

    /** @test */
    public function test_language_validation_with_invalid_iso_codes()
    {
        // Тест с несуществующим кодом языка
        $response = $this->withHeaders([
            'Accept' => 'application/json',
        ])->get('/api/creatives/filters/validate?' . http_build_query([
            'languages' => ['zz']
        ]));

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['languages.0']);

        // Тест со смешанными валидными и невалидными кодами
        $response = $this->withHeaders([
            'Accept' => 'application/json',
        ])->get('/api/creatives/filters/validate?' . http_build_query([
            'languages' => ['en', 'invalid']
        ]));

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['languages.1']);
    }

    /** @test */
    public function test_cr_languages_url_parameter_validation()
    {
        // Тест с валидными URL параметрами (comma-separated)
        $response = $this->withHeaders([
            'Accept' => 'application/json',
        ])->get('/api/creatives/filters/validate?' . http_build_query([
            'cr_languages' => 'fr,de'
        ]));

        $response->assertStatus(200);

        // Тест с одним валидным языком в URL
        $response = $this->withHeaders([
            'Accept' => 'application/json',
        ])->get('/api/creatives/filters/validate?' . http_build_query([
            'cr_languages' => 'fr'
        ]));

        $response->assertStatus(200);

        // Тест с невалидным URL параметром
        $response = $this->withHeaders([
            'Accept' => 'application/json',
        ])->get('/api/creatives/filters/validate?' . http_build_query([
            'cr_languages' => 'fr,invalid'
        ]));

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['cr_languages']);
    }

    /** @test */
    public function test_inactive_languages_are_not_valid()
    {
        $response = $this->withHeaders([
            'Accept' => 'application/json',
        ])->get('/api/creatives/filters/validate?' . http_build_query([
            'languages' => ['xx']
        ]));

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['languages.0']);
    }
}

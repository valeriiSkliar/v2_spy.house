<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\User;

class CreativeDetailsTranslationsTest extends TestCase
{
    use RefreshDatabase;
    public function test_creative_details_translations_available_in_russian(): void
    {
        // Устанавливаем русскую локаль для этого теста
        app()->setLocale('ru');

        // Тестируем контроллер напрямую
        $controller = new \App\Http\Controllers\Frontend\Creatives\CreativesController();

        // Используем рефлексию для доступа к protected методу
        $reflection = new \ReflectionClass($controller);
        $method = $reflection->getMethod('getDetailsTranslations');
        $method->setAccessible(true);

        $detailsTranslations = $method->invoke($controller);

        // Проверяем ключевые переводы которые использует компонент
        $this->assertArrayHasKey('advertisingNetworks', $detailsTranslations);
        $this->assertArrayHasKey('country', $detailsTranslations);
        $this->assertArrayHasKey('language', $detailsTranslations);
        $this->assertArrayHasKey('status', $detailsTranslations);
        $this->assertArrayHasKey('active', $detailsTranslations);
        $this->assertArrayHasKey('inactive', $detailsTranslations);

        // Проверяем правильность русских переводов
        $this->assertEquals('Рекламные сети', $detailsTranslations['advertisingNetworks']);
        $this->assertEquals('Страна', $detailsTranslations['country']);
        $this->assertEquals('Язык', $detailsTranslations['language']);
        $this->assertEquals('Статус', $detailsTranslations['status']);
        $this->assertEquals('Активно', $detailsTranslations['active']);
        $this->assertEquals('Не активно', $detailsTranslations['inactive']);
    }

    public function test_creative_details_translations_available_in_english(): void
    {
        app()->setLocale('en');

        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/creatives');

        $response->assertStatus(200);

        // Проверяем что переводы details переданы в view
        $response->assertViewHas('detailsTranslations');

        $detailsTranslations = $response->viewData('detailsTranslations');

        // Проверяем правильность английских переводов
        $this->assertEquals('Advertising networks', $detailsTranslations['advertisingNetworks']);
        $this->assertEquals('Country', $detailsTranslations['country']);
        $this->assertEquals('Language', $detailsTranslations['language']);
        $this->assertEquals('Status', $detailsTranslations['status']);
        $this->assertEquals('Active', $detailsTranslations['active']);
        $this->assertEquals('Inactive', $detailsTranslations['inactive']);
    }

    public function test_creative_details_all_required_translations_present(): void
    {
        app()->setLocale('ru');

        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/creatives');
        $detailsTranslations = $response->viewData('detailsTranslations');

        // Полный список переводов требуемых компонентом CreativeDetailsComponent
        $requiredKeys = [
            'title',
            'addToFavorites',
            'removeFromFavorites',
            'download',
            'openTab',
            'copy',
            'copied',
            'icon',
            'image',
            'text',
            'titleField',
            'description',
            'translateText',
            'redirectsDetails',
            'advertisingNetworks',
            'country',
            'language',
            'firstDisplayDate',
            'lastDisplayDate',
            'status',
            'active',
            'inactive'
        ];

        foreach ($requiredKeys as $key) {
            $this->assertArrayHasKey($key, $detailsTranslations, "Missing translation key: $key");
            $this->assertNotEmpty($detailsTranslations[$key], "Empty translation for key: $key");
        }
    }
}

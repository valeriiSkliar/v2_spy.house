<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class TranslationSystemIntegrationTest extends TestCase
{
    /**
     * Тест обновленной системы переводов контроллера креативов
     */
    public function test_creatives_controller_provides_unified_translations()
    {
        // Отправляем запрос на страницу креативов
        $response = $this->get('/creatives');

        $response->assertStatus(200);

        // Проверяем что в view передаются все необходимые переводы
        $response->assertViewHas([
            'allTranslations',  // Новая система - единый источник
            'filtersTranslations',  // Обратная совместимость
            'tabsTranslations',
            'detailsTranslations',
        ]);

        $viewData = $response->original->getData();

        // Проверяем структуру новой системы переводов
        $this->assertIsArray($viewData['allTranslations']);

        // Проверяем что все необходимые ключи фильтров присутствуют
        $expectedFilterKeys = [
            'title',
            'searchKeyword',
            'dateCreation',
            'sortBy',
            'periodDisplay',
            'onlyAdult',
            'isDetailedVisible',
            'advertisingNetworks',
            'languages',
            'operatingSystems',
            'browsers',
            'devices',
            'imageSizes',
            'savedSettings',
            'savePresetButton',
            'resetButton',
            'customDateLabel'
        ];

        foreach ($expectedFilterKeys as $key) {
            $this->assertArrayHasKey(
                $key,
                $viewData['allTranslations'],
                "Missing translation key: {$key}"
            );
            $this->assertNotEmpty(
                $viewData['allTranslations'][$key],
                "Empty translation for key: {$key}"
            );
        }

        // Проверяем ключи вкладок
        $expectedTabKeys = ['push', 'inpage', 'facebook', 'tiktok'];
        foreach ($expectedTabKeys as $key) {
            $this->assertArrayHasKey(
                $key,
                $viewData['allTranslations'],
                "Missing tab translation key: {$key}"
            );
        }

        // Проверяем ключи деталей
        $expectedDetailKeys = [
            'copy',
            'copied',
            'share',
            'preview',
            'information',
            'stats',
            'close'
        ];
        foreach ($expectedDetailKeys as $key) {
            $this->assertArrayHasKey(
                $key,
                $viewData['allTranslations'],
                "Missing detail translation key: {$key}"
            );
        }

        // Проверяем ключи состояний
        $expectedStateKeys = ['loading', 'error', 'empty', 'success', 'processing'];
        foreach ($expectedStateKeys as $key) {
            $this->assertArrayHasKey(
                $key,
                $viewData['allTranslations'],
                "Missing state translation key: {$key}"
            );
        }

        // Проверяем ключи действий  
        $expectedActionKeys = ['retry', 'refresh', 'loadMore', 'save', 'cancel'];
        foreach ($expectedActionKeys as $key) {
            $this->assertArrayHasKey(
                $key,
                $viewData['allTranslations'],
                "Missing action translation key: {$key}"
            );
        }
    }

    /**
     * Тест что переводы корректно используют Laravel локализацию
     */
    public function test_translations_use_laravel_localization()
    {
        // Тестируем русскую локализацию (по умолчанию)
        config(['app.locale' => 'ru']);
        config(['app.fallback_locale' => 'ru']);

        $response = $this->get('/creatives');
        $viewData = $response->original->getData();

        // Проверяем что переводы не пустые и соответствуют ожидаемым значениям
        $this->assertNotEmpty($viewData['allTranslations']['searchKeyword']);
        $this->assertNotEmpty($viewData['allTranslations']['dateCreation']);
        $this->assertNotEmpty($viewData['allTranslations']['title']);

        // Проверяем что ключи переводов присутствуют (структурный тест)
        $expectedKeys = [
            'searchKeyword',
            'dateCreation',
            'title',
            'sortBy',
            'periodDisplay',
            'onlyAdult',
            'isDetailedVisible',
            'resetButton',
            'customDateLabel'
        ];

        foreach ($expectedKeys as $key) {
            $this->assertArrayHasKey($key, $viewData['allTranslations']);
            $this->assertIsString($viewData['allTranslations'][$key]);
            $this->assertNotEmpty($viewData['allTranslations'][$key]);
        }

        // Проверяем что переводы используют Laravel __ функции
        // (косвенно через проверку что они не равны ключам)
        $this->assertNotEquals('searchKeyword', $viewData['allTranslations']['searchKeyword']);
        $this->assertNotEquals('dateCreation', $viewData['allTranslations']['dateCreation']);
        $this->assertNotEquals('title', $viewData['allTranslations']['title']);
    }

    /**
     * Тест что обратная совместимость работает
     */
    public function test_backward_compatibility_translations()
    {
        $response = $this->get('/creatives');
        $viewData = $response->original->getData();

        // Проверяем что старые массивы переводов все еще доступны
        $this->assertArrayHasKey('filtersTranslations', $viewData);
        $this->assertArrayHasKey('tabsTranslations', $viewData);
        $this->assertArrayHasKey('detailsTranslations', $viewData);

        // Проверяем что в старых массивах есть ожидаемые ключи
        $this->assertArrayHasKey('searchKeyword', $viewData['filtersTranslations']);
        $this->assertArrayHasKey('push', $viewData['tabsTranslations']);
        $this->assertArrayHasKey('copy', $viewData['detailsTranslations']);
    }

    /**
     * Тест что количество переводов соответствует ожиданиям
     */
    public function test_translations_count_is_sufficient()
    {
        $response = $this->get('/creatives');
        $viewData = $response->original->getData();

        $translationsCount = count($viewData['allTranslations']);

        // Должно быть достаточно переводов для всех компонентов
        // ~17 фильтров + 4 вкладки + 15 деталей + 5 состояний + 9 действий = ~50 переводов
        $this->assertGreaterThan(
            40,
            $translationsCount,
            "Insufficient translations count: {$translationsCount}"
        );

        // Но не слишком много (предотвращаем раздувание)
        $this->assertLessThan(
            100,
            $translationsCount,
            "Too many translations: {$translationsCount}"
        );
    }
}

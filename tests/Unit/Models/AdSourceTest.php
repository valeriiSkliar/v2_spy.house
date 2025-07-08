<?php

namespace Tests\Unit\Models;

use App\Models\AdSource;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdSourceTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Тест создания источника рекламы
     */
    public function test_can_create_ad_source(): void
    {
        $adSource = AdSource::create([
            'source_name' => 'test_source',
            'source_display_name' => 'Test Source',
        ]);

        $this->assertInstanceOf(AdSource::class, $adSource);
        $this->assertEquals('test_source', $adSource->source_name);
        $this->assertEquals('Test Source', $adSource->source_display_name);
        $this->assertNotNull($adSource->created_at);
        $this->assertNotNull($adSource->updated_at);
    }

    /**
     * Тест уникальности source_name
     */
    public function test_source_name_must_be_unique(): void
    {
        AdSource::create([
            'source_name' => 'duplicate_source',
            'source_display_name' => 'First Source',
        ]);

        $this->expectException(\Illuminate\Database\QueryException::class);

        AdSource::create([
            'source_name' => 'duplicate_source',
            'source_display_name' => 'Second Source',
        ]);
    }

    /**
     * Тест метода findBySourceName
     */
    public function test_find_by_source_name(): void
    {
        $adSource = AdSource::create([
            'source_name' => 'push_house',
            'source_display_name' => 'Push House',
        ]);

        $found = AdSource::findBySourceName('push_house');
        $this->assertInstanceOf(AdSource::class, $found);
        $this->assertEquals($adSource->id, $found->id);

        $notFound = AdSource::findBySourceName('non_existent');
        $this->assertNull($notFound);
    }

    /**
     * Тест метода getActive
     */
    public function test_get_active_sources(): void
    {
        AdSource::create([
            'source_name' => 'tiktok',
            'source_display_name' => 'TikTok Ads',
        ]);

        AdSource::create([
            'source_name' => 'facebook',
            'source_display_name' => 'Facebook Ads',
        ]);

        $activeSources = AdSource::getActive();
        $this->assertCount(2, $activeSources);

        // Проверяем сортировку по display_name
        $this->assertEquals('Facebook Ads', $activeSources->first()->source_display_name);
        $this->assertEquals('TikTok Ads', $activeSources->last()->source_display_name);
    }

    /**
     * Тест метода exists
     */
    public function test_source_exists(): void
    {
        AdSource::create([
            'source_name' => 'feed_house',
            'source_display_name' => 'Feed House',
        ]);

        $this->assertTrue(AdSource::exists('feed_house'));
        $this->assertFalse(AdSource::exists('non_existent_source'));
    }

    /**
     * Тест использования фабрики
     */
    public function test_factory_creates_valid_ad_source(): void
    {
        $adSource = AdSource::factory()->create();

        $this->assertInstanceOf(AdSource::class, $adSource);
        $this->assertNotEmpty($adSource->source_name);
        $this->assertNotEmpty($adSource->source_display_name);
    }

    /**
     * Тест специфичных методов фабрики
     */
    public function test_factory_specific_methods(): void
    {
        $pushHouse = AdSource::factory()->pushHouse()->create();
        $this->assertEquals('push_house', $pushHouse->source_name);
        $this->assertEquals('Push House', $pushHouse->source_display_name);

        $tiktok = AdSource::factory()->tiktok()->create();
        $this->assertEquals('tiktok', $tiktok->source_name);
        $this->assertEquals('TikTok Ads', $tiktok->source_display_name);
    }
}

<?php

namespace Tests\Unit;

use App\Enums\Frontend\AdvertisingFormat;
use App\Enums\Frontend\AdvertisingStatus;
use App\Enums\Frontend\BrowserType;
use App\Enums\Frontend\DeviceType;
use App\Enums\Frontend\OperationSystem;
use App\Enums\Frontend\Platform;
use App\Models\AdSource;
use App\Models\Browser;
use App\Models\Creative;
use App\Models\Frontend\IsoEntity;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CreativeIsoRelationshipTest extends TestCase
{
    use RefreshDatabase;

    public function test_creative_belongs_to_country()
    {
        // Создаём страну
        $country = IsoEntity::create([
            'type' => 'country',
            'iso_code_2' => 'US',
            'iso_code_3' => 'USA',
            'numeric_code' => '840',
            'name' => 'United States',
            'is_active' => true,
        ]);

        // Создаём креатив с привязкой к стране
        $creative = Creative::create([
            'format' => AdvertisingFormat::PUSH->value,
            'status' => AdvertisingStatus::Active->value,
            'country_id' => $country->id,
            'external_id' => 12345,
            'combined_hash' => hash('sha256', 'test_creative_12345'),
        ]);

        // Проверяем связь
        $this->assertInstanceOf(IsoEntity::class, $creative->country);
        $this->assertEquals($country->id, $creative->country->id);
        $this->assertEquals('country', $creative->country->type);
        $this->assertEquals('US', $creative->country->iso_code_2);
    }

    public function test_creative_belongs_to_language()
    {
        // Создаём язык
        $language = IsoEntity::create([
            'type' => 'language',
            'iso_code_2' => 'en',
            'iso_code_3' => 'eng',
            'name' => 'English',
            'is_active' => true,
        ]);

        // Создаём креатив с привязкой к языку
        $creative = Creative::create([
            'format' => AdvertisingFormat::PUSH->value,
            'status' => AdvertisingStatus::Active->value,
            'language_id' => $language->id,
            'external_id' => 12346,
            'combined_hash' => hash('sha256', 'test_creative_12346'),
        ]);

        // Проверяем связь
        $this->assertInstanceOf(IsoEntity::class, $creative->language);
        $this->assertEquals($language->id, $creative->language->id);
        $this->assertEquals('language', $creative->language->type);
        $this->assertEquals('en', $creative->language->iso_code_2);
    }

    public function test_iso_entity_has_many_creatives_as_country()
    {
        // Создаём страну
        $country = IsoEntity::create([
            'type' => 'country',
            'iso_code_2' => 'US',
            'iso_code_3' => 'USA',
            'numeric_code' => '840',
            'name' => 'United States',
            'is_active' => true,
        ]);

        // Создаём несколько креативов с привязкой к стране
        $creative1 = Creative::create([
            'format' => AdvertisingFormat::PUSH->value,
            'status' => AdvertisingStatus::Active->value,
            'country_id' => $country->id,
            'external_id' => 12347,
            'combined_hash' => hash('sha256', 'test_creative_12347'),
        ]);

        $creative2 = Creative::create([
            'format' => AdvertisingFormat::INPAGE->value,
            'status' => AdvertisingStatus::Active->value,
            'country_id' => $country->id,
            'external_id' => 12348,
            'combined_hash' => hash('sha256', 'test_creative_12348'),
        ]);

        // Проверяем обратную связь
        $this->assertCount(2, $country->creativesAsCountry);
        $this->assertTrue($country->creativesAsCountry->contains($creative1));
        $this->assertTrue($country->creativesAsCountry->contains($creative2));
    }

    public function test_iso_entity_has_many_creatives_as_language()
    {
        // Создаём язык
        $language = IsoEntity::create([
            'type' => 'language',
            'iso_code_2' => 'en',
            'iso_code_3' => 'eng',
            'name' => 'English',
            'is_active' => true,
        ]);

        // Создаём несколько креативов с привязкой к языку
        $creative1 = Creative::create([
            'format' => AdvertisingFormat::INPAGE->value,
            'status' => AdvertisingStatus::Active->value,
            'language_id' => $language->id,
            'external_id' => 12349,
            'combined_hash' => hash('sha256', 'test_creative_12349'),
        ]);

        $creative2 = Creative::create([
            'format' => AdvertisingFormat::INPAGE->value,
            'status' => AdvertisingStatus::Active->value,
            'language_id' => $language->id,
            'external_id' => 12350,
            'combined_hash' => hash('sha256', 'test_creative_12350'),
        ]);

        // Проверяем обратную связь
        $this->assertCount(2, $language->creativesAsLanguage);
        $this->assertTrue($language->creativesAsLanguage->contains($creative1));
        $this->assertTrue($language->creativesAsLanguage->contains($creative2));
    }

    public function test_creative_can_have_both_country_and_language()
    {
        // Создаём страну и язык
        $country = IsoEntity::create([
            'type' => 'country',
            'iso_code_2' => 'US',
            'iso_code_3' => 'USA',
            'numeric_code' => '840',
            'name' => 'United States',
            'is_active' => true,
        ]);

        $language = IsoEntity::create([
            'type' => 'language',
            'iso_code_2' => 'en',
            'iso_code_3' => 'eng',
            'name' => 'English',
            'is_active' => true,
        ]);

        // Создаём креатив с привязкой к стране и языку
        $creative = Creative::create([
            'format' => AdvertisingFormat::FACEBOOK->value,
            'status' => AdvertisingStatus::Active->value,
            'country_id' => $country->id,
            'language_id' => $language->id,
            'external_id' => 12351,
            'combined_hash' => hash('sha256', 'test_creative_12351'),
        ]);

        // Проверяем обе связи
        $this->assertInstanceOf(IsoEntity::class, $creative->country);
        $this->assertInstanceOf(IsoEntity::class, $creative->language);
        $this->assertEquals($country->id, $creative->country->id);
        $this->assertEquals($language->id, $creative->language->id);
    }

    public function test_creative_belongs_to_browser()
    {
        // Создаём браузер
        $browser = Browser::create([
            'browser' => 'Chrome',
            'browser_type' => BrowserType::BROWSER->value,
            'device_type' => DeviceType::DESKTOP->value,
            'user_agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
            'browser_version' => '91.0.4472.124',
            'platform' => 'Windows',
            'is_active' => true,
            'is_for_filter' => true,
        ]);

        // Создаём креатив с привязкой к браузеру
        $creative = Creative::create([
            'format' => AdvertisingFormat::PUSH->value,
            'status' => AdvertisingStatus::Active->value,
            'browser_id' => $browser->id,
            'operation_system' => OperationSystem::WINDOWS->value,
            'external_id' => 12352,
            'combined_hash' => hash('sha256', 'test_creative_12352'),
        ]);

        // Проверяем связь
        $this->assertInstanceOf(Browser::class, $creative->browser);
        $this->assertEquals($browser->id, $creative->browser->id);
        $this->assertEquals('Chrome', $creative->browser->browser);
        $this->assertEquals(OperationSystem::WINDOWS, $creative->operation_system);
    }

    public function test_browser_has_many_creatives()
    {
        // Создаём браузер
        $browser = Browser::create([
            'browser' => 'Firefox',
            'browser_type' => BrowserType::BROWSER->value,
            'device_type' => DeviceType::DESKTOP->value,
            'user_agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:89.0) Gecko/20100101 Firefox/89.0',
            'browser_version' => '89.0',
            'platform' => 'Windows',
            'is_active' => true,
            'is_for_filter' => true,
        ]);

        // Создаём несколько креативов с привязкой к браузеру
        $creative1 = Creative::create([
            'format' => AdvertisingFormat::PUSH->value,
            'status' => AdvertisingStatus::Active->value,
            'browser_id' => $browser->id,
            'operation_system' => OperationSystem::WINDOWS->value,
            'external_id' => 12353,
            'combined_hash' => hash('sha256', 'test_creative_12353'),
        ]);

        $creative2 = Creative::create([
            'format' => AdvertisingFormat::INPAGE->value,
            'status' => AdvertisingStatus::Active->value,
            'browser_id' => $browser->id,
            'operation_system' => OperationSystem::LINUX->value,
            'external_id' => 12354,
            'combined_hash' => hash('sha256', 'test_creative_12354'),
        ]);

        // Проверяем обратную связь
        $this->assertCount(2, $browser->creatives);
        $this->assertTrue($browser->creatives->contains($creative1));
        $this->assertTrue($browser->creatives->contains($creative2));
    }

    public function test_creative_with_all_targeting_data()
    {
        // Создаём все необходимые сущности
        $country = IsoEntity::create([
            'type' => 'country',
            'iso_code_2' => 'US',
            'iso_code_3' => 'USA',
            'numeric_code' => '840',
            'name' => 'United States',
            'is_active' => true,
        ]);

        $language = IsoEntity::create([
            'type' => 'language',
            'iso_code_2' => 'en',
            'iso_code_3' => 'eng',
            'name' => 'English',
            'is_active' => true,
        ]);

        $browser = Browser::create([
            'browser' => 'Safari',
            'browser_type' => BrowserType::BROWSER->value,
            'device_type' => DeviceType::MOBILE->value,
            'user_agent' => 'Mozilla/5.0 (iPhone; CPU iPhone OS 14_6 like Mac OS X)',
            'browser_version' => '14.6',
            'platform' => 'iOS',
            'ismobiledevice' => true,
            'is_active' => true,
            'is_for_filter' => true,
        ]);

        // Создаём креатив со всеми параметрами таргетинга
        $creative = Creative::create([
            'format' => AdvertisingFormat::INPAGE->value,
            'status' => AdvertisingStatus::Active->value,
            'country_id' => $country->id,
            'language_id' => $language->id,
            'browser_id' => $browser->id,
            'operation_system' => OperationSystem::IOS->value,
            'external_id' => 12355,
            'combined_hash' => hash('sha256', 'test_creative_12355'),
        ]);

        // Проверяем все связи
        $this->assertInstanceOf(IsoEntity::class, $creative->country);
        $this->assertInstanceOf(IsoEntity::class, $creative->language);
        $this->assertInstanceOf(Browser::class, $creative->browser);
        $this->assertEquals(OperationSystem::IOS, $creative->operation_system);

        // Проверяем конкретные значения
        $this->assertEquals('US', $creative->country->iso_code_2);
        $this->assertEquals('en', $creative->language->iso_code_2);
        $this->assertEquals('Safari', $creative->browser->browser);
        $this->assertEquals('ios', $creative->operation_system->value);
    }

    public function test_creative_belongs_to_ad_source()
    {
        // Создаём источник рекламы
        $adSource = AdSource::create([
            'source_name' => 'push_house',
            'source_display_name' => 'Push House',
        ]);

        // Создаём креатив с привязкой к источнику
        $creative = Creative::create([
            'format' => AdvertisingFormat::PUSH->value,
            'status' => AdvertisingStatus::Active->value,
            'source_id' => $adSource->id,
            'external_id' => 12356,
            'combined_hash' => hash('sha256', 'test_creative_12356'),
        ]);

        // Проверяем связь
        $this->assertInstanceOf(AdSource::class, $creative->source);
        $this->assertEquals($adSource->id, $creative->source->id);
        $this->assertEquals('push_house', $creative->source->source_name);
        $this->assertEquals('Push House', $creative->source->source_display_name);
    }

    public function test_creative_with_platform_enum()
    {
        // Создаём креатив с платформой
        $creative = Creative::create([
            'format' => AdvertisingFormat::PUSH->value,
            'status' => AdvertisingStatus::Active->value,
            'platform' => Platform::MOBILE->value,
            'external_id' => 12357,
            'combined_hash' => hash('sha256', 'test_creative_12357'),
        ]);

        // Проверяем enum платформы
        $this->assertInstanceOf(Platform::class, $creative->platform);
        $this->assertEquals(Platform::MOBILE, $creative->platform);
        $this->assertEquals('mobile', $creative->platform->value);
    }

    public function test_creative_with_new_processing_fields()
    {
        $now = now();
        $externalCreatedAt = $now->copy()->subDay();

        // Создаём креатив с новыми полями обработки
        $creative = Creative::create([
            'format' => AdvertisingFormat::PUSH->value,
            'status' => AdvertisingStatus::Active->value,
            'external_id' => 12358,
            'combined_hash' => hash('sha256', 'test_creative_12358'),
            'is_processed' => true,
            'processed_at' => $now,
            'is_valid' => true,
            'validation_error' => null,
            'processing_error' => null,
            'external_created_at' => $externalCreatedAt,
        ]);

        // Проверяем новые поля
        $this->assertTrue($creative->is_processed);
        $this->assertTrue($creative->is_valid);
        $this->assertEquals($now->toDateTimeString(), $creative->processed_at->toDateTimeString());
        $this->assertEquals($externalCreatedAt->toDateTimeString(), $creative->external_created_at->toDateTimeString());
        $this->assertNull($creative->validation_error);
        $this->assertNull($creative->processing_error);
    }

    public function test_creative_with_validation_errors()
    {
        // Создаём креатив с ошибками валидации
        $creative = Creative::create([
            'format' => AdvertisingFormat::PUSH->value,
            'status' => AdvertisingStatus::Active->value,
            'external_id' => 12359,
            'combined_hash' => hash('sha256', 'test_creative_12359'),
            'is_processed' => false,
            'is_valid' => false,
            'validation_error' => 'Invalid image format',
            'processing_error' => 'Failed to download image',
        ]);

        // Проверяем поля с ошибками
        $this->assertFalse($creative->is_processed);
        $this->assertFalse($creative->is_valid);
        $this->assertEquals('Invalid image format', $creative->validation_error);
        $this->assertEquals('Failed to download image', $creative->processing_error);
    }

    public function test_creative_to_array_includes_new_fields()
    {
        // Создаём источник рекламы
        $adSource = AdSource::create([
            'source_name' => 'tiktok',
            'source_display_name' => 'TikTok Ads',
        ]);

        $now = now();
        $externalCreatedAt = $now->copy()->subHour();

        // Создаём креатив со всеми новыми полями
        $creative = Creative::create([
            'format' => AdvertisingFormat::TIKTOK->value,
            'status' => AdvertisingStatus::Active->value,
            'source_id' => $adSource->id,
            'platform' => Platform::DESKTOP->value,
            'external_id' => 12360,
            'combined_hash' => hash('sha256', 'test_creative_12360'),
            'is_processed' => true,
            'processed_at' => $now,
            'is_valid' => true,
            'external_created_at' => $externalCreatedAt,
        ]);

        $array = $creative->toCreativeArray();

        // Проверяем новые поля в массиве
        $this->assertEquals('desktop', $array['platform']);
        $this->assertEquals('TikTok Ads', $array['source']);
        $this->assertTrue($array['is_processed']);
        $this->assertTrue($array['is_valid']);
        $this->assertEquals($now->format('Y-m-d H:i:s'), $array['processed_at']);
        $this->assertEquals($externalCreatedAt->format('Y-m-d'), $array['external_created_at']);
        $this->assertNull($array['validation_error']);
        $this->assertNull($array['processing_error']);
    }
}

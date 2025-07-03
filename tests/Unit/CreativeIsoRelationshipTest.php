<?php

namespace Tests\Unit;

use App\Enums\Frontend\AdvertisingFormat;
use App\Enums\Frontend\AdvertisingStatus;
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
            'format' => AdvertisingFormat::NATIVE->value,
            'status' => AdvertisingStatus::Active->value,
            'language_id' => $language->id,
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
        ]);

        $creative2 = Creative::create([
            'format' => AdvertisingFormat::POP->value,
            'status' => AdvertisingStatus::Active->value,
            'country_id' => $country->id,
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
        ]);

        $creative2 = Creative::create([
            'format' => AdvertisingFormat::NATIVE->value,
            'status' => AdvertisingStatus::Active->value,
            'language_id' => $language->id,
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
        ]);

        // Проверяем обе связи
        $this->assertInstanceOf(IsoEntity::class, $creative->country);
        $this->assertInstanceOf(IsoEntity::class, $creative->language);
        $this->assertEquals($country->id, $creative->country->id);
        $this->assertEquals($language->id, $creative->language->id);
    }
}

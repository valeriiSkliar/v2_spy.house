<?php

namespace Tests\Unit\DTOs;

use App\Http\DTOs\CreativeDTO;
use Carbon\Carbon;
use Tests\TestCase;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Contracts\Support\Jsonable;

class CreativeDTOTest extends TestCase
{
    public function test_dto_can_be_created_from_array()
    {
        $data = [
            'id' => 1,
            'title' => 'Test Title',
            'description' => 'Test Description',
            'category' => 'push',
            'country' => 'US',
            'file_size' => '1024KB',
            'icon_url' => 'https://example.com/icon.png',
            'landing_page_url' => 'https://example.com',
            'created_at' => '2024-01-15',
        ];

        $dto = CreativeDTO::fromArray($data);

        $this->assertEquals(1, $dto->id);
        $this->assertEquals('Test Title', $dto->title);
        $this->assertEquals('push', $dto->category);
        $this->assertEquals('US', $dto->country);
    }

    public function test_dto_computes_properties_correctly()
    {
        $data = [
            'id' => 1,
            'title' => 'Test Title',
            'description' => 'Test Description',
            'category' => 'push',
            'country' => 'US',
            'file_size' => '1024KB',
            'icon_url' => 'https://example.com/icon.png',
            'landing_page_url' => 'https://example.com',
            'created_at' => now()->subDays(3)->format('Y-m-d'), // 3 дня назад - recent
            'activity_date' => now()->subDays(35)->format('Y-m-d'), // 35 дней назад - не активен
        ];

        $dto = CreativeDTO::fromArrayWithComputed($data);

        // Проверяем computed свойства
        $this->assertEquals('Test Title', $dto->displayName);
        $this->assertTrue($dto->isRecent); // 3 дня назад - recent
        $this->assertFalse($dto->is_active); // 35 дней назад - не активен
        $this->assertNotNull($dto->created_at_formatted);
        $this->assertNotNull($dto->last_activity_date_formatted);
    }

    public function test_dto_validates_required_fields()
    {
        $invalidData = [
            'title' => 'Test Title',
            // Пропущено обязательное поле 'id'
        ];

        $errors = CreativeDTO::validate($invalidData);

        $this->assertNotEmpty($errors);
        $this->assertContains("Field 'id' is required", $errors);
    }

    public function test_dto_validates_data_types()
    {
        $invalidData = [
            'id' => 'not-a-number', // Должно быть число
            'title' => 'Test Title',
            'description' => 'Test Description',
            'category' => 'push',
            'country' => 'US',
            'file_size' => '1024KB',
            'icon_url' => 'https://example.com/icon.png',
            'landing_page_url' => 'https://example.com',
            'created_at' => '2024-01-15',
            'has_video' => 'yes', // Должно быть boolean
            'advertising_networks' => 'facebook', // Должно быть array
        ];

        $errors = CreativeDTO::validate($invalidData);

        $this->assertNotEmpty($errors);
        $this->assertContains("Field 'id' must be numeric", $errors);
        $this->assertContains("Field 'has_video' must be boolean", $errors);
        $this->assertContains("Field 'advertising_networks' must be array", $errors);
    }

    public function test_dto_converts_to_array_correctly()
    {
        $data = [
            'id' => 1,
            'title' => 'Test Title',
            'description' => 'Test Description',
            'category' => 'push',
            'country' => 'US',
            'file_size' => '1024KB',
            'icon_url' => 'https://example.com/icon.png',
            'landing_page_url' => 'https://example.com',
            'created_at' => '2024-01-15',
            'has_video' => true,
            'social_likes' => 1000,
            'advertising_networks' => ['facebook', 'google'],
        ];

        $dto = CreativeDTO::fromArrayWithComputed($data);
        $result = $dto->toArray();

        // Проверяем исходные данные
        $this->assertEquals(1, $result['id']);
        $this->assertTrue($result['has_video']);
        $this->assertEquals(1000, $result['social_likes']);
        $this->assertEquals(['facebook', 'google'], $result['advertising_networks']);

        // Проверяем computed свойства
        $this->assertEquals('Test Title', $result['displayName']);
        $this->assertIsBool($result['isRecent']);
        $this->assertNotNull($result['created_at_formatted']);
    }

    public function test_dto_collection_processes_multiple_items()
    {
        $items = [
            [
                'id' => 1,
                'title' => 'Title 1',
                'description' => 'Description 1',
                'category' => 'push',
                'country' => 'US',
                'file_size' => '1024KB',
                'icon_url' => 'https://example.com/icon1.png',
                'landing_page_url' => 'https://example1.com',
                'created_at' => '2024-01-15',
            ],
            [
                'id' => 2,
                'title' => 'Title 2',
                'description' => 'Description 2',
                'category' => 'inpage',
                'country' => 'GB',
                'file_size' => '2048KB',
                'icon_url' => 'https://example.com/icon2.png',
                'landing_page_url' => 'https://example2.com',
                'created_at' => '2024-01-16',
            ],
        ];

        $collection = CreativeDTO::collection($items);

        $this->assertCount(2, $collection);
        $this->assertEquals(1, $collection[0]['id']);
        $this->assertEquals(2, $collection[1]['id']);
        $this->assertEquals('Title 1', $collection[0]['displayName']);
        $this->assertEquals('Title 2', $collection[1]['displayName']);
    }

    public function test_dto_handles_optional_fields()
    {
        $minimalData = [
            'id' => 1,
            'title' => 'Test Title',
            'description' => 'Test Description',
            'category' => 'push',
            'country' => 'US',
            'file_size' => '1024KB',
            'icon_url' => 'https://example.com/icon.png',
            'landing_page_url' => 'https://example.com',
            'created_at' => '2024-01-15',
        ];

        $dto = CreativeDTO::fromArray($minimalData);

        // Проверяем что опциональные поля имеют дефолтные значения
        $this->assertNull($dto->video_url);
        $this->assertFalse($dto->has_video);
        $this->assertNull($dto->advertising_networks);
        $this->assertFalse($dto->is_adult);
        $this->assertNull($dto->social_likes);
    }

    public function test_dto_validates_urls()
    {
        $invalidData = [
            'id' => 1,
            'title' => 'Test Title',
            'description' => 'Test Description',
            'category' => 'push',
            'country' => 'US',
            'file_size' => '1024KB',
            'icon_url' => 'invalid-url',
            'landing_page_url' => 'not-a-url',
            'created_at' => '2024-01-15',
        ];

        $errors = CreativeDTO::validate($invalidData);

        $this->assertNotEmpty($errors);
        $this->assertContains("Field 'icon_url' must be a valid URL", $errors);
        $this->assertContains("Field 'landing_page_url' must be a valid URL", $errors);
    }

    public function test_dto_validates_categories()
    {
        $invalidData = [
            'id' => 1,
            'title' => 'Test Title',
            'description' => 'Test Description',
            'category' => 'invalid_category',
            'country' => 'US',
            'file_size' => '1024KB',
            'icon_url' => 'https://example.com/icon.png',
            'landing_page_url' => 'https://example.com',
            'created_at' => '2024-01-15',
        ];

        $errors = CreativeDTO::validate($invalidData);

        $this->assertNotEmpty($errors);
        $this->assertContains("Field 'category' must be one of: push, inpage, banner, video, native, facebook, tiktok", $errors);
    }

    public function test_dto_validates_social_fields()
    {
        $invalidData = [
            'id' => 1,
            'title' => 'Test Title',
            'description' => 'Test Description',
            'category' => 'push',
            'country' => 'US',
            'file_size' => '1024KB',
            'icon_url' => 'https://example.com/icon.png',
            'landing_page_url' => 'https://example.com',
            'created_at' => '2024-01-15',
            'social_likes' => 'not-a-number',
            'social_comments' => 'invalid',
        ];

        $errors = CreativeDTO::validate($invalidData);

        $this->assertNotEmpty($errors);
        $this->assertContains("Field 'social_likes' must be numeric", $errors);
        $this->assertContains("Field 'social_comments' must be numeric", $errors);
    }

    public function test_dto_converts_social_fields_to_int()
    {
        $data = [
            'id' => 1,
            'title' => 'Test Title',
            'description' => 'Test Description',
            'category' => 'push',
            'country' => 'US',
            'file_size' => '1024KB',
            'icon_url' => 'https://example.com/icon.png',
            'landing_page_url' => 'https://example.com',
            'created_at' => '2024-01-15',
            'social_likes' => '1000',
            'social_comments' => '50',
            'social_shares' => '25',
        ];

        $dto = CreativeDTO::fromArray($data);

        $this->assertIsInt($dto->social_likes);
        $this->assertIsInt($dto->social_comments);
        $this->assertIsInt($dto->social_shares);
        $this->assertEquals(1000, $dto->social_likes);
        $this->assertEquals(50, $dto->social_comments);
        $this->assertEquals(25, $dto->social_shares);
    }

    public function test_dto_implements_contracts()
    {
        $data = [
            'id' => 1,
            'title' => 'Test Title',
            'description' => 'Test Description',
            'category' => 'push',
            'country' => 'US',
            'file_size' => '1024KB',
            'icon_url' => 'https://example.com/icon.png',
            'landing_page_url' => 'https://example.com',
            'created_at' => '2024-01-15',
        ];

        $dto = CreativeDTO::fromArray($data);

        $this->assertInstanceOf(\Illuminate\Contracts\Support\Arrayable::class, $dto);
        $this->assertInstanceOf(\Illuminate\Contracts\Support\Jsonable::class, $dto);

        $json = $dto->toJson();
        $this->assertIsString($json);
        $this->assertJson($json);
    }

    public function test_dto_compact_array()
    {
        $data = [
            'id' => 1,
            'title' => 'Test Title',
            'description' => 'Very long description that should not be in compact version',
            'category' => 'push',
            'country' => 'US',
            'file_size' => '1024KB',
            'icon_url' => 'https://example.com/icon.png',
            'landing_page_url' => 'https://example.com',
            'created_at' => '2024-01-15',
        ];

        $dto = CreativeDTO::fromArrayWithComputed($data);
        $compact = $dto->toCompactArray();

        // Проверяем что в компактной версии только основные поля
        $this->assertArrayHasKey('id', $compact);
        $this->assertArrayHasKey('title', $compact);
        $this->assertArrayHasKey('description', $compact);
        $this->assertArrayHasKey('advertising_networks', $compact);
    }

    public function test_dto_metadata()
    {
        $data = [
            'id' => 1,
            'title' => 'Test Title',
            'description' => 'Test Description',
            'category' => 'push',
            'country' => 'US',
            'file_size' => '1024KB',
            'icon_url' => 'https://example.com/icon.png',
            'landing_page_url' => 'https://example.com',
            'created_at' => '2024-01-15',
            'has_video' => true,
            'social_likes' => 100,
            'social_comments' => 20,
            'social_shares' => 5,
            'advertising_networks' => ['facebook'],
            'operating_systems' => ['android'],
        ];

        $dto = CreativeDTO::fromArray($data);
        $metadata = $dto->getMetadata();

        $this->assertArrayHasKey('hasVideo', $metadata);
        $this->assertArrayHasKey('socialEngagement', $metadata);
        $this->assertArrayHasKey('techInfo', $metadata);
        $this->assertArrayHasKey('platforms', $metadata);

        $this->assertTrue($metadata['hasVideo']);
        $this->assertEquals(125, $metadata['socialEngagement']['total']); // 100+20+5
        $this->assertEquals(['facebook'], $metadata['platforms']['networks']);
    }
}

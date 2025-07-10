<?php

namespace Tests\Unit\Services;

use App\Services\Parsers\CreativeImageValidator;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * Тесты для валидатора изображений креативов
 * 
 * Проверяет работу HTTP валидации, обработку ошибок,
 * валидацию типов контента и размеров изображений
 *
 * @package Tests\Unit\Services
 * @author SeniorSoftwareEngineer
 */
class CreativeImageValidatorTest extends TestCase
{
    private CreativeImageValidator $validator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->validator = new CreativeImageValidator();
    }

    /** @test */
    public function it_validates_accessible_image_successfully()
    {
        Http::fake([
            'https://example.com/image.jpg' => Http::response('', 200, [
                'Content-Type' => 'image/jpeg',
                'Content-Length' => '50000'
            ])
        ]);

        $result = $this->validator->isImageAccessible('https://example.com/image.jpg');
        $this->assertTrue($result);
    }

    /** @test */
    public function it_rejects_invalid_url_formats()
    {
        $this->assertFalse($this->validator->isImageAccessible(''));
        $this->assertFalse($this->validator->isImageAccessible('not-a-url'));
        $this->assertFalse($this->validator->isImageAccessible('ftp://example.com/image.jpg'));
    }

    /** @test */
    public function it_handles_http_errors_gracefully()
    {
        Http::fake([
            'https://example.com/notfound.jpg' => Http::response('', 404)
        ]);

        $result = $this->validator->isImageAccessible('https://example.com/notfound.jpg');
        $this->assertFalse($result);
    }

    /** @test */
    public function it_provides_detailed_image_information()
    {
        Http::fake([
            'https://example.com/image.png' => Http::response('', 200, [
                'Content-Type' => 'image/png',
                'Content-Length' => '75000'
            ])
        ]);

        $details = $this->validator->getImageDetails('https://example.com/image.png');

        $this->assertTrue($details['valid']);
        $this->assertTrue($details['accessible']);
        $this->assertEquals('image/png', $details['content_type']);
        $this->assertEquals(75000, $details['content_length']);
        $this->assertTrue($details['type_valid']);
        $this->assertTrue($details['size_valid']);
        $this->assertEquals(200, $details['status_code']);
        $this->assertNotNull($details['response_time_ms']);
    }

    /** @test */
    public function it_rejects_unsupported_content_types()
    {
        Http::fake([
            'https://example.com/document.pdf' => Http::response('', 200, [
                'Content-Type' => 'application/pdf',
                'Content-Length' => '50000'
            ])
        ]);

        $details = $this->validator->getImageDetails('https://example.com/document.pdf');

        $this->assertFalse($details['valid']);
        $this->assertTrue($details['accessible']);
        $this->assertFalse($details['type_valid']);
        $this->assertStringContainsString('unsupported content type', $details['error']);
    }

    /** @test */
    public function it_rejects_images_that_are_too_large()
    {
        Http::fake([
            'https://example.com/huge-image.jpg' => Http::response('', 200, [
                'Content-Type' => 'image/jpeg',
                'Content-Length' => '20971520' // 20MB, больше лимита в 10MB
            ])
        ]);

        $details = $this->validator->getImageDetails('https://example.com/huge-image.jpg');

        $this->assertFalse($details['valid']);
        $this->assertTrue($details['accessible']);
        $this->assertTrue($details['type_valid']);
        $this->assertFalse($details['size_valid']);
        $this->assertStringContainsString('invalid size', $details['error']);
    }

    /** @test */
    public function it_rejects_images_that_are_too_small()
    {
        Http::fake([
            'https://example.com/tiny-image.jpg' => Http::response('', 200, [
                'Content-Type' => 'image/jpeg',
                'Content-Length' => '500' // Меньше лимита в 1KB
            ])
        ]);

        $details = $this->validator->getImageDetails('https://example.com/tiny-image.jpg');

        $this->assertFalse($details['valid']);
        $this->assertFalse($details['size_valid']);
    }

    /** @test */
    public function it_validates_multiple_images_correctly()
    {
        Http::fake([
            'https://example.com/good-image.jpg' => Http::response('', 200, [
                'Content-Type' => 'image/jpeg',
                'Content-Length' => '50000'
            ]),
            'https://example.com/bad-image.jpg' => Http::response('', 404)
        ]);

        $results = $this->validator->validateImages([
            'https://example.com/good-image.jpg',
            'https://example.com/bad-image.jpg'
        ]);

        $this->assertCount(2, $results);
        $this->assertTrue($results['https://example.com/good-image.jpg']['valid']);
        $this->assertFalse($results['https://example.com/bad-image.jpg']['valid']);
    }

    /** @test */
    public function it_validates_creative_data_successfully()
    {
        Http::fake([
            'https://example.com/icon.png' => Http::response('', 200, [
                'Content-Type' => 'image/png',
                'Content-Length' => '5000'
            ]),
            'https://example.com/main-image.jpg' => Http::response('', 200, [
                'Content-Type' => 'image/jpeg',
                'Content-Length' => '50000'
            ])
        ]);

        $creativeData = [
            'title' => 'Test Creative',
            'description' => 'Test Description',
            'external_id' => '12345',
            'icon_url' => 'https://example.com/icon.png',
            'main_image_url' => 'https://example.com/main-image.jpg'
        ];

        $result = $this->validator->isCreativeValid($creativeData);
        $this->assertTrue($result);
    }

    /** @test */
    public function it_rejects_creative_without_required_fields()
    {
        $creativeData = [
            'icon_url' => 'https://example.com/icon.png'
            // Отсутствуют title, description, external_id
        ];

        $result = $this->validator->isCreativeValid($creativeData);
        $this->assertFalse($result);
    }

    /** @test */
    public function it_rejects_creative_without_any_images()
    {
        $creativeData = [
            'title' => 'Test Creative',
            'description' => 'Test Description',
            'external_id' => '12345'
            // Нет изображений
        ];

        $result = $this->validator->isCreativeValid($creativeData);
        $this->assertFalse($result);
    }

    /** @test */
    public function it_handles_connection_timeouts_gracefully()
    {
        Http::fake([
            'https://slow-server.com/image.jpg' => function () {
                throw new \Exception('Connection timeout');
            }
        ]);

        $result = $this->validator->isImageAccessible('https://slow-server.com/image.jpg');
        $this->assertFalse($result);
    }

    /** @test */
    public function it_handles_empty_urls_in_validation_array()
    {
        $results = $this->validator->validateImages([
            'https://example.com/valid.jpg',
            '', // Пустой URL
            null // null URL
        ]);

        $this->assertCount(3, $results);
        $this->assertFalse($results['']['valid']);
        $this->assertEquals('Empty URL provided', $results['']['error']);
        $this->assertFalse($results['null']['valid']);
        $this->assertEquals('Empty URL provided', $results['null']['error']);
    }

    /** @test */
    public function it_returns_correct_configuration_values()
    {
        $allowedTypes = CreativeImageValidator::getAllowedImageTypes();
        $this->assertContains('image/jpeg', $allowedTypes);
        $this->assertContains('image/png', $allowedTypes);

        $sizeLimits = CreativeImageValidator::getSizeLimits();
        $this->assertArrayHasKey('min', $sizeLimits);
        $this->assertArrayHasKey('max', $sizeLimits);
        $this->assertEquals(1024, $sizeLimits['min']);

        $httpSettings = CreativeImageValidator::getHttpSettings();
        $this->assertArrayHasKey('timeout', $httpSettings);
        $this->assertArrayHasKey('max_redirects', $httpSettings);
    }

    /** @test */
    public function it_accepts_image_without_content_type_header()
    {
        Http::fake([
            'https://example.com/no-headers.jpg' => Http::response('', 200)
            // Без Content-Type и Content-Length заголовков
        ]);

        $details = $this->validator->getImageDetails('https://example.com/no-headers.jpg');

        // Должно быть валидным, так как отсутствие заголовков не является ошибкой
        $this->assertTrue($details['valid']);
        $this->assertTrue($details['accessible']);
        $this->assertNull($details['content_type']);
        $this->assertNull($details['content_length']);
    }

    /** @test */
    public function it_handles_redirects_properly()
    {
        Http::fake([
            'https://redirect.com/image.jpg' => Http::response('', 200, [
                'Content-Type' => 'image/jpeg',
                'Content-Length' => '50000'
            ])
        ]);

        $result = $this->validator->isImageAccessible('https://redirect.com/image.jpg');
        $this->assertTrue($result);
    }

    /** @test */
    public function it_cleans_content_type_from_additional_parameters()
    {
        Http::fake([
            'https://example.com/image.jpg' => Http::response('', 200, [
                'Content-Type' => 'image/jpeg; charset=utf-8', // С дополнительными параметрами
                'Content-Length' => '50000'
            ])
        ]);

        $details = $this->validator->getImageDetails('https://example.com/image.jpg');

        $this->assertEquals('image/jpeg', $details['content_type']);
        $this->assertTrue($details['type_valid']);
        $this->assertTrue($details['valid']);
    }

    /** @test */
    public function it_rejects_tracking_urls()
    {
        $trackingUrls = [
            'https://feed-9561.feedfinder23.info/api/push/track?id=3o-spluyd&event=1',
            'https://example.com/metrics/save.img?event=impressions',
            'https://analytics.example.com/pixel/beacon.gif',
            'https://tracker.com/counter.php?event=click'
        ];

        foreach ($trackingUrls as $url) {
            $details = $this->validator->getImageDetails($url);
            $this->assertFalse($details['valid']);
            $this->assertStringContainsString('tracking pixel', $details['error']);
        }
    }

    /** @test */
    public function it_rejects_blacklisted_domains()
    {
        $blacklistedUrls = [
            'https://mcufwk.xyz/image.jpg',
            'https://yimufc.xyz/photo.png',
            'https://imcdn.co/picture.gif',
            'https://eu.histi.co/banner.jpg'
        ];

        foreach ($blacklistedUrls as $url) {
            $details = $this->validator->getImageDetails($url);
            $this->assertFalse($details['valid']);
            $this->assertStringContainsString('blacklisted', $details['error']);
        }
    }

    /** @test */
    public function it_allows_legitimate_image_urls()
    {
        Http::fake([
            'https://cdn.example.com/image.jpg' => Http::response('', 200, [
                'Content-Type' => 'image/jpeg',
                'Content-Length' => '50000'
            ])
        ]);

        $legitimateUrls = [
            'https://cdn.example.com/image.jpg',
            'https://images.unsplash.com/photo.png',
            'https://static.example.com/assets/banner.gif'
        ];

        foreach ($legitimateUrls as $url) {
            if ($url === 'https://cdn.example.com/image.jpg') {
                $details = $this->validator->getImageDetails($url);
                $this->assertTrue($details['valid']);
            }
        }
    }
}

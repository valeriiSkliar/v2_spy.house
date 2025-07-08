<?php

namespace Tests\Unit\Services;

use App\Enums\Frontend\Platform;
use App\Services\Parsers\CreativePlatformNormalizer;
use App\Services\Parsers\PlatformNormalizers\PlatformNormalizerInterface;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class CreativePlatformNormalizerTest extends TestCase
{
    private CreativePlatformNormalizer $normalizer;

    protected function setUp(): void
    {
        parent::setUp();
        $this->normalizer = new CreativePlatformNormalizer();
    }

    /** @test */
    public function it_normalizes_push_house_mobile_platforms()
    {
        $testCases = [
            'Mob' => Platform::MOBILE,
            'mob' => Platform::MOBILE,
            'MOB' => Platform::MOBILE,
            'mobile' => Platform::MOBILE,
            'Mobile' => Platform::MOBILE,
            ' mob ' => Platform::MOBILE, // с пробелами
        ];

        foreach ($testCases as $input => $expected) {
            $result = $this->normalizer->normalize($input, 'push_house');
            $this->assertEquals($expected, $result, "Failed for input: {$input}");
        }
    }

    /** @test */
    public function it_normalizes_push_house_desktop_platforms()
    {
        $testCases = [
            'desktop' => Platform::DESKTOP,
            'Desktop' => Platform::DESKTOP,
            'DESKTOP' => Platform::DESKTOP,
            'desk' => Platform::DESKTOP,
            'pc' => Platform::DESKTOP,
            'web' => Platform::DESKTOP,
            ' desktop ' => Platform::DESKTOP, // с пробелами
        ];

        foreach ($testCases as $input => $expected) {
            $result = $this->normalizer->normalize($input, 'push_house');
            $this->assertEquals($expected, $result, "Failed for input: {$input}");
        }
    }

    /** @test */
    public function it_returns_mobile_as_fallback_for_unknown_push_house_values()
    {
        $unknownValues = ['tablet', 'tv', 'unknown', '', 'smartwatch'];

        foreach ($unknownValues as $input) {
            $result = $this->normalizer->normalize($input, 'push_house');
            $this->assertEquals(Platform::MOBILE, $result, "Failed for unknown input: {$input}");
        }
    }

    /** @test */
    public function it_throws_exception_for_unknown_source()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('No normalizer found for source: unknown_source');

        $this->normalizer->normalize('mobile', 'unknown_source');
    }

    /** @test */
    public function it_can_register_custom_normalizer()
    {
        $customNormalizer = new class implements PlatformNormalizerInterface {
            public function normalize(string $platformValue): Platform
            {
                return $platformValue === 'custom_mobile' ? Platform::MOBILE : Platform::DESKTOP;
            }

            public function canHandle(string $source): bool
            {
                return $source === 'custom_source';
            }
        };

        $this->normalizer->registerNormalizer($customNormalizer);

        $result = $this->normalizer->normalize('custom_mobile', 'custom_source');
        $this->assertEquals(Platform::MOBILE, $result);

        $result = $this->normalizer->normalize('anything_else', 'custom_source');
        $this->assertEquals(Platform::DESKTOP, $result);
    }

    /** @test */
    public function static_method_works_correctly()
    {
        $result = CreativePlatformNormalizer::normalizePlatform('Mob', 'push_house');
        $this->assertEquals(Platform::MOBILE, $result);

        $result = CreativePlatformNormalizer::normalizePlatform('desktop', 'push_house');
        $this->assertEquals(Platform::DESKTOP, $result);
    }
}

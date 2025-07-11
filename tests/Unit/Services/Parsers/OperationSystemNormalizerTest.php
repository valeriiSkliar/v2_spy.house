<?php

namespace Tests\Unit\Services\Parsers;

use App\Enums\Frontend\OperationSystem;
use App\Services\Parsers\OperationSystemNormalizer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class OperationSystemNormalizerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Очищаем кеш перед каждым тестом
        OperationSystemNormalizer::clearCache();
    }

    protected function tearDown(): void
    {
        // Очищаем кеш после каждого теста
        OperationSystemNormalizer::clearCache();

        parent::tearDown();
    }

    /** @test */
    public function it_normalizes_android_variants_correctly()
    {
        $androidVariants = [
            'Android' => OperationSystem::ANDROID->value,
            'android' => OperationSystem::ANDROID->value,
            'Android 12' => OperationSystem::ANDROID->value,
            'android 13.0' => OperationSystem::ANDROID->value,
            'Android Mobile' => OperationSystem::ANDROID->value,
            'Google Android' => OperationSystem::ANDROID->value,
            'android os' => OperationSystem::ANDROID->value,
            'Android Tablet' => OperationSystem::ANDROID->value,
        ];

        foreach ($androidVariants as $input => $expected) {
            $this->assertEquals(
                $expected,
                OperationSystemNormalizer::normalizeOperationSystem($input),
                "Failed to normalize: {$input}"
            );
        }
    }

    /** @test */
    public function it_normalizes_windows_variants_correctly()
    {
        $windowsVariants = [
            'Windows' => OperationSystem::WINDOWS->value,
            'windows' => OperationSystem::WINDOWS->value,
            'Windows 10' => OperationSystem::WINDOWS->value,
            'Windows 11' => OperationSystem::WINDOWS->value,
            'Microsoft Windows' => OperationSystem::WINDOWS->value,
            'Win' => OperationSystem::WINDOWS->value,
            'Windows NT' => OperationSystem::WINDOWS->value,
            'Windows Phone' => OperationSystem::WINDOWS->value,
        ];

        foreach ($windowsVariants as $input => $expected) {
            $this->assertEquals(
                $expected,
                OperationSystemNormalizer::normalizeOperationSystem($input),
                "Failed to normalize: {$input}"
            );
        }
    }

    /** @test */
    public function it_normalizes_macos_variants_correctly()
    {
        $macosVariants = [
            'macOS' => OperationSystem::MACOS->value,
            'macos' => OperationSystem::MACOS->value,
            'Mac OS' => OperationSystem::MACOS->value,
            'Mac OS X' => OperationSystem::MACOS->value,
            'OSX' => OperationSystem::MACOS->value,
            'OS X' => OperationSystem::MACOS->value,
            'Darwin' => OperationSystem::MACOS->value,
            'Apple macOS' => OperationSystem::MACOS->value,
        ];

        foreach ($macosVariants as $input => $expected) {
            $this->assertEquals(
                $expected,
                OperationSystemNormalizer::normalizeOperationSystem($input),
                "Failed to normalize: {$input}"
            );
        }
    }

    /** @test */
    public function it_normalizes_ios_variants_correctly()
    {
        $iosVariants = [
            'iOS' => OperationSystem::IOS->value,
            'ios' => OperationSystem::IOS->value,
            'iOS 15' => OperationSystem::IOS->value,
            'iPhone OS' => OperationSystem::IOS->value,
            'iPhone' => OperationSystem::IOS->value,
            'iPad' => OperationSystem::IOS->value,
            'iPadOS' => OperationSystem::IOS->value,
        ];

        foreach ($iosVariants as $input => $expected) {
            $this->assertEquals(
                $expected,
                OperationSystemNormalizer::normalizeOperationSystem($input),
                "Failed to normalize: {$input}"
            );
        }
    }

    /** @test */
    public function it_normalizes_linux_variants_correctly()
    {
        $linuxVariants = [
            'Linux' => OperationSystem::LINUX->value,
            'linux' => OperationSystem::LINUX->value,
            'Ubuntu' => OperationSystem::LINUX->value,
            'Debian' => OperationSystem::LINUX->value,
            'Fedora' => OperationSystem::LINUX->value,
            'Arch Linux' => OperationSystem::LINUX->value,
            'GNU/Linux' => OperationSystem::LINUX->value,
        ];

        foreach ($linuxVariants as $input => $expected) {
            $this->assertEquals(
                $expected,
                OperationSystemNormalizer::normalizeOperationSystem($input),
                "Failed to normalize: {$input}"
            );
        }
    }

    /** @test */
    public function it_handles_unknown_os_correctly()
    {
        $unknownVariants = [
            'SomeUnknownOS',
            'CustomOS',
            'FantasySystem',
        ];

        foreach ($unknownVariants as $input) {
            $this->assertNull(
                OperationSystemNormalizer::normalizeOperationSystem($input),
                "Should return null for unknown OS: {$input}"
            );
        }
    }

    /** @test */
    public function it_handles_empty_input_correctly()
    {
        $emptyInputs = ['', '   ', null];

        foreach ($emptyInputs as $input) {
            $this->assertNull(
                OperationSystemNormalizer::normalizeOperationSystem($input ?? ''),
                "Should return null for empty input"
            );
        }
    }

    /** @test */
    public function it_works_with_fallback_method()
    {
        // Тестируем точное совпадение
        $this->assertEquals(
            OperationSystem::ANDROID->value,
            OperationSystemNormalizer::normalizeWithFallback('Android')
        );

        // Тестируем частичное совпадение (если входящее название содержит известный вариант)
        $this->assertEquals(
            OperationSystem::WINDOWS->value,
            OperationSystemNormalizer::normalizeWithFallback('Windows Server 2019')
        );

        // Тестируем fallback на OTHER для неизвестной ОС
        $this->assertEquals(
            OperationSystem::OTHER->value,
            OperationSystemNormalizer::normalizeWithFallback('CompletelyUnknownOS')
        );
    }

    /** @test */
    public function it_provides_os_information_correctly()
    {
        $info = OperationSystemNormalizer::getOperationSystemInfo('Android 12');

        $this->assertNotNull($info);
        $this->assertEquals(OperationSystem::ANDROID->value, $info['enum_value']);
        $this->assertEquals('Android', $info['label']);
        $this->assertEquals('Android 12', $info['original_input']);
        $this->assertArrayHasKey('translated_label', $info);
    }

    /** @test */
    public function it_validates_os_names_correctly()
    {
        $this->assertTrue(OperationSystemNormalizer::isValidOperationSystem('Windows 10'));
        $this->assertTrue(OperationSystemNormalizer::isValidOperationSystem('iOS'));
        $this->assertFalse(OperationSystemNormalizer::isValidOperationSystem('UnknownOS'));
        $this->assertFalse(OperationSystemNormalizer::isValidOperationSystem(''));
    }

    /** @test */
    public function it_processes_batch_normalization_correctly()
    {
        $inputBatch = [
            'Windows 10',
            'Android 12',
            'iOS 15',
            'UnknownOS',
            'macOS'
        ];

        $result = OperationSystemNormalizer::normalizeBatch($inputBatch);

        $this->assertEquals(OperationSystem::WINDOWS->value, $result['Windows 10']);
        $this->assertEquals(OperationSystem::ANDROID->value, $result['Android 12']);
        $this->assertEquals(OperationSystem::IOS->value, $result['iOS 15']);
        $this->assertEquals(OperationSystem::MACOS->value, $result['macOS']);
        $this->assertNull($result['UnknownOS']);
    }

    /** @test */
    public function it_provides_statistics_correctly()
    {
        // Загружаем карту
        OperationSystemNormalizer::normalizeOperationSystem('Windows');

        $stats = OperationSystemNormalizer::getStats();

        $this->assertArrayHasKey('total_mappings', $stats);
        $this->assertArrayHasKey('enum_distribution', $stats);
        $this->assertArrayHasKey('cache_initialized', $stats);
        $this->assertTrue($stats['cache_initialized']);
        $this->assertGreaterThan(0, $stats['total_mappings']);
    }

    /** @test */
    public function it_caches_mappings_correctly()
    {
        // Очищаем кеш
        Cache::flush();

        // Первый вызов должен построить карту и сохранить в кеш
        $result1 = OperationSystemNormalizer::normalizeOperationSystem('Windows');

        // Проверяем что результат есть в кеше
        $this->assertTrue(Cache::has('os_normalizer.variant_to_enum_map'));

        // Второй вызов должен использовать кеш
        $result2 = OperationSystemNormalizer::normalizeOperationSystem('Windows');

        $this->assertEquals($result1, $result2);
        $this->assertEquals(OperationSystem::WINDOWS->value, $result1);
    }

    /** @test */
    public function it_finds_partial_matches_correctly()
    {
        // Тестируем поиск по частичному совпадению
        $partialMatch = OperationSystemNormalizer::findByPartialMatch('Ubuntu 20.04 LTS');
        $this->assertEquals(OperationSystem::LINUX->value, $partialMatch);

        $partialMatch = OperationSystemNormalizer::findByPartialMatch('Windows Server 2019');
        $this->assertEquals(OperationSystem::WINDOWS->value, $partialMatch);

        $partialMatch = OperationSystemNormalizer::findByPartialMatch('iPhone 13 Pro');
        $this->assertEquals(OperationSystem::IOS->value, $partialMatch);

        // Тестируем случай когда частичного совпадения нет
        $noMatch = OperationSystemNormalizer::findByPartialMatch('CompletelyUnknownSystem');
        $this->assertNull($noMatch);
    }
}

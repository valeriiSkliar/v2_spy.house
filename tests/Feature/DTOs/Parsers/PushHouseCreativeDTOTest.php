<?php

namespace Tests\Feature\DTOs\Parsers;

use App\Enums\Frontend\AdvertisingFormat;
use App\Enums\Frontend\AdvertisingStatus;
use App\Enums\Frontend\Platform;
use App\Http\DTOs\Parsers\PushHouseCreativeDTO;
use App\Models\AdSource;
use App\Models\AdvertismentNetwork;
use App\Models\Frontend\IsoEntity;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\DatabaseSeeding;

class PushHouseCreativeDTOTest extends TestCase
{
    use RefreshDatabase, DatabaseSeeding;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedTestData();
    }

    /**
     * Создание тестовых данных
     */
    protected function seedTestData(): void
    {
        // Создаем тестовые страны
        $this->seedTestCountries();

        // Создаем источник push_house
        AdSource::create([
            'source_name' => 'push_house',
            'source_display_name' => 'Push House',
        ]);

        // Создаем рекламную сеть pushhouse
        AdvertismentNetwork::create([
            'network_name' => 'pushhouse',
            'network_display_name' => 'Push House',
            'description' => 'Push House advertising network for testing',
            'traffic_type_description' => 'push',
            'network_url' => 'https://push.house',
            'is_active' => true,
        ]);
    }

    /**
     * Тест создания DTO из новых данных API Push.House
     */
    public function test_creates_dto_from_new_api_format(): void
    {
        $apiData = [
            'id' => 1393905,
            'title' => '😱 PARABÉNS! VOCÊ GANHOU! 💵',
            'text' => '✅ BÔNUS R$4,675 + 100 GIRADAS GRÁTIS 🤑',
            'icon' => 'https://s3.push.house/icon.png',
            'img' => 'https://s3.push.house/image.png',
            'url' => 'https://example.com/?creative_id={camp}&source={site}',
            'cpc' => '0.00770000',
            'country' => 'BR',
            'platform' => 'Mob',
            'isAdult' => false,
            'isActive' => true,
            'created_at' => '2025-07-03'
        ];

        $dto = PushHouseCreativeDTO::fromApiResponse($apiData);

        $this->assertEquals(1393905, $dto->externalId);
        $this->assertEquals('😱 PARABÉNS! VOCÊ GANHOU! 💵', $dto->title);
        $this->assertEquals('✅ BÔNUS R$4,675 + 100 GIRADAS GRÁTIS 🤑', $dto->text);
        $this->assertEquals('https://s3.push.house/icon.png', $dto->iconUrl);
        $this->assertEquals('https://s3.push.house/image.png', $dto->imageUrl);
        $this->assertEquals('https://example.com/?creative_id={camp}&source={site}', $dto->targetUrl);
        $this->assertEquals(0.0077, $dto->cpc);
        $this->assertEquals('BR', $dto->countryCode);
        $this->assertEquals(Platform::MOBILE, $dto->platform);
        $this->assertFalse($dto->isAdult);
        $this->assertTrue($dto->isActive);
        $this->assertEquals('push_house', $dto->source);
        $this->assertInstanceOf(Carbon::class, $dto->createdAt);
    }

    /**
     * Тест создания DTO из старых данных парсера (legacy format)
     */
    public function test_creates_dto_from_legacy_parser_format(): void
    {
        $legacyData = [
            'res_uniq_id' => 12345,
            'title' => 'Legacy Title',
            'text' => 'Legacy Description',
            'icon' => 'https://legacy.com/icon.png',
            'img' => 'https://legacy.com/image.png',
            'url' => 'https://legacy.com/landing',
            'price_cpc' => 0.05,
            'country' => 'us',
            'platform' => 'mobile', // Новый формат платформы
            'isAdult' => true,
            'isActive' => false
        ];

        $dto = PushHouseCreativeDTO::fromApiResponse($legacyData);

        $this->assertEquals(12345, $dto->externalId);
        $this->assertEquals('Legacy Title', $dto->title);
        $this->assertEquals('Legacy Description', $dto->text);
        $this->assertEquals('https://legacy.com/icon.png', $dto->iconUrl);
        $this->assertEquals('https://legacy.com/image.png', $dto->imageUrl);
        $this->assertEquals('https://legacy.com/landing', $dto->targetUrl);
        $this->assertEquals(0.05, $dto->cpc);
        $this->assertEquals('US', $dto->countryCode); // Должен быть приведен к верхнему регистру
        $this->assertEquals(Platform::MOBILE, $dto->platform); // platform: mobile -> MOBILE
        $this->assertTrue($dto->isAdult);
        $this->assertFalse($dto->isActive);
    }

    /**
     * Тест обработки пустых/отсутствующих данных
     */
    public function test_handles_empty_and_missing_data(): void
    {
        $emptyData = [];

        $dto = PushHouseCreativeDTO::fromApiResponse($emptyData);

        $this->assertEquals(0, $dto->externalId);
        $this->assertEquals('', $dto->title);
        $this->assertEquals('', $dto->text);
        $this->assertEquals('', $dto->iconUrl);
        $this->assertEquals('', $dto->imageUrl);
        $this->assertEquals('', $dto->targetUrl);
        $this->assertEquals(0.0, $dto->cpc);
        $this->assertEquals('', $dto->countryCode);
        $this->assertEquals(Platform::MOBILE, $dto->platform); // Fallback
        $this->assertFalse($dto->isAdult);
        $this->assertTrue($dto->isActive); // По умолчанию true
    }

    /**
     * Тест нормализации платформы
     */
    public function test_platform_normalization(): void
    {
        // Тест различных значений платформы
        $testCases = [
            // Новый формат API
            [['platform' => 'Mob'], Platform::MOBILE],
            [['platform' => 'Desktop'], Platform::DESKTOP],
            [['platform' => 'mobile'], Platform::MOBILE],

            // Fallback
            [[], Platform::MOBILE],
        ];

        foreach ($testCases as [$input, $expectedPlatform]) {
            $dto = PushHouseCreativeDTO::fromApiResponse($input);
            $this->assertEquals(
                $expectedPlatform,
                $dto->platform,
                'Failed for input: ' . json_encode($input)
            );
        }
    }

    /**
     * Тест преобразования в формат БД с реальными нормализаторами
     */
    public function test_transforms_to_database_format(): void
    {
        $apiData = [
            'id' => 1393905,
            'title' => 'Test Title',
            'text' => 'Test Description',
            'icon' => 'https://example.com/icon.png',
            'img' => 'https://example.com/image.png',
            'url' => 'https://example.com/landing',
            'cpc' => '0.05',
            'country' => 'US',
            'platform' => 'Mob',
            'isAdult' => true,
            'isActive' => true,
            'created_at' => '2025-07-03'
        ];

        $dto = PushHouseCreativeDTO::fromApiResponse($apiData);
        $databaseData = $dto->toDatabase();

        // Проверяем основные поля
        $this->assertEquals(1393905, $databaseData['external_id']);
        $this->assertEquals('Test Title', $databaseData['title']);
        $this->assertEquals('Test Description', $databaseData['description']);
        $this->assertEquals('https://example.com/icon.png', $databaseData['icon_url']);
        $this->assertEquals('https://example.com/image.png', $databaseData['main_image_url']);
        $this->assertEquals('https://example.com/landing', $databaseData['landing_url']);
        $this->assertEquals('mobile', $databaseData['platform']);
        $this->assertTrue($databaseData['is_adult']);

        // Проверяем enum значения
        $this->assertEquals(AdvertisingStatus::Active, $databaseData['status']);
        $this->assertEquals(AdvertisingFormat::PUSH, $databaseData['format']);

        // Проверяем обязательные поля
        $this->assertArrayHasKey('combined_hash', $databaseData);
        $this->assertArrayHasKey('created_at', $databaseData);
        $this->assertArrayHasKey('updated_at', $databaseData);
        $this->assertArrayHasKey('external_created_at', $databaseData);

        // Проверяем нормализованные ID (должны работать с реальными данными)
        $this->assertArrayHasKey('source_id', $databaseData);
        $this->assertArrayHasKey('country_id', $databaseData);

        // Проверяем, что нормализаторы сработали
        $this->assertNotNull($databaseData['source_id']); // push_house должен найтись
        $this->assertNotNull($databaseData['country_id']); // US должен найтись
    }

    /**
     * Тест генерации хеша
     */
    public function test_generates_consistent_hash(): void
    {
        $apiData = [
            'id' => 1393905,
            'title' => 'Test Title',
            'text' => 'Test Description',
            'country' => 'US',
        ];

        $dto1 = PushHouseCreativeDTO::fromApiResponse($apiData);
        $dto2 = PushHouseCreativeDTO::fromApiResponse($apiData);

        $databaseData1 = $dto1->toDatabase();
        $databaseData2 = $dto2->toDatabase();

        // Хеши должны быть одинаковыми для одинаковых данных
        $this->assertEquals($databaseData1['combined_hash'], $databaseData2['combined_hash']);

        // Хеш должен быть 64-символьным SHA256
        $this->assertEquals(64, strlen($databaseData1['combined_hash']));
        $this->assertMatchesRegularExpression('/^[a-f0-9]{64}$/', $databaseData1['combined_hash']);
    }

    /**
     * Тест валидации DTO
     */
    public function test_validates_dto_data(): void
    {
        // Валидные данные (с изображением)
        $validData = [
            'id' => 123,
            'country' => 'US',
            'icon' => 'https://example.com/icon.png' // Добавляем валидное изображение
        ];
        $validDto = PushHouseCreativeDTO::fromApiResponse($validData);
        $this->assertTrue($validDto->isValid());

        // Невалидные данные - нет external_id
        $invalidData1 = [
            'country' => 'US',
            'icon' => 'https://example.com/icon.png'
        ];
        $invalidDto1 = PushHouseCreativeDTO::fromApiResponse($invalidData1);
        $this->assertFalse($invalidDto1->isValid());

        // Невалидные данные - нет country
        $invalidData2 = [
            'id' => 123,
            'icon' => 'https://example.com/icon.png'
        ];
        $invalidDto2 = PushHouseCreativeDTO::fromApiResponse($invalidData2);
        $this->assertFalse($invalidDto2->isValid());

        // Невалидные данные - пустые значения
        $invalidData3 = [
            'id' => 0,
            'country' => ''
        ];
        $invalidDto3 = PushHouseCreativeDTO::fromApiResponse($invalidData3);
        $this->assertFalse($invalidDto3->isValid());

        // Невалидные данные - нет изображений
        $invalidData4 = [
            'id' => 123,
            'country' => 'US'
            // Нет изображений
        ];
        $invalidDto4 = PushHouseCreativeDTO::fromApiResponse($invalidData4);
        $this->assertFalse($invalidDto4->isValid());
    }

    /**
     * Тест статуса активности
     */
    public function test_handles_activity_status(): void
    {
        // Активный креатив
        $activeData = ['id' => 123, 'country' => 'US', 'isActive' => true];
        $activeDto = PushHouseCreativeDTO::fromApiResponse($activeData);
        $activeDatabaseData = $activeDto->toDatabase();
        $this->assertEquals(AdvertisingStatus::Active, $activeDatabaseData['status']);

        // Неактивный креатив
        $inactiveData = ['id' => 124, 'country' => 'US', 'isActive' => false];
        $inactiveDto = PushHouseCreativeDTO::fromApiResponse($inactiveData);
        $inactiveDatabaseData = $inactiveDto->toDatabase();
        $this->assertEquals(AdvertisingStatus::Inactive, $inactiveDatabaseData['status']);

        // По умолчанию активный (если поле отсутствует)
        $defaultData = ['id' => 125, 'country' => 'US'];
        $defaultDto = PushHouseCreativeDTO::fromApiResponse($defaultData);
        $defaultDatabaseData = $defaultDto->toDatabase();
        $this->assertEquals(AdvertisingStatus::Active, $defaultDatabaseData['status']);
    }

    /**
     * Тест обработки дат
     */
    public function test_handles_dates(): void
    {
        // С указанной датой
        $dataWithDate = [
            'id' => 123,
            'country' => 'US',
            'created_at' => '2025-07-03 10:30:00'
        ];
        $dto = PushHouseCreativeDTO::fromApiResponse($dataWithDate);
        $this->assertEquals('2025-07-03 10:30:00', $dto->createdAt->format('Y-m-d H:i:s'));

        // Без указанной даты (должна использоваться текущая)
        $dataWithoutDate = [
            'id' => 124,
            'country' => 'US'
        ];
        $dtoWithoutDate = PushHouseCreativeDTO::fromApiResponse($dataWithoutDate);
        $this->assertInstanceOf(Carbon::class, $dtoWithoutDate->createdAt);
    }

    /**
     * Тест интеграции с реальными нормализаторами
     */
    public function test_integration_with_normalizers(): void
    {
        $apiData = [
            'id' => 999,
            'country' => 'CA', // Канада должна быть в тестовых данных
            'title' => 'Integration Test',
            'text' => 'Testing normalizers integration'
        ];

        $dto = PushHouseCreativeDTO::fromApiResponse($apiData);
        $databaseData = $dto->toDatabase();

        // Проверяем, что источник нормализовался
        $source = AdSource::find($databaseData['source_id']);
        $this->assertNotNull($source);
        $this->assertEquals('push_house', $source->source_name);

        // Проверяем, что страна нормализовалась
        $country = IsoEntity::find($databaseData['country_id']);
        $this->assertNotNull($country);
        $this->assertEquals('CA', $country->iso_code_2);
        $this->assertEquals('country', $country->type);
    }

    /**
     * Тест определения формата креатива на основе изображений
     */
    public function test_determines_advertising_format_based_on_images(): void
    {
        // Тест PUSH формата (оба изображения присутствуют)
        $pushData = [
            'id' => 1395508,
            'title' => 'Test Push Creative',
            'text' => 'Test Description',
            'icon' => 'https://s3.push.house/push.house-camps/100778/686b6454abb47.png',
            'img' => 'https://s3.push.house/push.house-camps/100778/686b6454a812e.png',
            'country' => 'US'
        ];

        $pushDto = PushHouseCreativeDTO::fromApiResponse($pushData);
        $pushDatabaseData = $pushDto->toDatabase();

        $this->assertEquals(AdvertisingFormat::PUSH, $pushDatabaseData['format']);
        $this->assertTrue($pushDto->isValid());

        // Тест INPAGE формата (только icon присутствует)
        $inpageData = [
            'id' => 1395482,
            'title' => 'Test Inpage Creative',
            'text' => 'Test Description',
            'icon' => 'https://s3.push.house/push.house-camps/102659/686b2495da589.png',
            'img' => 'https://s3.push.house/push.house-camps/102659/', // Нет имени файла
            'country' => 'US'
        ];

        $inpageDto = PushHouseCreativeDTO::fromApiResponse($inpageData);
        $inpageDatabaseData = $inpageDto->toDatabase();

        $this->assertEquals(AdvertisingFormat::INPAGE, $inpageDatabaseData['format']);
        $this->assertTrue($inpageDto->isValid());

        // Тест невалидного креатива (нет изображений с именами файлов)
        $invalidData = [
            'id' => 1395509,
            'title' => '',
            'text' => '',
            'icon' => 'https://s3.push.house/push.house-camps/28718/', // Нет имени файла
            'img' => 'https://s3.push.house/push.house-camps/28718/', // Нет имени файла
            'country' => 'US'
        ];

        $invalidDto = PushHouseCreativeDTO::fromApiResponse($invalidData);

        $this->assertFalse($invalidDto->isValid(), 'Креатив без изображений должен быть невалидным');
    }

    /**
     * Тест валидации URL изображений
     */
    public function test_image_url_validation(): void
    {
        $testCases = [
            // Валидные изображения
            [['icon' => 'https://example.com/image.png'], true, 'Valid PNG image'],
            [['icon' => 'https://example.com/image.jpg'], true, 'Valid JPG image'],
            [['img' => 'https://s3.push.house/camps/123/abc123.gif'], true, 'Valid GIF image'],

            // Невалидные изображения
            [['icon' => 'https://example.com/'], false, 'URL ending with slash'],
            [['icon' => 'https://example.com/path/'], false, 'Path ending with slash'],
            [['icon' => 'https://example.com/filename'], false, 'No file extension'],
            [['icon' => ''], false, 'Empty URL'],
        ];

        foreach ($testCases as [$imageData, $expectedValid, $description]) {
            $data = array_merge([
                'id' => 123,
                'country' => 'US',
                'icon' => '',
                'img' => ''
            ], $imageData);

            $dto = PushHouseCreativeDTO::fromApiResponse($data);

            $this->assertEquals(
                $expectedValid,
                $dto->isValid(),
                "Failed for case: {$description}"
            );
        }
    }

    /**
     * Тест граничных случаев определения формата
     */
    public function test_format_determination_edge_cases(): void
    {
        // Только img без icon → должен быть PUSH с fallback логикой
        $onlyImgData = [
            'id' => 123,
            'country' => 'US',
            'icon' => '', // Пустой
            'img' => 'https://example.com/image.png'
        ];

        $onlyImgDto = PushHouseCreativeDTO::fromApiResponse($onlyImgData);
        $onlyImgDatabaseData = $onlyImgDto->toDatabase();

        $this->assertEquals(AdvertisingFormat::PUSH, $onlyImgDatabaseData['format']);
        $this->assertTrue($onlyImgDto->isValid());

        // Оба URL пустые
        $noImagesData = [
            'id' => 124,
            'country' => 'US',
            'icon' => '',
            'img' => ''
        ];

        $noImagesDto = PushHouseCreativeDTO::fromApiResponse($noImagesData);

        $this->assertFalse($noImagesDto->isValid());

        // Оба URL заканчиваются слешем
        $slashEndingData = [
            'id' => 125,
            'country' => 'US',
            'icon' => 'https://example.com/path/',
            'img' => 'https://example.com/other/'
        ];

        $slashEndingDto = PushHouseCreativeDTO::fromApiResponse($slashEndingData);

        $this->assertFalse($slashEndingDto->isValid());
    }
}

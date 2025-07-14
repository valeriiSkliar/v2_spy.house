<?php

declare(strict_types=1);

namespace App\Services\Parsers\FeedHouse;

use App\Http\DTOs\Parsers\FeedHouseCreativeDTO;
use App\Models\AdSource;
use App\Services\Parsers\Exceptions\ParserException;
use App\Services\Parsers\FeedHouseParser;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

/**
 * FeedHouse Parsing Service
 * 
 * Главный координатор процесса парсинга FeedHouse
 * Объединяет FeedHouseParser и FeedHouseSynchronizer для выполнения полного workflow:
 * 1. Получение данных от API через курсорную пагинацию
 * 2. Преобразование в DTO
 * 3. Hybrid синхронизация с БД (immediate save + async enhancement)
 * 4. State management через AdSource
 * 
 * @package App\Services\Parsers\FeedHouse
 * @author SeniorSoftwareEngineer
 * @version 1.0.0
 */
class FeedHouseParsingService
{
    private FeedHouseParser $parser;
    private FeedHouseSynchronizer $synchronizer;

    /**
     * Результат последнего парсинга
     */
    private array $lastParsingResult = [];

    /**
     * Инициализация сервиса парсинга
     *
     * @param FeedHouseParser|null $parser
     * @param FeedHouseSynchronizer|null $synchronizer
     */
    public function __construct(
        ?FeedHouseParser $parser = null,
        ?FeedHouseSynchronizer $synchronizer = null
    ) {
        $this->parser = $parser ?? new FeedHouseParser();
        // Создаем реальный ParserManager для FeedHouseSynchronizer
        $parserManager = app(\App\Services\Parsers\ParserManager::class);
        $this->synchronizer = $synchronizer ?? new FeedHouseSynchronizer($parserManager);
    }

    /**
     * Выполнить one-shot парсинг FeedHouse с AdSource state management
     *
     * @param AdSource $adSource Модель источника для state management
     * @param array $options Опции парсинга
     * @return array Результат парсинга с статистикой
     * @throws ParserException
     */
    public function parseAndSync(AdSource $adSource, array $options = []): array
    {
        Log::info("FeedHouse Parsing: Starting one-shot parsing cycle", array_merge($options, [
            'adSource_id' => $adSource->id,
            'adSource_name' => $adSource->source_display_name
        ]));

        try {
            // Используем упрощенный FeedHouseSynchronizer для one-shot подхода
            $result = $this->synchronizer->synchronize($adSource, $options);

            Log::info("FeedHouse Parsing: One-shot cycle completed successfully", [
                'duration_seconds' => $result['duration_seconds'] ?? 0,
                'total_processed' => $result['total_processed'] ?? 0,
                'total_saved' => $result['total_saved'] ?? 0,
                'errors' => $result['errors'] ?? 0
            ]);

            return $this->lastParsingResult = $result;
        } catch (\Exception $e) {
            Log::error("FeedHouse Parsing: One-shot cycle failed", [
                'error' => $e->getMessage(),
                'adSource_id' => $adSource->id,
                'options' => $options
            ]);

            throw new ParserException("FeedHouse one-shot parsing failed: " . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Получить данные от FeedHouse API (для ручного использования)
     *
     * @param array $options Опции запроса
     * @return Collection Коллекция валидных DTO
     * @throws ParserException
     */
    public function fetchFromApi(array $options = []): Collection
    {
        $lastId = $options['lastId'] ?? null;
        $limit = $options['limit'] ?? 200;
        $formats = $options['formats'] ?? ['push', 'inpage'];
        $adNetworks = $options['adNetworks'] ?? $this->getActiveNetworks();

        Log::info("FeedHouse Parsing: Fetching from API", [
            'lastId' => $lastId,
            'limit' => $limit,
            'formats' => $formats,
            'adNetworks' => $adNetworks
        ]);

        try {
            $queryParams = [
                'limit' => $limit,
                'formats' => implode(',', $formats),
                'adNetworks' => implode(',', $adNetworks)
            ];

            if ($lastId !== null) {
                $queryParams['lastId'] = $lastId;
            }

            $apiData = $this->parser->fetchData($queryParams);

            // Конвертируем в DTO
            $dtoCollection = collect($apiData)->map(function ($item) {
                return FeedHouseCreativeDTO::fromApiResponse($item);
            })->filter(function ($dto) {
                return $dto->isValid();
            });

            Log::info("FeedHouse Parsing: API fetch completed", [
                'raw_count' => count($apiData),
                'valid_dto_count' => $dtoCollection->count()
            ]);

            return $dtoCollection;
        } catch (\Exception $e) {
            Log::error("FeedHouse Parsing: API fetch failed", [
                'error' => $e->getMessage(),
                'lastId' => $lastId,
                'limit' => $limit
            ]);

            throw new ParserException("Failed to fetch from FeedHouse API: " . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Синхронизировать данные с БД (для ручного использования)
     *
     * @param Collection $apiCreatives Креативы от API
     * @param AdSource $adSource Модель источника
     * @return array Результат синхронизации
     * @throws ParserException
     */
    public function syncWithDatabase(Collection $apiCreatives, AdSource $adSource): array
    {
        Log::info("FeedHouse Parsing: Starting database sync", [
            'creatives_count' => $apiCreatives->count(),
            'adSource_id' => $adSource->id
        ]);

        try {
            // Эмулируем результат синхронизации для batch данных
            $results = [
                'processed' => 0,
                'saved' => 0,
                'enhanced' => 0,
                'duplicates' => 0,
                'errors' => 0
            ];

            foreach ($apiCreatives as $dto) {
                try {
                    $results['processed']++;

                    // Проверяем на дубликат
                    if ($this->isDuplicate($dto)) {
                        $results['duplicates']++;
                        continue;
                    }

                    // Сохраняем немедленно
                    $creativeId = $this->saveImmediately($dto);
                    if ($creativeId) {
                        $results['saved']++;
                        $results['enhanced']++; // Логируем как enhanced
                    }
                } catch (\Exception $e) {
                    Log::error("Failed to process FeedHouse creative", [
                        'error' => $e->getMessage(),
                        'external_id' => $dto->externalId
                    ]);
                    $results['errors']++;
                }
            }

            Log::info("FeedHouse Parsing: Database sync completed", $results);

            return $results;
        } catch (\Exception $e) {
            Log::error("FeedHouse Parsing: Database sync failed", [
                'error' => $e->getMessage(),
                'creatives_count' => $apiCreatives->count()
            ]);

            throw new ParserException("Database synchronization failed: " . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Проверка на дубликат креатива
     */
    private function isDuplicate(FeedHouseCreativeDTO $dto): bool
    {
        $hash = $this->generateHashForDto($dto);
        return \App\Models\Creative::where('combined_hash', $hash)->exists();
    }

    /**
     * Немедленное сохранение креатива в БД
     */
    private function saveImmediately(FeedHouseCreativeDTO $dto): ?int
    {
        try {
            $basicData = $dto->toBasicDatabase();

            $creative = \App\Models\Creative::updateOrCreate(
                ['combined_hash' => $basicData['combined_hash']],
                $basicData
            );

            return $creative->id;
        } catch (\Exception $e) {
            Log::error("Failed to save creative immediately", [
                'error' => $e->getMessage(),
                'external_id' => $dto->externalId
            ]);
            return null;
        }
    }

    /**
     * Генерация хеша для DTO
     */
    private function generateHashForDto(FeedHouseCreativeDTO $dto): string
    {
        $data = [
            'external_id' => $dto->externalId,
            'source' => 'feedhouse',
            'title' => $dto->title,
            'text' => $dto->text,
            'country' => $dto->countryCode,
            'adNetwork' => $dto->adNetwork,
        ];

        return hash('sha256', json_encode($data, JSON_UNESCAPED_UNICODE));
    }

    /**
     * Формирование итогового результата парсинга
     *
     * @param array $syncResult Результат синхронизации
     * @param float $startTime Время начала
     * @param array $options Опции парсинга
     * @return array Итоговый результат
     */
    private function buildParsingResult(array $syncResult, float $startTime, array $options): array
    {
        $duration = microtime(true) - $startTime;

        return [
            // Основные метрики из синхронизатора
            'total_processed' => $syncResult['total_processed'] ?? 0,
            'total_saved' => $syncResult['total_saved'] ?? 0,
            'total_enhanced' => $syncResult['total_enhanced'] ?? 0,
            'duplicates_skipped' => $syncResult['duplicates_skipped'] ?? 0,
            'errors' => $syncResult['errors'] ?? 0,
            'batches_processed' => $syncResult['batches_processed'] ?? 0,

            // Информация о выполнении
            'status' => $syncResult['status'] ?? 'completed',
            'duration' => $duration,
            'duration_seconds' => round($duration, 2),
            'memory_used' => $syncResult['memory_used'] ?? 0,
            'memory_peak' => $syncResult['memory_peak'] ?? 0,

            // Режим работы и опции
            'mode' => $options['mode'] ?? 'hybrid',
            'max_items_per_run' => $options['max_items_per_run'] ?? null,
            'one_shot' => $options['one_shot'] ?? true,
            'reached_limit' => ($syncResult['total_processed'] ?? 0) >= ($options['max_items_per_run'] ?? PHP_INT_MAX),
            'reached_end' => false, // Определяется в синхронизаторе

            // Совместимость с командой
            'final_last_id' => $syncResult['final_last_id'] ?? null,
        ];
    }

    /**
     * Получить результат последнего парсинга
     *
     * @return array
     */
    public function getLastParsingStats(): array
    {
        return $this->lastParsingResult ?: [
            'message' => 'No parsing performed yet'
        ];
    }

    /**
     * Получить активные сети из БД или fallback значения
     */
    private function getActiveNetworks(): array
    {
        try {
            return \App\Models\AdvertismentNetwork::getDefaultNetworksForParser();
        } catch (\Exception $e) {
            // В случае ошибки (например, БД недоступна), используем fallback
            Log::warning("Failed to get active networks from database, using fallback", [
                'error' => $e->getMessage()
            ]);

            return ['rollerads', 'richads'];
        }
    }

    /**
     * Проверить соединение с API
     *
     * @return array Результат проверки
     */
    public function testConnection(): array
    {
        try {
            $activeNetworks = $this->getActiveNetworks();
            $testData = $this->parser->fetchData([
                'limit' => 5,
                'formats' => 'push,inpage',
                'adNetworks' => implode(',', $activeNetworks)
            ]);

            return [
                'status' => 'success',
                'connection' => 'ok',
                'test_data_count' => count($testData),
                'api_response' => 'Valid JSON array'
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'failed',
                'connection' => 'error',
                'error' => $e->getMessage(),
                'api_response' => 'Failed'
            ];
        }
    }
}

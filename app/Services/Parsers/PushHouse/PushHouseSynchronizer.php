<?php

declare(strict_types=1);

namespace App\Services\Parsers\PushHouse;

use App\Http\DTOs\Parsers\PushHouseCreativeDTO;
use App\Services\Parsers\Exceptions\ParserException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Push.House Synchronizer
 * 
 * Ключевой компонент для синхронизации данных Push.House с базой данных
 * Выполняет сравнение списков ID, определение новых и деактивированных объявлений,
 * координацию DB транзакций и пакетные операции INSERT/UPDATE
 * 
 * @package App\Services\Parsers\PushHouse
 * @author SeniorSoftwareEngineer
 * @version 1.0.0
 */
class PushHouseSynchronizer
{
    private const SOURCE_NAME = 'push_house';
    private const BATCH_SIZE = 100; // Размер пакета для массовых операций

    /**
     * Результат синхронизации
     */
    public readonly array $syncResult;

    /**
     * Синхронизировать креативы с базой данных
     *
     * @param Collection<PushHouseCreativeDTO> $apiCreatives Креативы от API
     * @return array Результат синхронизации с статистикой
     * @throws ParserException
     */
    public function synchronize(Collection $apiCreatives): array
    {
        Log::info("PushHouse Sync: Starting synchronization", [
            'api_creatives_count' => $apiCreatives->count()
        ]);

        try {
            // Шаг 1: Извлечь ID из API данных
            $apiIds = $this->extractApiIds($apiCreatives);

            // Шаг 2: Получить существующие ID из БД
            $dbIds = $this->getExistingIds();

            // Шаг 3: Определить новые и деактивированные объявления
            $newIds = $this->findNewIds($apiIds, $dbIds);
            $deactivatedIds = $this->findDeactivatedIds($apiIds, $dbIds);

            // Шаг 4: Выполнить синхронизацию в транзакции
            $result = $this->performSynchronization($apiCreatives, $newIds, $deactivatedIds);

            Log::info("PushHouse Sync: Synchronization completed", $result);

            return $this->syncResult = $result;
        } catch (\Exception $e) {
            Log::error("PushHouse Sync: Synchronization failed", [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            throw new ParserException("Synchronization failed: " . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Извлечь уникальные external_id из коллекции DTO
     *
     * @param Collection<PushHouseCreativeDTO> $apiCreatives
     * @return array Массив уникальных external_id
     */
    private function extractApiIds(Collection $apiCreatives): array
    {
        $apiIds = $apiCreatives
            ->map(fn($dto) => $dto->externalId)
            ->unique()
            ->values()
            ->toArray();

        Log::debug("PushHouse Sync: API IDs extracted", [
            'count' => count($apiIds),
            'sample' => array_slice($apiIds, 0, 5) // Первые 5 для примера
        ]);

        return $apiIds;
    }

    /**
     * Получить существующие external_id из БД для Push.House
     *
     * @return array Массив существующих external_id
     */
    public function getExistingIds(): array
    {
        $dbIds = DB::table('creatives')
            ->join('ad_sources', 'creatives.source_id', '=', 'ad_sources.id')
            ->where('ad_sources.source_name', self::SOURCE_NAME)
            ->pluck('creatives.external_id')
            ->toArray();

        Log::debug("PushHouse Sync: DB IDs retrieved", [
            'count' => count($dbIds),
            'sample' => array_slice($dbIds, 0, 5) // Первые 5 для примера
        ]);

        return $dbIds;
    }

    /**
     * Найти новые ID (есть в API, нет в БД)
     *
     * @param array $apiIds ID от API
     * @param array $dbIds ID из БД
     * @return array Новые ID
     */
    private function findNewIds(array $apiIds, array $dbIds): array
    {
        $newIds = array_diff($apiIds, $dbIds);

        Log::info("PushHouse Sync: New IDs identified", [
            'count' => count($newIds),
            'sample' => array_slice($newIds, 0, 5)
        ]);

        return $newIds;
    }

    /**
     * Найти деактивированные ID (есть в БД, нет в API)
     *
     * @param array $apiIds ID от API
     * @param array $dbIds ID из БД
     * @return array Деактивированные ID
     */
    private function findDeactivatedIds(array $apiIds, array $dbIds): array
    {
        $deactivatedIds = array_diff($dbIds, $apiIds);

        Log::info("PushHouse Sync: Deactivated IDs identified", [
            'count' => count($deactivatedIds),
            'sample' => array_slice($deactivatedIds, 0, 5)
        ]);

        return $deactivatedIds;
    }

    /**
     * Выполнить синхронизацию в транзакции
     *
     * @param Collection<PushHouseCreativeDTO> $apiCreatives
     * @param array $newIds
     * @param array $deactivatedIds
     * @return array Результат синхронизации
     * @throws ParserException
     */
    private function performSynchronization(Collection $apiCreatives, array $newIds, array $deactivatedIds): array
    {
        return DB::transaction(function () use ($apiCreatives, $newIds, $deactivatedIds) {
            $result = [
                'new_creatives' => 0,
                'deactivated_creatives' => 0,
                'unchanged_creatives' => 0,
                'new_creative_ids' => [],
                'deactivated_creative_ids' => [],
                'errors' => []
            ];

            try {
                // Шаг 1: Вставка новых креативов
                if (!empty($newIds)) {
                    $insertResult = $this->insertNewCreatives($apiCreatives, $newIds);
                    $result['new_creatives'] = $insertResult['count'];
                    $result['new_creative_ids'] = $insertResult['local_ids'];
                }

                // Шаг 2: Деактивация удаленных креативов
                if (!empty($deactivatedIds)) {
                    $deactivateResult = $this->deactivateCreatives($deactivatedIds);
                    $result['deactivated_creatives'] = $deactivateResult['count'];
                    $result['deactivated_creative_ids'] = $deactivateResult['local_ids'];
                }

                // Шаг 3: Подсчет неизменных креативов
                $result['unchanged_creatives'] = $this->countUnchangedCreatives($newIds, $deactivatedIds);

                Log::info("PushHouse Sync: Transaction completed successfully", $result);

                return $result;
            } catch (\Exception $e) {
                Log::error("PushHouse Sync: Transaction failed", [
                    'error' => $e->getMessage()
                ]);
                throw $e;
            }
        });
    }

    /**
     * Вставить новые креативы в БД
     *
     * @param Collection<PushHouseCreativeDTO> $apiCreatives
     * @param array $newIds
     * @return array Результат вставки
     */
    private function insertNewCreatives(Collection $apiCreatives, array $newIds): array
    {
        // Фильтруем только новые креативы
        $newCreatives = $apiCreatives->filter(fn($dto) => in_array($dto->externalId, $newIds));

        // Преобразуем в формат БД
        $creativesData = $newCreatives->map(fn($dto) => $dto->toDatabase())->toArray();

        Log::info("PushHouse Sync: Inserting new creatives", [
            'count' => count($creativesData)
        ]);

        // Выполняем пакетную вставку
        $insertedCount = 0;
        foreach (array_chunk($creativesData, self::BATCH_SIZE) as $batch) {
            DB::table('creatives')->insert($batch);
            $insertedCount += count($batch);
        }

        // Получаем внутренние ID вставленных записей
        $localIds = $this->getLocalIdsByExternalIds($newIds);

        Log::info("PushHouse Sync: New creatives inserted", [
            'inserted_count' => $insertedCount,
            'local_ids_count' => count($localIds)
        ]);

        return [
            'count' => $insertedCount,
            'local_ids' => $localIds
        ];
    }

    /**
     * Деактивировать креативы в БД
     *
     * @param array $deactivatedIds
     * @return array Результат деактивации
     */
    private function deactivateCreatives(array $deactivatedIds): array
    {
        Log::info("PushHouse Sync: Deactivating creatives", [
            'count' => count($deactivatedIds)
        ]);

        // Получаем внутренние ID перед обновлением
        $localIds = $this->getLocalIdsByExternalIds($deactivatedIds);

        // Выполняем пакетное обновление статуса
        $updatedCount = 0;
        foreach (array_chunk($deactivatedIds, self::BATCH_SIZE) as $batch) {
            $updated = DB::table('creatives')
                ->join('ad_sources', 'creatives.source_id', '=', 'ad_sources.id')
                ->where('ad_sources.source_name', self::SOURCE_NAME)
                ->whereIn('creatives.external_id', $batch)
                ->update([
                    'creatives.status' => 'inactive',
                    'creatives.updated_at' => now()
                ]);

            $updatedCount += $updated;
        }

        Log::info("PushHouse Sync: Creatives deactivated", [
            'updated_count' => $updatedCount,
            'local_ids_count' => count($localIds)
        ]);

        return [
            'count' => $updatedCount,
            'local_ids' => $localIds
        ];
    }

    /**
     * Получить внутренние ID по внешним ID
     *
     * @param array $externalIds
     * @return array Массив внутренних ID
     */
    private function getLocalIdsByExternalIds(array $externalIds): array
    {
        if (empty($externalIds)) {
            return [];
        }

        return DB::table('creatives')
            ->join('ad_sources', 'creatives.source_id', '=', 'ad_sources.id')
            ->where('ad_sources.source_name', self::SOURCE_NAME)
            ->whereIn('creatives.external_id', $externalIds)
            ->pluck('creatives.id')
            ->toArray();
    }

    /**
     * Подсчитать количество неизменных креативов
     *
     * @param array $newIds
     * @param array $deactivatedIds
     * @return int
     */
    private function countUnchangedCreatives(array $newIds, array $deactivatedIds): int
    {
        $changedIds = array_merge($newIds, $deactivatedIds);

        $unchangedCount = DB::table('creatives')
            ->join('ad_sources', 'creatives.source_id', '=', 'ad_sources.id')
            ->where('ad_sources.source_name', self::SOURCE_NAME)
            ->whereNotIn('creatives.external_id', $changedIds)
            ->count();

        return $unchangedCount;
    }

    /**
     * Получить статистику синхронизации
     *
     * @return array
     */
    public function getLastSyncStats(): array
    {
        return $this->syncResult ?? [
            'message' => 'No synchronization performed yet'
        ];
    }

    /**
     * Проверить целостность данных после синхронизации
     *
     * @return array Результат проверки
     */
    public function validateSyncIntegrity(): array
    {
        $totalInDb = DB::table('creatives')
            ->join('ad_sources', 'creatives.source_id', '=', 'ad_sources.id')
            ->where('ad_sources.source_name', self::SOURCE_NAME)
            ->count();

        $activeInDb = DB::table('creatives')
            ->join('ad_sources', 'creatives.source_id', '=', 'ad_sources.id')
            ->where('ad_sources.source_name', self::SOURCE_NAME)
            ->where('creatives.status', 'active')
            ->count();

        $inactiveInDb = DB::table('creatives')
            ->join('ad_sources', 'creatives.source_id', '=', 'ad_sources.id')
            ->where('ad_sources.source_name', self::SOURCE_NAME)
            ->where('creatives.status', 'inactive')
            ->count();

        return [
            'total_creatives' => $totalInDb,
            'active_creatives' => $activeInDb,
            'inactive_creatives' => $inactiveInDb,
            'integrity_check' => ($activeInDb + $inactiveInDb) === $totalInDb
        ];
    }

    /**
     * Очистка старых неактивных креативов (опционально)
     *
     * @param int $daysOld Количество дней для считания креатива устаревшим
     * @return int Количество удаленных записей
     */
    public function cleanupOldCreatives(int $daysOld = 30): int
    {
        $deletedCount = DB::table('creatives')
            ->join('ad_sources', 'creatives.source_id', '=', 'ad_sources.id')
            ->where('ad_sources.source_name', self::SOURCE_NAME)
            ->where('creatives.status', 'inactive')
            ->where('creatives.updated_at', '<', now()->subDays($daysOld))
            ->delete();

        Log::info("PushHouse Sync: Old creatives cleaned up", [
            'deleted_count' => $deletedCount,
            'days_old' => $daysOld
        ]);

        return $deletedCount;
    }
}

<?php

namespace App\Console\Commands\Parsers;

use App\Models\Creative;
use App\Services\Parsers\CreativeImageValidator;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Команда для очистки невалидных креативов из базы данных
 *
 * Проверяет изображения существующих креативов и помечает/удаляет
 * те, которые имеют недоступные или невалидные изображения
 *
 * @package App\Console\Commands\Parsers
 * @author SeniorSoftwareEngineer
 */
class CleanupInvalidCreativesCommand extends Command
{
    /**
     * Название и параметры команды
     */
    protected $signature = 'parser:cleanup-invalid-creatives 
                           {--dry-run : Just show what would be affected}
                           {--mark-invalid : Mark as invalid instead of deleting}
                           {--batch-size=100 : Number of creatives to process at once}
                           {--source= : Only process creatives from specific source}
                           {--days= : Only process creatives from last N days}';

    /**
     * Описание команды
     */
    protected $description = 'Clean up invalid creatives with broken or missing images';

    private CreativeImageValidator $validator;

    /**
     * Выполняет команду
     */
    public function handle(): int
    {
        $this->info('🧹 Starting cleanup of invalid creatives...');

        $this->validator = new CreativeImageValidator();

        $isDryRun = $this->option('dry-run');
        $markInvalid = $this->option('mark-invalid');
        $batchSize = (int) $this->option('batch-size');
        $source = $this->option('source');
        $days = $this->option('days') ? (int) $this->option('days') : null;

        try {
            $query = $this->buildQuery($source, $days);
            $totalCount = $query->count();

            if ($totalCount === 0) {
                $this->info('✅ No creatives found to process.');
                return Command::SUCCESS;
            }

            $this->info("📊 Found {$totalCount} creatives to process");

            if ($isDryRun) {
                $this->warn('🔍 DRY RUN MODE - No changes will be made');
            }

            $processedCount = 0;
            $invalidCount = 0;
            $deletedCount = 0;
            $markedInvalidCount = 0;

            $progressBar = $this->output->createProgressBar($totalCount);

            $query->chunk($batchSize, function ($creatives) use (
                &$processedCount,
                &$invalidCount,
                &$deletedCount,
                &$markedInvalidCount,
                $isDryRun,
                $markInvalid,
                $progressBar
            ) {
                foreach ($creatives as $creative) {
                    $result = $this->processCreative($creative, $isDryRun, $markInvalid);

                    $processedCount++;

                    if (!$result['valid']) {
                        $invalidCount++;
                        if ($result['deleted']) {
                            $deletedCount++;
                        } elseif ($result['marked_invalid']) {
                            $markedInvalidCount++;
                        }
                    }

                    $progressBar->advance();
                }
            });

            $progressBar->finish();
            $this->line('');

            // Результаты
            $this->displayResults($processedCount, $invalidCount, $deletedCount, $markedInvalidCount, $isDryRun);

            return Command::SUCCESS;
        } catch (\Exception $e) {
            $this->error('❌ Error during cleanup: ' . $e->getMessage());
            Log::error('Creative cleanup failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return Command::FAILURE;
        }
    }

    /**
     * Строит запрос для получения креативов
     */
    private function buildQuery(?string $source, ?int $days)
    {
        $query = Creative::query()
            ->whereNotNull('icon_url')
            ->orWhereNotNull('main_image_url');

        if ($source) {
            $query->whereHas('source', function ($q) use ($source) {
                $q->where('source_name', 'like', "%{$source}%");
            });
        }

        if ($days) {
            $query->where('created_at', '>=', now()->subDays($days));
        }

        return $query->orderBy('id');
    }

    /**
     * Обрабатывает один креатив
     */
    private function processCreative(Creative $creative, bool $isDryRun, bool $markInvalid): array
    {
        $imageUrls = array_filter([
            $creative->icon_url,
            $creative->main_image_url
        ]);

        if (empty($imageUrls)) {
            return [
                'valid' => false,
                'reason' => 'No image URLs',
                'deleted' => false,
                'marked_invalid' => false
            ];
        }

        try {
            $validationResults = $this->validator->validateImages($imageUrls);

            // Проверяем есть ли хотя бы одно валидное изображение
            $hasValidImage = false;
            $errorReasons = [];

            foreach ($validationResults as $url => $result) {
                if ($result['valid']) {
                    $hasValidImage = true;
                    break;
                } else {
                    $errorReasons[] = "URL: {$url} - Error: {$result['error']}";
                }
            }

            if ($hasValidImage) {
                return [
                    'valid' => true,
                    'deleted' => false,
                    'marked_invalid' => false
                ];
            }

            // Креатив невалиден
            $reason = implode('; ', $errorReasons);

            if (!$isDryRun) {
                if ($markInvalid) {
                    $creative->update([
                        'is_valid' => false,
                        'validation_error' => $reason
                    ]);
                    return [
                        'valid' => false,
                        'reason' => $reason,
                        'deleted' => false,
                        'marked_invalid' => true
                    ];
                } else {
                    $creative->delete();
                    return [
                        'valid' => false,
                        'reason' => $reason,
                        'deleted' => true,
                        'marked_invalid' => false
                    ];
                }
            }

            return [
                'valid' => false,
                'reason' => $reason,
                'deleted' => false,
                'marked_invalid' => false
            ];
        } catch (\Exception $e) {
            Log::warning('Failed to validate creative images', [
                'creative_id' => $creative->id,
                'error' => $e->getMessage()
            ]);

            return [
                'valid' => false,
                'reason' => 'Validation error: ' . $e->getMessage(),
                'deleted' => false,
                'marked_invalid' => false
            ];
        }
    }

    /**
     * Отображает результаты обработки
     */
    private function displayResults(
        int $processedCount,
        int $invalidCount,
        int $deletedCount,
        int $markedInvalidCount,
        bool $isDryRun
    ): void {
        $this->line('');
        $this->line('📈 <fg=cyan>Cleanup Results</fg>');
        $this->line('═══════════════════');

        $validCount = $processedCount - $invalidCount;

        $this->table(
            ['Metric', 'Count', 'Percentage'],
            [
                ['Total Processed', number_format($processedCount), '100%'],
                ['Valid Creatives', number_format($validCount), round(($validCount / $processedCount) * 100, 1) . '%'],
                ['Invalid Creatives', number_format($invalidCount), round(($invalidCount / $processedCount) * 100, 1) . '%'],
                ['Deleted', number_format($deletedCount), $deletedCount > 0 ? round(($deletedCount / $invalidCount) * 100, 1) . '%' : '0%'],
                ['Marked Invalid', number_format($markedInvalidCount), $markedInvalidCount > 0 ? round(($markedInvalidCount / $invalidCount) * 100, 1) . '%' : '0%'],
            ]
        );

        if ($isDryRun) {
            $this->line('');
            $this->warn('⚠️  This was a DRY RUN - no actual changes were made');
            $this->line('   Run without --dry-run to apply changes');
        } else {
            $this->line('');
            if ($deletedCount > 0) {
                $this->info("✅ Successfully deleted {$deletedCount} invalid creatives");
            }
            if ($markedInvalidCount > 0) {
                $this->info("✅ Successfully marked {$markedInvalidCount} creatives as invalid");
            }
        }

        // Рекомендации
        if ($invalidCount > 0) {
            $invalidPercentage = round(($invalidCount / $processedCount) * 100, 1);
            $this->line('');
            $this->line('💡 <fg=yellow>Recommendations</fg>');

            if ($invalidPercentage > 30) {
                $this->line("   🔴 High percentage of invalid creatives ({$invalidPercentage}%)");
                $this->line("   🔧 Consider reviewing the data source quality");
                $this->line("   🔧 Increase image validation strictness");
            } elseif ($invalidPercentage > 10) {
                $this->line("   🟡 Moderate percentage of invalid creatives ({$invalidPercentage}%)");
                $this->line("   🔧 Monitor data source for quality degradation");
            } else {
                $this->line("   🟢 Low percentage of invalid creatives ({$invalidPercentage}%)");
                $this->line("   ✅ Data source quality appears good");
            }
        }
    }
}

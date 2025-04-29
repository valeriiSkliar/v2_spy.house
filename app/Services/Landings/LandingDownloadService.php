<?php

namespace App\Services\Landings;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;
use ZipArchive;
use Illuminate\Support\Str;
use App\Models\WebsiteDownloadMonitor;

class LandingDownloadService
{
    public function download(WebsiteDownloadMonitor $landing): StreamedResponse|JsonResponse
    {
        $tempDir = null;
        try {
            $folderPath = 'private/website-downloads/' . basename($landing->output_path);

            Log::info('Starting download process for landing', [
                'landing_id' => $landing->id,
                'folder_path' => $folderPath,
                'storage_path' => Storage::path($folderPath),
                'disk' => config('filesystems.default')
            ]);

            if (!Storage::exists($folderPath)) {
                Log::warning('Source folder not found', [
                    'landing_id' => $landing->id,
                    'folder_path' => $folderPath
                ]);
                $landing->update([
                    'status' => 'failed',
                    'error' => 'Source folder not found. The download may have failed or the folder was deleted.'
                ]);
                return response()->json([
                    'status' => 'error',
                    'message' => 'landingsPage.sourceFolderNotFound.description' // [
                    //     'type' => 'error',
                    //     'title' => 'landingsPage.sourceFolderNotFound.title',
                    //     'description' => 'landingsPage.sourceFolderNotFound.description',
                    //     'duration' => 3000
                    // ]
                ], 404);
            }

            // Создаём временную директорию
            $tempDir = storage_path('app/temp');
            if (!file_exists($tempDir)) {
                mkdir($tempDir, 0755, true);
            }
            $tempSubDir = $tempDir . '/' . Str::random(40);
            mkdir($tempSubDir, 0755, true);

            // Генерируем имя ZIP-файла и его путь
            $zipFileName = Str::random(40) . '.zip';
            $zipPath = $tempSubDir . '/' . $zipFileName;

            // Создаём ZIP-архив
            $zip = new ZipArchive();
            if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
                throw new \Exception('Unable to create ZIP archive at: ' . $zipPath);
            }

            // Получаем файлы и директории
            $files = Storage::allFiles($folderPath);
            $directories = Storage::allDirectories($folderPath);

            if (empty($files) && empty($directories)) {
                Log::warning('No files or directories found in folder', [
                    'folder_path' => $folderPath
                ]);
                throw new \Exception('No files found in the source folder');
            }

            // Добавляем файлы в архив с сохранением структуры
            foreach ($files as $file) {
                $relativePath = substr($file, strlen($folderPath) + 1);
                $zip->addFile(storage_path('app/' . $file), $relativePath);
            }

            // Добавляем пустые директории
            foreach ($directories as $directory) {
                $relativePath = substr($directory, strlen($folderPath) + 1);
                $zip->addEmptyDir($relativePath);
            }

            $zip->close();

            Log::info('Successfully created ZIP file', [
                'zip_size' => filesize($zipPath),
                'file_count' => count($files)
            ]);

            // Регистрируем shutdown-функцию для очистки временных файлов
            register_shutdown_function(function () use ($tempSubDir, $zipPath) {
                if (file_exists($zipPath)) {
                    unlink($zipPath);
                }
                if (is_dir($tempSubDir)) {
                    rmdir($tempSubDir);
                }
            });

            // Возвращаем потоковый ответ
            return response()->stream(
                function () use ($zipPath) {
                    $stream = fopen($zipPath, 'rb');
                    fpassthru($stream);
                    fclose($stream);
                },
                200,
                [
                    'Content-Type' => 'application/zip',
                    'Content-Disposition' => 'attachment; filename="landing-' . $landing->id . '.zip"',
                ]
            );
        } catch (\Exception $e) {
            if ($tempDir && file_exists($tempDir)) {
                $this->removeDirectory($tempDir);
            }
            Log::error('Download failed for landing', [
                'landing_id' => $landing->id,
                'error_message' => $e->getMessage(),
                'error_trace' => $e->getTraceAsString(),
                'folder_path' => $folderPath ?? null
            ]);

            return response()->json([
                'message' => 'An error occurred while preparing your download: ' . $e->getMessage(),
                'status' => 'error'
            ], 500);
        }
    }

    /**
     * Рекурсивное удаление директории и её содержимого
     */
    private function removeDirectory(string $dir): void
    {
        if (!file_exists($dir)) {
            return;
        }

        $files = array_diff(scandir($dir), ['.', '..']);
        foreach ($files as $file) {
            $path = $dir . '/' . $file;
            is_dir($path) ? $this->removeDirectory($path) : unlink($path);
        }
        rmdir($dir);
    }
}

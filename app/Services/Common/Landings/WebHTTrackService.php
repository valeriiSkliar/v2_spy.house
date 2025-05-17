<?php

namespace App\Services\Common\Landings;

use Symfony\Component\Process\Process;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;

class WebHTTrackService
{
    /**
     * Default timeout in seconds
     */
    private const DEFAULT_TIMEOUT = 3600;
    private static $timerWrapper;

    /**
     * Default HTTrack options
     */
    private array $defaultOptions;

    public function __construct()
    {
        self::$timerWrapper = config('httrack.timer_wrapper');
        $this->defaultOptions = [
            '--quiet',                 // Тихий режим
            '--robots=0',              // Игнорировать robots.txt
            '--connection-per-second=2', // Ограничение количества подключений в секунду
            '-v',                      // Подробный вывод
            '-r3',                     // Ограничение глубины ссылок до трёх
            '--stay-on-same-domain',   // Оставаться в пределах одного домена
            '--wrapper',
            self::$timerWrapper, // Указываем путь к mytimer.so
        ];
    }

    /**
     * Download a website using HTTrack
     *
     * @param string $url The URL to download
     * @param string $outputPath The path where to save the downloaded files
     * @param array $options Additional HTTrack options
     * @return Process
     * @throws ProcessFailedException
     * @throws \InvalidArgumentException
     */
    public function downloadSite(string $url, string $outputPath, array $options = []): Process
    {
        if (!$this->validateUrl($url)) {
            throw new \InvalidArgumentException('Invalid URL provided');
        }

        // Create directory in private storage
        $directory = 'private/website-downloads/' . basename($outputPath);
        Storage::makeDirectory($directory, 0750);
        $fullPath = Storage::path($directory);

        // Replace template variables with dummy values for HTTrack
        $processedUrl = preg_replace('/\{[^}]+\}/', 'dummy', $url);

        // Build HTTrack command with default options
        $command = array_merge(
            [
                'httrack',
                $processedUrl,  // Use the processed URL
                '-O',
                $fullPath,
            ],
            $this->defaultOptions,
            $options
        );

        Log::info('Starting HTTrack download', [
            'command' => $command,
            'storage_path' => $directory,
            'full_path' => $fullPath,
            'original_url' => $url,
            'processed_url' => $processedUrl
        ]);

        $process = new Process($command);
        $process->setTimeout(self::DEFAULT_TIMEOUT);

        // Start the process
        $process->start();

        // Wait for the process to finish
        $process->wait(function ($type, $buffer) use ($process) {
            if (Process::ERR === $type) {
                Log::warning('HTTrack Error: ' . $buffer);
            } else {
                Log::info('HTTrack Output: ' . $buffer);
                // Check for the specific message indicating time limit reached
                if (strpos($buffer, 'Time limit of 3 minutes reached. Stopping mirror gracefully.') !== false) {
                    // Stop the process gracefully
                    $process->stop(0, SIGTERM);
                    Log::info('HTTrack process stopped gracefully due to time limit.');
                }
            }
        });

        if (!$process->isSuccessful()) {
            throw new ProcessFailedException($process);
        }

        Log::info('Website download completed successfully', [
            'url' => $url,
            'storage_path' => $directory,
            'full_path' => $fullPath
        ]);

        return $process;
    }


    /**
     * Get the download progress
     *
     * @param string $outputPath Path to the download directory
     * @return int Progress percentage (0-100)
     */
    public function getDownloadProgress(string $outputPath): int
    {
        // TODO: Implement actual progress tracking
        return 100;
    }

    /**
     * Validate a URL
     */
    private function validateUrl(string $url): bool
    {
        // Replace template variables with dummy values for validation
        $dummyUrl = preg_replace('/\{[^}]+\}/', 'dummy', $url);

        return Validator::make(
            ['url' => $dummyUrl],
            ['url' => 'required|url']
        )->passes();
    }
}

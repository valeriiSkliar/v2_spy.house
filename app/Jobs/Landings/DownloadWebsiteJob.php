<?php

namespace App\Jobs\Landings;

use App\Models\Frontend\Landings\WebsiteDownloadMonitor;
use App\Notifications\Landings\WebsiteDownloadStatus;
use App\Services\Landings\WebHTTrackService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Bus\Queueable;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class DownloadWebsiteJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of times the job may be attempted.
     *
     * @var int
     */
    public $tries = 1;

    /**
     * The maximum number of seconds the job can run.
     *
     * @var int
     */
    public $timeout = 3600;

    /**
     * Create a new job instance.
     */
    public function __construct(
        private readonly string $url,
        private readonly string $uuid,
        private readonly int $userId,
        private readonly array $options = []
    ) {}

    /**
     * Execute the job.
     */
    public function handle(WebHTTrackService $webHTTrackService): void
    {
        $outputPath = "private/website-downloads/{$this->uuid}";
        $monitor = WebsiteDownloadMonitor::where('output_path', $outputPath)->first();

        if (!$monitor) {
            Log::error('Monitor not found for download', [
                'url' => $this->url,
                'output_path' => $outputPath,
                'user_id' => $this->userId,
            ]);
            return;
        }

        try {
            Log::info('Starting website download job', [
                'url' => $this->url,
                'output_path' => $outputPath,
                'user_id' => $this->userId,
            ]);

            $monitor->start();

            // Start the download
            $process = $webHTTrackService->downloadSite(
                $this->url,
                $this->uuid,
                $this->options
            );

            // Update progress periodically
            while ($process->isRunning()) {
                $progress = $webHTTrackService->getDownloadProgress(Storage::path($outputPath));
                $monitor->updateProgress($progress);
                sleep(5); // Wait 5 seconds before next check
            }

            if (!$process->isSuccessful()) {
                throw new \RuntimeException('Download process failed: ' . $process->getErrorOutput());
            }

            // Check for the presence of index.html or similar files
            $indexFiles = ['index.html', 'index.htm', 'default.html', 'default.htm'];
            $indexFound = false;
            $downloadPath = Storage::path($outputPath);

            foreach ($indexFiles as $indexFile) {
                if (file_exists($downloadPath . '/' . $indexFile)) {
                    $indexFound = true;
                    break;
                }
            }

            if (!$indexFound) {
                throw new \RuntimeException('Download incomplete: No index HTML file found in the downloaded content.');
            }

            // Verify the index file has content
            $foundIndexFile = null;
            foreach ($indexFiles as $indexFile) {
                $filePath = $downloadPath . '/' . $indexFile;
                if (file_exists($filePath) && filesize($filePath) > 0) {
                    $foundIndexFile = $filePath;
                    break;
                }
            }

            if (!$foundIndexFile) {
                throw new \RuntimeException('Download incomplete: Index HTML file is empty.');
            }

            // Check if the index file contains basic HTML structure
            $indexContent = file_get_contents($foundIndexFile);
            if (!preg_match('/<html.*>.*<\/html>/is', $indexContent)) {
                throw new \RuntimeException('Download incomplete: Index HTML file does not contain valid HTML structure.');
            }

            // Mark as completed with 100% progress
            $monitor->updateProgress(100);

            // Notify user of completion
            $monitor->user->notify(new WebsiteDownloadStatus(
                $this->url,
                'completed'
            ));

            Log::info('Website download job completed', [
                'url' => $this->url,
                'output_path' => $outputPath,
                'user_id' => $this->userId,
            ]);
        } catch (\Exception $e) {
            Log::error('Website download job failed with exception', [
                'url' => $this->url,
                'exception' => $e->getMessage(),
                'user_id' => $this->userId,
            ]);

            $monitor->markAsFailed($e->getMessage());

            // Clean up files on failure
            Storage::deleteDirectory($outputPath);

            // Notify user of failure
            $monitor->user->notify(new WebsiteDownloadStatus(
                $this->url,
                'failed',
                $e->getMessage()
            ));

            throw $e;
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('Website download job failed with exception', [
            'url' => $this->url,
            'exception' => $exception->getMessage(),
            'user_id' => $this->userId,
        ]);
    }
}

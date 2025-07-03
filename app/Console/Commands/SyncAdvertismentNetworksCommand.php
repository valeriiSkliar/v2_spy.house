<?php

namespace App\Console\Commands;

use App\Jobs\SyncAdvertismentNetworksJob;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class SyncAdvertismentNetworksCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'advertisment-networks:sync 
                            {--queue : Run the job in the background queue}
                            {--force : Force synchronization even if recently executed}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Synchronize advertisement networks from FeedHouse API';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Starting advertisement networks synchronization...');

        try {
            if ($this->option('queue')) {
                // Запускаем в фоновой очереди
                SyncAdvertismentNetworksJob::dispatch();
                $this->info('Synchronization job has been dispatched to the queue.');

                Log::info('SyncAdvertismentNetworksCommand dispatched to queue', [
                    'command' => $this->signature,
                    'options' => $this->options(),
                ]);
            } else {
                // Запускаем синхронно
                $this->info('Running synchronization synchronously...');

                $job = new SyncAdvertismentNetworksJob();
                $job->handle();

                $this->info('Synchronization completed successfully!');
                $this->line('Check the logs for details about any new networks found.');

                Log::info('SyncAdvertismentNetworksCommand completed successfully', [
                    'command' => $this->signature,
                    'options' => $this->options(),
                ]);
            }

            return Command::SUCCESS;
        } catch (\Exception $e) {
            $this->error('Synchronization failed: ' . $e->getMessage());

            if ($this->getOutput()->isVerbose()) {
                $this->line($e->getTraceAsString());
            }

            Log::error('SyncAdvertismentNetworksCommand failed', [
                'command' => $this->signature,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return Command::FAILURE;
        }
    }

    /**
     * Get the console command arguments.
     */
    protected function getArguments(): array
    {
        return [];
    }

    /**
     * Get the console command options.
     */
    protected function getOptions(): array
    {
        return [
            ['queue', null, \Symfony\Component\Console\Input\InputOption::VALUE_NONE, 'Run the job in the background queue'],
            ['force', null, \Symfony\Component\Console\Input\InputOption::VALUE_NONE, 'Force synchronization even if recently executed'],
        ];
    }
}

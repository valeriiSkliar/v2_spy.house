<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

class ExpireTrialPeriods extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'trial:expire {--queue : Run via queue instead of directly}';

    /**
     * The console command description.
     */
    protected $description = 'Expire trial periods for users whose trial has ended';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        if ($this->option('queue')) {
            \App\Jobs\ExpireTrialPeriodsJob::dispatch();
            $this->info('Trial expiration job dispatched to queue.');
            return Command::SUCCESS;
        }

        $expiredUsers = User::where('is_trial_period', true)
            ->where('subscription_time_end', '<', now())
            ->get();

        $count = 0;

        foreach ($expiredUsers as $user) {
            $user->update([
                'is_trial_period' => false,
                'subscription_time_start' => null,
                'subscription_time_end' => null,
            ]);

            $count++;
        }

        $this->info("Expired trial periods for {$count} users.");

        return Command::SUCCESS;
    }
}

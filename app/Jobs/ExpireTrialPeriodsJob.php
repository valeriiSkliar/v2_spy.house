<?php

namespace App\Jobs;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ExpireTrialPeriodsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Execute the job.
     */
    public function handle(): void
    {
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

            Log::info('Trial period expired for user', [
                'user_id' => $user->id,
                'email' => $user->email,
                'trial_end' => $user->subscription_time_end,
            ]);

            $count++;
        }

        if ($count > 0) {
            Log::info("Expired trial periods for {$count} users");
        }
    }
}

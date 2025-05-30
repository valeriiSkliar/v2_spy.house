<?php

namespace App\Jobs;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class ProcessUserRegistrationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected User $user;
    protected array $metadata;

    /**
     * Create a new job instance.
     */
    public function __construct(User $user, array $metadata = [])
    {
        $this->user = $user;
        $this->metadata = $metadata;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $cacheKey = 'user_registration_job:' . $this->user->id;

        // Защита от дублирования в очереди
        if (Cache::has($cacheKey)) {
            Log::info('User registration job already processed, skipping', [
                'user_id' => $this->user->id
            ]);
            return;
        }

        Cache::put($cacheKey, true, now()->addMinutes(30));

        Log::info('Processing user registration job', [
            'user_id' => $this->user->id,
            'email' => $this->user->email,
            'metadata' => $this->metadata
        ]);

        try {
            // Отправляем уведомления
            if (!$this->user->hasVerifiedEmail()) {
                $this->user->sendEmailVerificationNotification();
            }

            $this->user->sendWelcomeNotification();
            $this->user->sendWelcomeInAppNotification();

            Log::info('User registration job completed successfully', [
                'user_id' => $this->user->id
            ]);
        } catch (\Exception $e) {
            Cache::forget($cacheKey);
            Log::error('User registration job failed', [
                'user_id' => $this->user->id,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('User registration job failed permanently', [
            'user_id' => $this->user->id,
            'error' => $exception->getMessage()
        ]);
    }
}

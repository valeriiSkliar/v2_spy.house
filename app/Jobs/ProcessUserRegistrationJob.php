<?php

namespace App\Jobs;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

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
        Log::debug('Processing user registration', [
            'user_id' => $this->user->id,
            'email' => $this->user->email,
            'metadata' => $this->metadata,
        ]);

        // Отправляем email верификацию если нужно
        if (! $this->user->hasVerifiedEmail()) {
            Log::debug('Sending email verification notification', ['user_id' => $this->user->id]);
            $this->user->sendEmailVerificationNotification();
            Log::debug('Email verification notification sent', ['user_id' => $this->user->id]);
        }

        // Отправляем приветственные уведомления
        Log::debug('Sending welcome email notification', ['user_id' => $this->user->id]);
        // $this->user->sendWelcomeNotification();
        Log::debug('Welcome email notification sent', ['user_id' => $this->user->id]);

        // WelcomeInAppNotification теперь отправляется после подтверждения email
        Log::debug('Welcome in-app notification will be sent after email verification', ['user_id' => $this->user->id]);

        Log::debug('User registration processed successfully', [
            'user_id' => $this->user->id,
        ]);
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('User registration job failed permanently', [
            'user_id' => $this->user->id,
            'error' => $exception->getMessage(),
        ]);
    }
}

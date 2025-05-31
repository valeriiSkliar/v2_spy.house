<?php

namespace App\Jobs;

use App\Models\User;
use App\Notifications\Profile\PasswordUpdateConfirmationNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SendPasswordUpdateConfirmationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected User $user;

    protected int $verificationCode;

    public function __construct(User $user, int $verificationCode)
    {
        $this->user = $user;
        $this->verificationCode = $verificationCode;
    }

    public function handle(): void
    {
        Log::info('Sending password update confirmation code', [
            'user_id' => $this->user->id,
            'email' => $this->user->email,
            'code_length' => strlen((string) $this->verificationCode),
        ]);

        try {
            $this->user->notify(new PasswordUpdateConfirmationNotification($this->verificationCode));

            Log::info('Password update confirmation code sent successfully', [
                'user_id' => $this->user->id,
                'email' => $this->user->email,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to send password update confirmation code', [
                'user_id' => $this->user->id,
                'email' => $this->user->email,
                'error' => $e->getMessage(),
            ]);

            throw $e; // Re-throw to trigger job failure handling
        }
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('Password update confirmation job failed permanently', [
            'user_id' => $this->user->id,
            'email' => $this->user->email,
            'error' => $exception->getMessage(),
        ]);
    }
}

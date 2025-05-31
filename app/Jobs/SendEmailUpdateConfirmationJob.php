<?php

namespace App\Jobs;

use App\Models\User;
use App\Notifications\Profile\EmailUpdateConfirmationNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SendEmailUpdateConfirmationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected User $user;

    protected string $verificationCode;

    protected string $newEmail;

    public function __construct(User $user, string $verificationCode, string $newEmail)
    {
        $this->user = $user;
        $this->verificationCode = $verificationCode;
        $this->newEmail = $newEmail;
    }

    public function handle(): void
    {
        Log::info('Sending email update confirmation code', [
            'user_id' => $this->user->id,
            'current_email' => $this->user->email,
            'new_email' => $this->newEmail,
            'code_length' => strlen($this->verificationCode),
        ]);

        try {
            $this->user->notify(new EmailUpdateConfirmationNotification($this->verificationCode));

            Log::info('Email update confirmation code sent successfully', [
                'user_id' => $this->user->id,
                'current_email' => $this->user->email,
                'new_email' => $this->newEmail,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to send email update confirmation code', [
                'user_id' => $this->user->id,
                'current_email' => $this->user->email,
                'new_email' => $this->newEmail,
                'error' => $e->getMessage(),
            ]);

            throw $e; // Re-throw to trigger job failure handling
        }
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('Email update confirmation job failed permanently', [
            'user_id' => $this->user->id,
            'current_email' => $this->user->email,
            'new_email' => $this->newEmail,
            'error' => $exception->getMessage(),
        ]);
    }
}

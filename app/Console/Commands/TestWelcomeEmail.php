<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class TestWelcomeEmail extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test:welcome-email {email}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test welcome email sending';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $email = $this->argument('email');

        $this->info("Testing welcome email to: {$email}");

        try {
            // Найдем пользователя или создадим тестовые данные
            $user = User::where('email', $email)->first();

            if (! $user) {
                $this->error("User with email {$email} not found!");

                return 1;
            }

            $this->info("Found user: {$user->login} (ID: {$user->id})");

            // Тестируем отправку приветственного письма
            $user->sendWelcomeNotification();

            $this->info('Welcome email sent successfully!');
            $this->info('Check logs for details: tail -f storage/logs/laravel.log');

            return 0;
        } catch (\Exception $e) {
            $this->error('Error sending welcome email: '.$e->getMessage());
            Log::error('TestWelcomeEmail command failed', [
                'email' => $email,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return 1;
        }
    }
}

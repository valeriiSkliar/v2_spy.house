<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

class TestEmailVerification extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test:email-verification {email}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test email verification sending';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $email = $this->argument('email');

        // Создаем или находим пользователя
        $user = User::where('email', $email)->first();

        if (!$user) {
            $user = User::create([
                'name' => 'Test User',
                'login' => 'testuser_' . time(),
                'email' => $email,
                'password' => bcrypt('password'),
                'messenger_type' => 'telegram',
                'messenger_contact' => '@testuser_' . time(),
                'experience' => 'Beginner',
                'scope_of_activity' => 'Gambling',
            ]);
            $this->info("Created new user with email: {$email}");
        } else {
            $this->info("Found existing user with email: {$email}");
        }

        // Отправляем email верификации
        try {
            $user->sendEmailVerificationNotification();
            $this->info("Email verification sent successfully to: {$email}");
        } catch (\Exception $e) {
            $this->error("Failed to send email verification: " . $e->getMessage());
        }
    }
}

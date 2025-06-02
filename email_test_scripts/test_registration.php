<?php

// Скрипт для тестирования регистрации пользователя
// Использует обновленную архитектуру с ProcessUserRegistrationJob

use App\Enums\Frontend\UserExperience;
use App\Enums\Frontend\UserScopeOfActivity;
use App\Models\User;
use App\Jobs\ProcessUserRegistrationJob;
use Illuminate\Support\Facades\Hash;

// Создаем тестовые данные пользователя
$userData = [
    'login' => 'test_user_' . time(),
    'email' => 'delivered@resend.dev',
    'password' => Hash::make('Test123!@#'),
    'messenger_type' => 'telegram',
    'messenger_contact' => '@test_user',
    'experience' => UserExperience::BEGINNER->value,
    'scope_of_activity' => UserScopeOfActivity::CRYPTO->value
];

echo "Creating test user...\n";

// Создаем пользователя напрямую
$user = User::create($userData);

echo "User created with ID: " . $user->id . "\n";
echo "Email: " . $user->email . "\n";

// Запускаем job для обработки регистрации
echo "Dispatching ProcessUserRegistrationJob...\n";

ProcessUserRegistrationJob::dispatch($user, [
    'registration_ip' => '127.0.0.1',
    'user_agent' => 'Test Script',
    'source' => 'test_script',
]);

echo "Registration job dispatched!\n";

// Проверяем логи email для тестового email
$testEmail = 'delivered@resend.dev';
$emailLog = App\Models\EmailLog::where('email', $testEmail)
    ->orderBy('created_at', 'desc')
    ->first();

if ($emailLog) {
    echo "\nEmail Log:\n";
    echo "Status: " . $emailLog->status . "\n";
    echo "Subject: " . $emailLog->subject . "\n";
    echo "Template: " . $emailLog->template . "\n";
    echo "Sent at: " . $emailLog->sent_at . "\n";
} else {
    echo "\nNo email log found for $testEmail\n";
}

echo "\nCheck logs with: tail -f storage/logs/laravel.log\n";
echo "Run queue worker: php artisan queue:work\n";

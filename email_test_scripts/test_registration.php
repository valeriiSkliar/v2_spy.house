<?php

// Скрипт для тестирования регистрации пользователя в Tinker
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

// Создаем пользователя напрямую
$user = User::create($userData);

dump("User created", [
    'id' => $user->id,
    'email' => $user->email,
    'login' => $user->login
]);

// Запускаем job для обработки регистрации
ProcessUserRegistrationJob::dispatch($user, [
    'registration_ip' => '127.0.0.1',
    'user_agent' => 'Test Script',
    'source' => 'test_script',
]);

dump("Registration job dispatched");

// Проверяем логи email для тестового email
$testEmail = 'delivered@resend.dev';
$emailLog = App\Models\EmailLog::where('email', $testEmail)
    ->orderBy('created_at', 'desc')
    ->first();

if ($emailLog) {
    dump("Email Log found", [
        'status' => $emailLog->status,
        'subject' => $emailLog->subject,
        'template' => $emailLog->template,
        'sent_at' => $emailLog->sent_at
    ]);
} else {
    dump("No email log found for $testEmail");
}

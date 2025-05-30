<?php

// Создаем тестовые данные пользователя

use App\Enums\Frontend\UserExperience;
use App\Enums\Frontend\UserScopeOfActivity;

$userData = [
    'login' => 'test_user_' . time(),
    'email' => '2.21atlanta@gmail.com',
    'password' => 'Test123!@#',
    'password_confirmation' => 'Test123!@#',
    'messenger_type' => 'telegram',
    'messenger_contact' => '@test_user',
    'experience' => UserExperience::BEGINNER->name,
    'scope_of_activity' => UserScopeOfActivity::CRYPTO->name
];

// Создаем экземпляры сервисов
$tokenService = new App\Services\Api\TokenService();
$registrationService = new App\Services\User\UserRegistrationService();
$controller = new App\Http\Controllers\Auth\RegisteredUserController($tokenService, $registrationService);

// Создаем RegisteredUserRequest
$request = new App\Http\Requests\Profile\RegisteredUserRequest();
$request->merge($userData);

// Устанавливаем валидацию
$request->setValidator(
    Illuminate\Support\Facades\Validator::make($userData, [
        'login' => ['required', 'string', 'max:255', 'unique:users'],
        'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
        'password' => ['required', 'string', 'min:8', 'confirmed'],
        'messenger_type' => ['required', 'string'],
        'messenger_contact' => ['required', 'string'],
        'experience' => ['required', 'string'],
        'scope_of_activity' => ['required', 'string'],
    ])
);

// Вызываем метод store
$response = $controller->store($request);

// Выводим результат
echo "Registration completed!\n";
echo "Response: " . json_encode($response, JSON_PRETTY_PRINT) . "\n";

// Проверяем логи email
$emailLog = App\Models\EmailLog::where('email', '2.21atlanta@gmail.com')
    ->orderBy('created_at', 'desc')
    ->first();

if ($emailLog) {
    echo "\nEmail Log:\n";
    echo "Status: " . $emailLog->status . "\n";
    echo "Subject: " . $emailLog->subject . "\n";
    echo "Template: " . $emailLog->template . "\n";
    echo "Sent at: " . $emailLog->sent_at . "\n";
} else {
    echo "\nNo email log found\n";
}

echo "\nCheck logs with: tail -f storage/logs/laravel.log\n";

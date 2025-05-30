<?php

require_once __DIR__ . '/../vendor/autoload.php';

// Загружаем конфигурацию Laravel
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Libraries\Resend;

echo "=== Тестирование добавления контактов в Resend API ===\n\n";

// Проверяем конфигурацию
echo "Проверка конфигурации:\n";
echo "API Key: " . (config('resend.api_key') ? 'установлен' : 'не установлен') . "\n";
echo "Audience ID: " . (config('resend.audience_id') ? config('resend.audience_id') : 'не установлен') . "\n\n";

// Создаем экземпляр библиотеки Resend
$resend = new Resend();

// Тестовые данные пользователя (как в примере)
$user_details = [
    'email' => 'test.user@example.com',
    'login' => 'testuser',
    'is_email_subscribed' => 1
];

$unsubscribe_token = 'token_' . time();

// Формируем данные для добавления контакта
$data = [
    'email'         => $user_details['email'],
    'first_name'    => $user_details['login'],
    'last_name'     => $unsubscribe_token,
    'unsubscribed'  => $user_details['is_email_subscribed'] == 1 ? FALSE : TRUE,
];

echo "Данные для добавления контакта:\n";
echo "Email: " . $data['email'] . "\n";
echo "First Name: " . $data['first_name'] . "\n";
echo "Last Name: " . $data['last_name'] . "\n";
echo "Unsubscribed: " . ($data['unsubscribed'] ? 'true' : 'false') . "\n\n";

// Тестируем добавление контакта
echo "Добавляем контакт...\n";
$api_resend = $resend->add_contact($data);

echo "Результат добавления контакта:\n";
if ($api_resend['status'] === 'error') {
    echo "ОШИБКА: " . $api_resend['msg'] . "\n";
    if (isset($api_resend['error'])) {
        echo "Детали ошибки: " . $api_resend['error'] . "\n";
    }
    if (isset($api_resend['http_code'])) {
        echo "HTTP код: " . $api_resend['http_code'] . "\n";
    }
} else {
    echo "УСПЕХ: " . $api_resend['msg'] . "\n";
    echo "ID контакта: " . $api_resend['id'] . "\n";
}
echo "\n";

// Дополнительные тесты

// Тест 1: Контакт с отпиской
echo "=== Тест 1: Добавление контакта с отпиской ===\n";
$data_unsubscribed = [
    'email'         => 'unsubscribed.user@example.com',
    'first_name'    => 'UnsubscribedUser',
    'last_name'     => 'token_unsubscribed',
    'unsubscribed'  => TRUE,
];

$result_unsubscribed = $resend->add_contact($data_unsubscribed);
echo "Результат:\n";
if ($result_unsubscribed['status'] === 'error') {
    echo "ОШИБКА: " . $result_unsubscribed['msg'] . "\n";
    if (isset($result_unsubscribed['error'])) {
        echo "Детали: " . $result_unsubscribed['error'] . "\n";
    }
    if (isset($result_unsubscribed['http_code'])) {
        echo "HTTP код: " . $result_unsubscribed['http_code'] . "\n";
    }
} else {
    echo "УСПЕХ: " . $result_unsubscribed['msg'] . "\n";
}
echo "\n";

// Тест 2: Контакт с минимальными данными
echo "=== Тест 2: Добавление контакта с минимальными данными ===\n";
$data_minimal = [
    'email' => 'minimal.user@example.com'
];

$result_minimal = $resend->add_contact($data_minimal);
echo "Результат:\n";
if ($result_minimal['status'] === 'error') {
    echo "ОШИБКА: " . $result_minimal['msg'] . "\n";
    if (isset($result_minimal['error'])) {
        echo "Детали: " . $result_minimal['error'] . "\n";
    }
    if (isset($result_minimal['http_code'])) {
        echo "HTTP код: " . $result_minimal['http_code'] . "\n";
    }
} else {
    echo "УСПЕХ: " . $result_minimal['msg'] . "\n";
}
echo "\n";

// Тест 3: Некорректный email
echo "=== Тест 3: Тест с некорректным email ===\n";
$data_invalid = [
    'email'         => 'invalid-email',
    'first_name'    => 'InvalidUser',
    'last_name'     => 'token_invalid',
    'unsubscribed'  => FALSE,
];

$result_invalid = $resend->add_contact($data_invalid);
echo "Результат:\n";
if ($result_invalid['status'] === 'error') {
    echo "ОШИБКА: " . $result_invalid['msg'] . "\n";
    if (isset($result_invalid['error'])) {
        echo "Детали: " . $result_invalid['error'] . "\n";
    }
    if (isset($result_invalid['http_code'])) {
        echo "HTTP код: " . $result_invalid['http_code'] . "\n";
    }
} else {
    echo "УСПЕХ: " . $result_invalid['msg'] . "\n";
}
echo "\n";

// Тест 4: Пустой email
echo "=== Тест 4: Тест с пустым email ===\n";
$data_empty = [
    'email'         => '',
    'first_name'    => 'EmptyEmailUser',
];

$result_empty = $resend->add_contact($data_empty);
echo "Результат:\n";
if ($result_empty['status'] === 'error') {
    echo "ОШИБКА: " . $result_empty['msg'] . "\n";
    if (isset($result_empty['error'])) {
        echo "Детали: " . $result_empty['error'] . "\n";
    }
} else {
    echo "УСПЕХ: " . $result_empty['msg'] . "\n";
}
echo "\n";

echo "=== Тестирование завершено ===\n";

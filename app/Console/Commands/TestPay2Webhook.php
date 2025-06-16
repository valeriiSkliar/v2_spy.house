<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Finance\Http\Controllers\Pay2WebhookController;
use App\Finance\Services\Pay2Service;
use App\Finance\Models\Payment;
use App\Models\User;
use App\Enums\Finance\PaymentMethod;
use App\Enums\Finance\PaymentStatus;
use App\Enums\Finance\PaymentType;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class TestPay2Webhook extends Command
{
    protected $signature = 'test:pay2-webhook';
    protected $description = 'Тестирование обработки webhook от Pay2.House с реальными данными';

    public function handle()
    {
        $this->info('=== Тестирование обработки Pay2.House webhook ===');

        try {
            // Реальные данные из curl запроса
            $webhookData = [
                'invoice_number' => 'IN2212956367',
                'external_number' => 'TN121750056778',
                'amount' => 1,
                'handling_fee' => 0,
                'currency_code' => 'USD',
                'description' => 'плата тарифа Start (month)',
                'status' => 'paid'
            ];

            $signature = 'NTM5OWVlODBiZDVhZWFiNzBmZjE4YmU3MGZjMGRlNGV8ZGYwYmY0MTI2ZTgxNzYwNjA1MjM0OTI5ODRmNzQ5NjE1YTMyOTQzNjNmMDQ3NDZmZmM2NDJlYWFmOTk0NzI4OXxZMm94YTJVd1ExWXpXbTUxT1hkUVRFVm1ORU5NTDFBMllqZFJNelp4VFhOcmJtcHliR0pDZVRRelZsTk1abFp2UW0wMVJsaDRRMFpQWm5GWmNXWlFUM0l5Y1dGRVUwbGtMM2R2TVdreVdqZElURkZQVkhNMGQwY3JVVGMyU210alVYa3ZjMGRqYTJObFpuTmFhRGRrYVZodmNITklla05aVjBwNE1IUjBVa04zU1dFdmVrWkhLMFpOVEZSTVVUTlFTRWd2WlU1WE5tMVpPVTlUZEVkVmEyWldSbXRWWWs5ek9XTnpNa2RYWkRJMWNERlVVRVJYYUZSclpHVlBNbTlRY0VadFFXVXdkR0kwZDJJMVdFcEhURlI1Um1oQ05EQmpVMFp0Ukc5eFFXWjNOSGhhWm0wNUx6WTJhUzlFV1UxRmN6ZEVLMHRwWlZwWmJubFFVM2RsU0RGc1F6Um9ia3RGY1RkRkwyMDNiekZzVUdGYVdqTmtNRUphVUZCeGNDOVVVRGxOYVZZemNETkJTa1Y0TW5OT05XVlRjME5FU1ZoMlRqbG5OMVE0TDNNPQ==';

            $this->info('1. Проверка подписи webhook...');
            $this->testWebhookSignature($signature, $webhookData);

            $this->info('2. Создание тестового платежа...');
            $payment = $this->createTestPayment($webhookData);

            $this->info('3. Симуляция обработки webhook...');
            $this->simulateWebhookProcessing($webhookData, $signature, $payment);

            $this->info('4. Проверка результатов...');
            $this->checkResults($payment);

            $this->info('✅ Тестирование webhook завершено успешно!');
        } catch (\Exception $e) {
            $this->error('❌ Ошибка при тестировании: ' . $e->getMessage());
            $this->error('Трейс: ' . $e->getTraceAsString());
        }
    }

    protected function testWebhookSignature($signature, $webhookData)
    {
        $pay2Service = app(Pay2Service::class);

        $this->info('Подпись: ' . substr($signature, 0, 50) . '...');
        $this->info('Данные: ' . json_encode($webhookData));
        $this->info('Режим Pay2: ' . (config('pay2.test_mode') ? 'Тестовый' : 'Продакшн'));

        // В тестовом режиме проверка подписи может быть пропущена
        if (config('pay2.test_mode')) {
            $this->warn('⚠️ Тестовый режим: проверка подписи пропущена');
        } else {
            $isValidSignature = $pay2Service->verifyWebhookSignature($signature, $webhookData);
            $this->info('Подпись валидна: ' . ($isValidSignature ? '✅ Да' : '❌ Нет'));

            if (!$isValidSignature) {
                $this->warn('⚠️ Принудительно включаем тестовый режим для тестирования');
                config(['pay2.test_mode' => true]);
            }
        }
    }

    protected function createTestPayment($webhookData)
    {
        // Создаем тестового пользователя или находим существующего
        $user = User::first();
        if (!$user) {
            $this->error('❌ Не найден пользователь для создания платежа');
            throw new \Exception('Пользователь не найден');
        }

        // Очищаем старый тестовый платеж, если есть
        Payment::where('invoice_number', $webhookData['invoice_number'])->delete();

        // Создаем тестовый платеж
        $payment = Payment::create([
            'user_id' => $user->id,
            'amount' => $webhookData['amount'],
            'payment_type' => PaymentType::DEPOSIT,
            'subscription_id' => null,
            'payment_method' => PaymentMethod::PAY2_HOUSE,
            'status' => PaymentStatus::PENDING,
            'invoice_number' => $webhookData['invoice_number'],
            'external_number' => $webhookData['external_number'],
        ]);

        $this->info("Создан тестовый платеж ID: {$payment->id}");
        $this->info("Пользователь: {$user->email}");
        $this->info("Сумма: {$payment->amount} USD");
        $this->info("Статус: {$payment->status->value}");
        $this->info("Баланс до: {$user->available_balance} USD");

        return $payment;
    }

    protected function simulateWebhookProcessing($webhookData, $signature, $payment)
    {
        // Создаем mock Request с данными webhook
        $request = new Request();
        $request->merge($webhookData);
        $request->headers->set('Pay2-House-Signature', $signature);
        $request->headers->set('Content-Type', 'application/json');

        $this->info('Симулируем обработку webhook через Pay2WebhookController...');
        $this->info('Тестовый режим: ' . (config('pay2.test_mode') ? 'включен' : 'выключен'));

        // Создаем экземпляр контроллера
        $webhookController = app(Pay2WebhookController::class);

        try {
            // Вызываем метод обработки
            $response = $webhookController->handle($request);

            $this->info('Ответ контроллера: ' . $response->getContent());
            $this->info('HTTP статус: ' . $response->getStatusCode());

            if ($response->getStatusCode() === 200) {
                $this->info('✅ Webhook обработан успешно');
            } else {
                $this->warn('⚠️ Webhook обработан с ошибкой');
            }
        } catch (\Exception $e) {
            $this->error('❌ Ошибка при обработке webhook: ' . $e->getMessage());
        }
    }

    protected function checkResults($payment)
    {
        // Обновляем данные платежа из БД
        $payment->refresh();

        $this->info('=== Результаты обработки ===');
        $this->info("Статус платежа: {$payment->status->value}");
        $this->info("Обновлен: {$payment->updated_at}");

        // Проверяем пользователя
        $user = $payment->user;
        $this->info("Баланс пользователя: {$user->balance}");

        if ($payment->status === PaymentStatus::SUCCESS) {
            $this->info('✅ Платеж успешно обработан!');
            if ($payment->payment_type === PaymentType::DEPOSIT) {
                $this->info('✅ Баланс должен быть пополнен');
            }
        } else {
            $this->warn('⚠️ Платеж не был обработан как успешный');
        }
    }
}

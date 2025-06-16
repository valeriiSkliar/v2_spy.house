<?php

namespace App\Console\Commands;

use App\Finance\Services\Pay2Service;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Config;

class TestPay2Webhook extends Command
{
    protected $signature = 'pay2:test-webhook';

    protected $description = 'Тестирование методов Pay2Service с реальными webhook данными';

    public function handle()
    {
        // Исходные данные из webhook лога
        $signature = 'NTM5OWVlODBiZDVhZWFiNzBmZjE4YmU3MGZjMGRlNGV8ZGYwYmY0MTI2ZTgxNzYwNjA1MjM0OTI5ODRmNzQ5NjE1YTMyOTQzNjNmMDQ3NDZmZmM2NDJlYWFmOTk0NzI4OXxZMm94YTJVd1ExWXpXbTUxT1hkUVRFVm1ORU5NTDFBMllqZFJNelp4VFhOcmJtcHliR0pDZVRRelZsTk1abFp2UW0wMVJsaDRRMFpQWm5GWmNXWlFUM0l5Y1dGRVUwbGtMM2R2TVdreVdqZElURkZQVkhNMGQwY3JVVGMyU210alVYa3ZjMGRqYTJObFpuTmFhRGRrYVZodmNITklla05aVjBwNE1IUjBVa04zU1dFdmVrWkhLMFpOVEZSTVVUTlFTRWd2WlU1WE5tMVpPVTlUZEVkVmEyWldSbXRWWWs5ek9XTnpNa2RYWkRJMWNERlVVRVJYYUZSclpHVlBNbTlRY0VadFFXVXdkR0kwZDJJMVdFcEhURlI1Um1oQ05EQmpVMFp0Ukc5eFFXWjNOSGhhWm0wNUx6WTJhUzlFV1UxRmN6ZEVLMHRwWlZwWmJubFFVM2RsU0RGc1F6Um9ia3RGY1RkRkwyMDNiekZzVUdGYVdqTmtNRUphVUZCeGNDOVVVRGxOYVZZemNETkJTa1Y0TW5OT05XVlRjME5FU1ZoMlRqbG5OMVE0TDNNPQ==';

        $payload = [
            'invoice_number' => 'IN2212956367',
            'external_number' => 'TN121750056778',
            'amount' => 1,
            'handling_fee' => 0,
            'currency_code' => 'USD',
            'description' => 'Оплата тарифа Start (month)',
            'status' => 'paid',
        ];

        $this->line('=== ТЕСТИРОВАНИЕ Pay2Service МЕТОДОВ ===');
        $this->line('Signature: '.substr($signature, 0, 50).'...');
        $this->line('Payload: '.json_encode($payload, JSON_UNESCAPED_UNICODE));
        $this->line('');

        // Создаем экземпляр сервиса
        $pay2Service = new Pay2Service;

        // Получаем API ключ из конфига
        $apiKey = Config::get('pay2.test_mode')
            ? Config::get('pay2.test_api_key')
            : Config::get('pay2.api_key');

        $this->info('Используемый API ключ: '.substr($apiKey, 0, 20).'...');
        $this->info('Тестовый режим: '.(Config::get('pay2.test_mode') ? 'ДА' : 'НЕТ'));
        $this->line('');

        // ТЕСТ 1: decrypt_webhook
        $this->line('=== ТЕСТ 1: decrypt_webhook ===');
        $decrypted = $pay2Service->decrypt_webhook($signature, $apiKey);
        if ($decrypted !== false) {
            $this->line('✅ Расшифровка успешна!');
            $this->line('Расшифрованные данные: '.$decrypted);

            $webhookData = json_decode($decrypted, true);
            if ($webhookData) {
                $this->line('Parsed JSON:');
                foreach ($webhookData as $key => $value) {
                    $this->line("  $key: $value");
                }
            }
        } else {
            $this->error('❌ Ошибка расшифровки');
        }
        $this->line('');

        // ТЕСТ 2: validate_pay2_webhook
        $this->line('=== ТЕСТ 2: validate_pay2_webhook (с отладкой) ===');
        $validationResult = $pay2Service->validate_pay2_webhook($signature, $payload, $apiKey, true);
        $this->line('Результат валидации:');
        $this->line('Valid: '.($validationResult['valid'] ? 'true' : 'false'));
        if (isset($validationResult['error'])) {
            $this->error('Error: '.$validationResult['error']);
        }
        if (isset($validationResult['webhook_data'])) {
            $this->line('Webhook data:');
            foreach ($validationResult['webhook_data'] as $key => $value) {
                $this->line("  $key: $value");
            }
        }
        $this->line('');

        // ТЕСТ 3: verifyWebhookSignature
        $this->line('=== ТЕСТ 3: verifyWebhookSignature ===');
        $isValid = $pay2Service->verifyWebhookSignature($signature, $payload);
        $this->line('Результат: '.($isValid ? '✅ ВАЛИДНАЯ' : '❌ НЕВАЛИДНАЯ').' подпись');

        $this->line('');
        $this->info('=== ТЕСТИРОВАНИЕ ЗАВЕРШЕНО ===');
    }
}

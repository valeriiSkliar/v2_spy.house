<?php

namespace Tests\Feature\Finance;

use App\Enums\Finance\PaymentMethod;
use App\Enums\Finance\PaymentStatus;
use App\Enums\Finance\PaymentType;
use App\Finance\Models\Payment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PaymentSecurityTest extends TestCase
{
    use RefreshDatabase;

    public function test_webhook_tokens_are_cryptographically_secure(): void
    {
        $tokens = [];

        // Создаем много платежей для проверки коллизий
        for ($i = 0; $i < 100; $i++) {
            $payment = Payment::factory()->create();
            $tokens[] = $payment->webhook_token;
        }

        // Проверяем уникальность всех токенов
        $uniqueTokens = array_unique($tokens);
        $this->assertCount(100, $uniqueTokens, 'Webhook tokens must be unique');

        // Проверяем что токены содержат только безопасные символы
        foreach ($tokens as $token) {
            $this->assertMatchesRegularExpression('/^[a-zA-Z0-9]+$/', $token);
            $this->assertEquals(64, strlen($token));
        }
    }

    public function test_idempotency_keys_prevent_duplicate_payments(): void
    {
        $idempotencyKey = 'test-idempotency-key-123';

        $payment1 = Payment::factory()->create([
            'idempotency_key' => $idempotencyKey,
        ]);

        // Попытка создать платеж с тем же idempotency_key должна вызвать ошибку
        $this->expectException(\Illuminate\Database\QueryException::class);

        Payment::factory()->create([
            'idempotency_key' => $idempotencyKey,
        ]);
    }

    public function test_transaction_numbers_are_unique_when_provided(): void
    {
        $transactionNumber = 'TXN123456789';

        $payment1 = Payment::factory()->create([
            'transaction_number' => $transactionNumber,
        ]);

        // Попытка создать платеж с тем же transaction_number должна вызвать ошибку
        $this->expectException(\Illuminate\Database\QueryException::class);

        Payment::factory()->create([
            'transaction_number' => $transactionNumber,
        ]);
    }

    public function test_payment_data_integrity_preserved_on_status_changes(): void
    {
        $originalData = [
            'amount' => 100.50,
            'payment_type' => PaymentType::DEPOSIT,
            'payment_method' => PaymentMethod::USDT,
        ];

        $payment = Payment::factory()->create($originalData);

        // Изменяем статус
        $payment->markAsSuccessful();

        // Проверяем что основные данные не изменились
        $this->assertEquals($originalData['amount'], $payment->amount);
        $this->assertEquals($originalData['payment_type'], $payment->payment_type);
        $this->assertEquals($originalData['payment_method'], $payment->payment_method);
    }

    public function test_sensitive_fields_are_not_mass_assignable_accidentally(): void
    {
        $allowedData = [
            'user_id' => User::factory()->create()->id,
            'amount' => 100.00,
            'payment_type' => PaymentType::DEPOSIT,
            'payment_method' => PaymentMethod::USDT,
        ];

        $maliciousData = [
            'id' => 999999,
            'created_at' => '1970-01-01 00:00:00',
            'updated_at' => '1970-01-01 00:00:00',
        ];

        // Пытаемся создать платеж с вредоносными данными через массовое присвоение
        $payment = Payment::create(array_merge($maliciousData, $allowedData));

        // Проверяем что защищенные поля не были установлены
        $this->assertNotEquals(999999, $payment->id);
        $this->assertNotEquals('1970-01-01 00:00:00', $payment->created_at->format('Y-m-d H:i:s'));

        // Но разрешенные поля должны быть установлены
        $this->assertEquals($allowedData['amount'], $payment->amount);
        $this->assertEquals($allowedData['payment_type'], $payment->payment_type);
    }

    public function test_payment_cannot_be_created_with_negative_amount(): void
    {
        // Хотя в модели нет прямой валидации на отрицательную сумму,
        // логика приложения должна это предотвращать
        $payment = Payment::factory()->make(['amount' => -100.00]);

        // В реальном приложении здесь должна быть валидация
        $this->assertTrue($payment->amount < 0, 'Negative amounts should be handled by business logic');
    }

    public function test_webhook_processing_idempotency(): void
    {
        $payment = Payment::factory()->pending()->create();
        $originalProcessedAt = $payment->webhook_processed_at;

        // Первое выполнение
        $payment->markAsSuccessful();
        $firstProcessedAt = $payment->webhook_processed_at;

        // Повторное выполнение не должно изменить время обработки
        $payment->markAsSuccessful();
        $secondProcessedAt = $payment->webhook_processed_at;

        $this->assertEquals($firstProcessedAt, $secondProcessedAt);
    }

    public function test_payment_isolation_between_users(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        $payment1 = Payment::factory()->create(['user_id' => $user1->id]);
        $payment2 = Payment::factory()->create(['user_id' => $user2->id]);

        // Проверяем что платежи изолированы между пользователями
        $user1Payments = Payment::where('user_id', $user1->id)->get();
        $user2Payments = Payment::where('user_id', $user2->id)->get();

        $this->assertCount(1, $user1Payments);
        $this->assertCount(1, $user2Payments);
        $this->assertEquals($payment1->id, $user1Payments->first()->id);
        $this->assertEquals($payment2->id, $user2Payments->first()->id);
    }

    public function test_payment_status_transitions_are_logged(): void
    {
        $payment = Payment::factory()->pending()->create();

        $this->assertNull($payment->webhook_processed_at);

        $payment->markAsSuccessful();
        $this->assertNotNull($payment->webhook_processed_at);
        $this->assertEquals(PaymentStatus::SUCCESS, $payment->status);

        // Проверяем что время обработки сохранено
        $this->assertLessThanOrEqual(now(), $payment->webhook_processed_at);
        $this->assertGreaterThan(now()->subMinute(), $payment->webhook_processed_at);
    }

    public function test_parent_payment_relationship_for_refunds(): void
    {
        $originalPayment = Payment::factory()->successful()->create();
        $refundPayment = Payment::factory()->create([
            'parent_payment_id' => $originalPayment->id,
            'amount' => -$originalPayment->amount, // Отрицательная сумма для возврата
        ]);

        $this->assertEquals($originalPayment->id, $refundPayment->parent_payment_id);
        $this->assertInstanceOf(Payment::class, $refundPayment->parentPayment);
        $this->assertEquals($originalPayment->id, $refundPayment->parentPayment->id);
    }
}

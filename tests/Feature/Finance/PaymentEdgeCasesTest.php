<?php

namespace Tests\Feature\Finance;

use App\Enums\Finance\PaymentMethod;
use App\Enums\Finance\PaymentStatus;
use App\Enums\Finance\PaymentType;
use App\Finance\Models\Payment;
use App\Finance\Models\Subscription;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PaymentEdgeCasesTest extends TestCase
{
    use RefreshDatabase;

    public function test_payment_with_zero_amount(): void
    {
        $payment = Payment::factory()->create(['amount' => 0.00]);

        $this->assertEquals(0.00, $payment->amount);
        $this->assertEquals('$0.00', $payment->getFormattedAmount());
    }

    public function test_payment_with_very_large_amount(): void
    {
        $largeAmount = 999999999.99;
        $payment = Payment::factory()->create(['amount' => $largeAmount]);

        $this->assertEquals($largeAmount, $payment->amount);
        $this->assertEquals('$999,999,999.99', $payment->getFormattedAmount());
    }

    public function test_payment_with_very_small_decimal_amount(): void
    {
        $smallAmount = 0.01;
        $payment = Payment::factory()->create(['amount' => $smallAmount]);

        $this->assertEquals($smallAmount, $payment->amount);
        $this->assertEquals('$0.01', $payment->getFormattedAmount());
    }

    public function test_payment_creation_without_optional_fields(): void
    {
        $payment = Payment::factory()->create([
            'transaction_number' => null,
            'promocode_id' => null,
            'parent_payment_id' => null,
        ]);

        $this->assertNull($payment->transaction_number);
        $this->assertNull($payment->promocode_id);
        $this->assertNull($payment->parent_payment_id);
        $this->assertNotNull($payment->webhook_token);
        $this->assertNotNull($payment->idempotency_key);
    }

    public function test_orphaned_payment_when_user_deleted(): void
    {
        $user = User::factory()->create();
        $payment = Payment::factory()->create(['user_id' => $user->id]);

        // Удаляем пользователя
        $user->delete();

        // Платеж должен быть удален каскадно согласно миграции
        $this->assertDatabaseMissing('payments', ['id' => $payment->id]);
    }

    public function test_payment_when_subscription_deleted(): void
    {
        $subscription = Subscription::factory()->create();
        $payment = Payment::factory()->create([
            'subscription_id' => $subscription->id,
            'payment_type' => PaymentType::DIRECT_SUBSCRIPTION,
        ]);

        // Удаляем подписку
        $subscription->delete();

        // Проверяем что subscription_id установлен в null
        $payment->refresh();
        $this->assertNull($payment->subscription_id);
    }

    public function test_payment_status_transition_edge_cases(): void
    {
        $payment = Payment::factory()->pending()->create();

        // Переводим из PENDING в SUCCESS
        $payment->markAsSuccessful();
        $this->assertEquals(PaymentStatus::SUCCESS, $payment->status);

        // Попытка перевести из SUCCESS обратно в FAILED
        $payment->markAsFailed();
        $this->assertEquals(PaymentStatus::FAILED, $payment->status);

        // Время обработки должно обновиться
        $this->assertNotNull($payment->webhook_processed_at);
    }

    public function test_payment_factory_with_invalid_combinations(): void
    {
        // Фабрика должна корректно обрабатывать неверные комбинации
        $payment = Payment::factory()->deposit()->create();

        // Проверяем что для депозита не назначена подписка
        $this->assertNull($payment->subscription_id);
        $this->assertEquals(PaymentType::DEPOSIT, $payment->payment_type);

        // Проверяем что метод платежа валиден для депозита
        $this->assertTrue($payment->payment_method->isValidForDeposits());
    }

    public function test_payment_with_unicode_transaction_number(): void
    {
        $unicodeTransactionNumber = 'TXN_тест_№12345_ñáéíóú';

        $payment = Payment::factory()->create([
            'transaction_number' => $unicodeTransactionNumber,
        ]);

        $this->assertEquals($unicodeTransactionNumber, $payment->transaction_number);
    }

    public function test_payment_webhook_processing_multiple_times(): void
    {
        $payment = Payment::factory()->pending()->create();
        $originalProcessedAt = $payment->webhook_processed_at;

        // Первая обработка
        $payment->markAsSuccessful();
        $firstProcessedAt = $payment->webhook_processed_at;
        $this->assertNotEquals($originalProcessedAt, $firstProcessedAt);

        // Повторная обработка того же платежа
        $payment->markAsSuccessful();
        $secondProcessedAt = $payment->webhook_processed_at;
        $this->assertEquals($firstProcessedAt, $secondProcessedAt);
    }

    public function test_payment_scope_chaining(): void
    {
        $user = User::factory()->create();

        // Создаем различные платежи
        Payment::factory()->successful()->deposit()->count(5)->create(['user_id' => $user->id]);
        Payment::factory()->pending()->deposit()->count(3)->create(['user_id' => $user->id]);
        Payment::factory()->successful()->directSubscription()->count(2)->create(['user_id' => $user->id]);

        // Тестируем цепочку scope-ов
        $successfulDeposits = Payment::where('user_id', $user->id)
            ->successful()
            ->deposits()
            ->get();

        $this->assertCount(5, $successfulDeposits);

        foreach ($successfulDeposits as $payment) {
            $this->assertEquals(PaymentStatus::SUCCESS, $payment->status);
            $this->assertEquals(PaymentType::DEPOSIT, $payment->payment_type);
        }
    }

    public function test_payment_with_extreme_webhook_token_length(): void
    {
        // Тестируем граничные случаи для webhook_token
        $shortToken = 'a'; // Короткий токен
        $longToken = str_repeat('a', 255); // Очень длинный токен

        $payment1 = Payment::factory()->create(['webhook_token' => $shortToken]);
        $payment2 = Payment::factory()->create(['webhook_token' => $longToken]);

        $this->assertEquals($shortToken, $payment1->webhook_token);
        $this->assertEquals($longToken, $payment2->webhook_token);
    }

    public function test_payment_parent_child_relationship_depth(): void
    {
        // Создаем цепочку возвратов
        $originalPayment = Payment::factory()->successful()->create(['amount' => 100.00]);

        $firstRefund = Payment::factory()->create([
            'parent_payment_id' => $originalPayment->id,
            'amount' => -50.00,
        ]);

        $secondRefund = Payment::factory()->create([
            'parent_payment_id' => $firstRefund->id,
            'amount' => -25.00,
        ]);

        // Проверяем что связи работают
        $this->assertEquals($originalPayment->id, $firstRefund->parent_payment_id);
        $this->assertEquals($firstRefund->id, $secondRefund->parent_payment_id);

        // Проверяем что можем получить родительские платежи
        $this->assertInstanceOf(Payment::class, $firstRefund->parentPayment);
        $this->assertInstanceOf(Payment::class, $secondRefund->parentPayment);
    }

    public function test_payment_concurrent_webhook_token_generation(): void
    {
        // Симулируем одновременное создание для проверки уникальности токенов
        $tokens = [];

        for ($i = 0; $i < 50; $i++) {
            $payment = Payment::factory()->create();
            $tokens[] = $payment->webhook_token;
        }

        // Все токены должны быть уникальными
        $uniqueTokens = array_unique($tokens);
        $this->assertCount(50, $uniqueTokens);
    }

    public function test_payment_enum_serialization(): void
    {
        $payment = Payment::factory()->create([
            'payment_type' => PaymentType::DEPOSIT,
            'payment_method' => PaymentMethod::USDT,
            'status' => PaymentStatus::SUCCESS,
        ]);

        // Проверяем сериализацию в JSON
        $jsonData = $payment->toJson();
        $decodedData = json_decode($jsonData, true);

        $this->assertEquals('DEPOSIT', $decodedData['payment_type']);
        $this->assertEquals('USDT', $decodedData['payment_method']);
        $this->assertEquals('SUCCESS', $decodedData['status']);
    }

    public function test_payment_database_constraints_violation(): void
    {
        $payment = Payment::factory()->create();

        // Попытка создать дубликат webhook_token
        $this->expectException(\Illuminate\Database\QueryException::class);

        Payment::factory()->create([
            'webhook_token' => $payment->webhook_token,
        ]);
    }

    public function test_payment_soft_delete_behavior(): void
    {
        // Проверяем что модель не использует SoftDeletes
        $payment = Payment::factory()->create();
        $paymentId = $payment->id;

        $payment->delete();

        // Платеж должен быть полностью удален, не помечен как удаленный
        $this->assertDatabaseMissing('payments', ['id' => $paymentId]);
    }

    public function test_payment_mass_assignment_protection(): void
    {
        $protectedData = [
            'id' => 999999,
            'created_at' => '1970-01-01 00:00:00',
            'updated_at' => '1970-01-01 00:00:00',
        ];

        $allowedData = [
            'user_id' => User::factory()->create()->id,
            'amount' => 100.00,
            'payment_type' => PaymentType::DEPOSIT,
            'payment_method' => PaymentMethod::USDT,
        ];

        $payment = Payment::create(array_merge($protectedData, $allowedData));

        // Защищенные поля не должны быть установлены
        $this->assertNotEquals(999999, $payment->id);
        $this->assertNotEquals('1970-01-01 00:00:00', $payment->created_at->format('Y-m-d H:i:s'));
    }
}

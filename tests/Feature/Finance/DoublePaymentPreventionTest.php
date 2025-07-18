<?php

namespace Tests\Feature\Finance;

use Tests\TestCase;
use App\Models\User;
use App\Finance\Models\Subscription;
use App\Finance\Models\Payment;
use App\Finance\Services\BalanceService;
use App\Enums\Finance\PaymentMethod;
use App\Enums\Finance\PaymentStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\DB;

class DoublePaymentPreventionTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected BalanceService $balanceService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->balanceService = app(BalanceService::class);
    }

    /**
     * @test
     */
    public function it_prevents_duplicate_payments_with_same_idempotency_key()
    {
        // Создаем пользователя с балансом
        $user = User::factory()->create([
            'available_balance' => 200.00,
            'balance_version' => 1,
        ]);

        $subscription = Subscription::factory()->create([
            'amount' => 99.00,
            'name' => 'Premium',
        ]);

        $idempotencyKey = 'test-idempotency-key-123';

        // Первый платеж должен пройти успешно
        $result1 = $this->balanceService->processSubscriptionPaymentFromBalance(
            $user, 
            $subscription, 
            'month', 
            $idempotencyKey
        );

        $this->assertTrue($result1['success']);
        $this->assertArrayHasKey('payment', $result1);

        // Второй платеж с тем же idempotency_key должен быть заблокирован
        $result2 = $this->balanceService->processSubscriptionPaymentFromBalance(
            $user, 
            $subscription, 
            'month', 
            $idempotencyKey
        );

        $this->assertFalse($result2['success']);
        $this->assertStringContainsString('уже обработан', $result2['error']);
        $this->assertArrayHasKey('payment_id', $result2);
        $this->assertEquals($result1['payment']->id, $result2['payment_id']);
    }

    /**
     * @test
     */
    public function it_prevents_recent_duplicate_payments()
    {
        // Создаем пользователя с балансом
        $user = User::factory()->create([
            'available_balance' => 200.00,
            'balance_version' => 1,
        ]);

        $subscription = Subscription::factory()->create([
            'amount' => 99.00,
            'name' => 'Premium',
        ]);

        // Первый платеж
        $result1 = $this->balanceService->processSubscriptionPaymentFromBalance(
            $user, 
            $subscription, 
            'month'
        );

        $this->assertTrue($result1['success']);

        // Второй платеж в течение 10 секунд должен быть заблокирован
        $result2 = $this->balanceService->processSubscriptionPaymentFromBalance(
            $user, 
            $subscription, 
            'month'
        );

        $this->assertFalse($result2['success']);
        $this->assertStringContainsString('уже обрабатывается', $result2['error']);
    }

    /**
     * @test
     */
    public function it_allows_payment_after_time_window()
    {
        // Создаем пользователя с балансом
        $user = User::factory()->create([
            'available_balance' => 200.00,
            'balance_version' => 1,
        ]);

        $subscription = Subscription::factory()->create([
            'amount' => 99.00,
            'name' => 'Premium',
        ]);

        // Создаем "старый" платеж (более 10 секунд назад) - используем FAILED статус
        $oldPayment = Payment::create([
            'user_id' => $user->id,
            'subscription_id' => $subscription->id,
            'payment_method' => PaymentMethod::USER_BALANCE,
            'payment_type' => \App\Enums\Finance\PaymentType::DIRECT_SUBSCRIPTION,
            'status' => PaymentStatus::FAILED,  // Изменяем на FAILED
            'amount' => 99.00,
            'external_number' => 'TN' . $user->id . $subscription->id . time(),
            'invoice_number' => 'IN' . strtoupper(\Illuminate\Support\Str::random(10)),
            'webhook_token' => \Illuminate\Support\Str::random(64),
            'idempotency_key' => \Illuminate\Support\Str::uuid(),
            'created_at' => now()->subSeconds(15),
        ]);

        // Новый платеж должен пройти успешно
        $result = $this->balanceService->processSubscriptionPaymentFromBalance(
            $user, 
            $subscription, 
            'month'
        );

        $this->assertTrue($result['success']);
        $this->assertArrayHasKey('payment', $result);
        $this->assertNotEquals($oldPayment->id, $result['payment']->id);
    }

    /**
     * @test
     */
    public function it_allows_payment_with_different_subscription()
    {
        // Создаем пользователя с балансом
        $user = User::factory()->create([
            'available_balance' => 300.00,
            'balance_version' => 1,
        ]);

        $subscription1 = Subscription::factory()->create([
            'amount' => 99.00,
            'name' => 'Premium',
        ]);

        $subscription2 = Subscription::factory()->create([
            'amount' => 59.00,
            'name' => 'Basic',
        ]);

        // Первый платеж за Premium
        $result1 = $this->balanceService->processSubscriptionPaymentFromBalance(
            $user, 
            $subscription1, 
            'month'
        );

        $this->assertTrue($result1['success']);

        // Обновляем пользователя для второго платежа
        $user->refresh();
        
        // Второй платеж за Basic должен пройти успешно
        $result2 = $this->balanceService->processSubscriptionPaymentFromBalance(
            $user, 
            $subscription2, 
            'month'
        );

        $this->assertTrue($result2['success']);
        $this->assertNotEquals($result1['payment']->id, $result2['payment']->id);
    }

    /**
     * @test
     */
    public function it_handles_insufficient_balance_correctly()
    {
        // Создаем пользователя с недостаточным балансом
        $user = User::factory()->create([
            'available_balance' => 50.00,
            'balance_version' => 1,
        ]);

        $subscription = Subscription::factory()->create([
            'amount' => 99.00,
            'name' => 'Premium',
        ]);

        // Платеж должен быть отклонен
        $result = $this->balanceService->processSubscriptionPaymentFromBalance(
            $user, 
            $subscription, 
            'month'
        );

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('Недостаточно средств', $result['error']);
    }

    /**
     * @test
     */
    public function it_handles_balance_version_conflict()
    {
        // Создаем пользователя с балансом
        $user = User::factory()->create([
            'available_balance' => 200.00,
            'balance_version' => 1,
        ]);

        $subscription = Subscription::factory()->create([
            'amount' => 99.00,
            'name' => 'Premium',
        ]);

        // Симулируем изменение версии баланса другим процессом
        DB::table('users')
            ->where('id', $user->id)
            ->update(['balance_version' => 2]);

        // Платеж должен быть отклонен из-за конфликта версий
        $result = $this->balanceService->processSubscriptionPaymentFromBalance(
            $user, 
            $subscription, 
            'month'
        );

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('изменен другой операцией', $result['error']);
    }

    /**
     * @test
     */
    public function it_creates_payments_with_unique_idempotency_keys()
    {
        // Создаем пользователя с балансом
        $user = User::factory()->create([
            'available_balance' => 300.00,
            'balance_version' => 1,
        ]);

        $subscription1 = Subscription::factory()->create([
            'amount' => 99.00,
            'name' => 'Premium',
        ]);

        $subscription2 = Subscription::factory()->create([
            'amount' => 59.00,
            'name' => 'Basic',
        ]);

        // Создаем два платежа для разных подписок
        $result1 = $this->balanceService->processSubscriptionPaymentFromBalance(
            $user, 
            $subscription1, 
            'month'
        );

        // Обновляем пользователя для второго платежа
        $user->refresh();

        $result2 = $this->balanceService->processSubscriptionPaymentFromBalance(
            $user, 
            $subscription2, 
            'month'
        );

        $this->assertTrue($result1['success']);
        $this->assertTrue($result2['success']);

        // Проверяем что idempotency_key разные
        $payment1 = $result1['payment'];
        $payment2 = $result2['payment'];

        $this->assertNotEquals($payment1->idempotency_key, $payment2->idempotency_key);
        $this->assertNotNull($payment1->idempotency_key);
        $this->assertNotNull($payment2->idempotency_key);
    }
}
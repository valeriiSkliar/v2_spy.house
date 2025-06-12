<?php

namespace Tests\Feature\Finance;

use Tests\TestCase;
use App\Models\User;
use App\Finance\Models\Subscription;
use App\Finance\Models\Payment;
use App\Finance\Services\BalanceService;
use App\Enums\Finance\PaymentType;
use App\Enums\Finance\PaymentMethod;
use App\Enums\Finance\PaymentStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;

class BalanceServiceTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected BalanceService $balanceService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->balanceService = app(BalanceService::class);
    }

    /** @test */
    public function it_can_check_insufficient_balance()
    {
        $user = User::factory()->create(['available_balance' => 50.00]);

        $this->assertTrue($this->balanceService->hasInsufficientBalance($user, 100.00));
        $this->assertFalse($this->balanceService->hasInsufficientBalance($user, 25.00));
        $this->assertFalse($this->balanceService->hasInsufficientBalance($user, 50.00));
    }

    /** @test */
    public function it_can_add_to_balance()
    {
        $user = User::factory()->create(['available_balance' => 100.00]);

        $result = $this->balanceService->addToBalance($user, 50.00, 'Test deposit');

        $this->assertTrue($result);
        $user->refresh();
        $this->assertEquals(150.00, $user->available_balance);
        $this->assertEquals(2, $user->balance_version); // Версия должна увеличиться
    }

    /** @test */
    public function it_rejects_subscription_payment_with_insufficient_balance()
    {
        $user = User::factory()->create(['available_balance' => 10.00]);
        $subscription = Subscription::factory()->create(['amount' => 50.00]);

        $result = $this->balanceService->processSubscriptionPaymentFromBalance($user, $subscription, 'month');

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('Недостаточно средств', $result['error']);

        // Баланс не должен измениться
        $user->refresh();
        $this->assertEquals(10.00, $user->available_balance);
    }

    /** @test */
    public function it_can_process_subscription_payment_from_balance()
    {
        $user = User::factory()->create(['available_balance' => 100.00]);
        $subscription = Subscription::factory()->create(['amount' => 30.00]);

        $result = $this->balanceService->processSubscriptionPaymentFromBalance($user, $subscription, 'month');

        $this->assertTrue($result['success']);
        $this->assertArrayHasKey('payment', $result);
        $this->assertInstanceOf(Payment::class, $result['payment']);

        // Проверяем что баланс уменьшился
        $user->refresh();
        $this->assertEquals(70.00, $user->available_balance);

        // Проверяем что платеж создан корректно
        $payment = $result['payment'];
        $this->assertEquals($user->id, $payment->user_id);
        $this->assertEquals(30.00, $payment->amount);
        $this->assertEquals(PaymentType::DIRECT_SUBSCRIPTION, $payment->payment_type);
        $this->assertEquals(PaymentMethod::USER_BALANCE, $payment->payment_method);
        $this->assertEquals(PaymentStatus::SUCCESS, $payment->status);

        // Проверяем что подписка активирована
        $user->refresh();
        $this->assertEquals($subscription->id, $user->subscription_id);
        $this->assertNotNull($user->subscription_time_start);
        $this->assertNotNull($user->subscription_time_end);
        $this->assertFalse($user->subscription_is_expired);
    }

    /** @test */
    public function it_applies_yearly_discount_correctly()
    {
        $user = User::factory()->create(['available_balance' => 500.00]);
        $subscription = Subscription::factory()->create(['amount' => 50.00]); // $50/месяц

        $result = $this->balanceService->processSubscriptionPaymentFromBalance($user, $subscription, 'year');

        $this->assertTrue($result['success']);

        // Годовая стоимость: 50 * 12 * 0.8 = 480 (20% скидка)
        $expectedAmount = 50.00 * 12 * 0.8;
        $payment = $result['payment'];
        $this->assertEquals($expectedAmount, $payment->amount);

        // Баланс должен уменьшиться на сумму со скидкой
        $user->refresh();
        $this->assertEquals(500.00 - $expectedAmount, $user->available_balance);
    }

    /** @test */
    public function it_prevents_race_conditions_with_optimistic_locking()
    {
        $user = User::factory()->create(['available_balance' => 100.00, 'balance_version' => 1]);

        // Симулируем изменение версии (как будто другой процесс изменил баланс)
        User::where('id', $user->id)->update(['balance_version' => 2]);

        $result = $this->balanceService->addToBalance($user, 50.00, 'Test');

        $this->assertFalse($result);

        // Баланс не должен измениться
        $user->refresh();
        $this->assertEquals(100.00, $user->available_balance);
        $this->assertEquals(2, $user->balance_version);
    }
}

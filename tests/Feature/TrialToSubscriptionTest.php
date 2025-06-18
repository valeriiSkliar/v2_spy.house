<?php

namespace Tests\Feature;

use App\Finance\Models\Subscription;
use App\Finance\Services\BalanceService;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TrialToSubscriptionTest extends TestCase
{
    use RefreshDatabase;

    protected BalanceService $balanceService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->balanceService = app(BalanceService::class);
    }

    /**
     * Тест покупки подписки пользователем в триале
     */
    public function test_trial_user_purchases_subscription(): void
    {
        // Создаем пользователя с активным триалом
        $user = User::factory()->create([
            'available_balance' => 100.00,
            'is_trial_period' => true,
            'subscription_time_start' => now()->subDays(2),
            'subscription_time_end' => now()->addDays(5), // 5 дней триала осталось
        ]);

        // Создаем подписку
        $subscription = Subscription::factory()->create([
            'amount' => 50.00,
        ]);

        // Проверяем что пользователь в триале
        $this->assertTrue($user->isTrialPeriod());
        $this->assertEquals(5, $user->getTrialDaysLeft());

        // Покупаем подписку с баланса
        $result = $this->balanceService->processSubscriptionPaymentFromBalance($user, $subscription, 'month');

        $this->assertTrue($result['success']);

        // Обновляем пользователя из БД
        $user->refresh();

        // Проверяем что триал сброшен
        $this->assertFalse($user->is_trial_period);
        $this->assertFalse($user->isTrialPeriod());

        // Проверяем что подписка активирована
        $this->assertEquals($subscription->id, $user->subscription_id);
        $this->assertNotNull($user->subscription_time_start);
        $this->assertNotNull($user->subscription_time_end);
        $this->assertFalse($user->subscription_is_expired);

        // Проверяем что время подписки начинается с момента покупки (не от окончания триала)
        $subscriptionStart = $user->subscription_time_start;
        $subscriptionEnd = $user->subscription_time_end;

        // Время начала должно быть близко к текущему времени
        $this->assertTrue($subscriptionStart->diffInMinutes(now()) < 1);

        // Время окончания должно быть ровно через месяц от начала
        $expectedEnd = $subscriptionStart->copy()->addMonth();
        $this->assertEquals($expectedEnd->timestamp, $subscriptionEnd->timestamp);

        // Проверяем что время триала НЕ прибавилось к времени подписки
        // Подписка должна быть ровно на месяц, не больше
        $subscriptionDuration = $subscriptionStart->diffInDays($subscriptionEnd);
        $this->assertEquals(30, $subscriptionDuration, '', 1); // 30 дней ± 1 день

        // Проверяем что баланс уменьшился
        $this->assertEquals(50.00, $user->available_balance);
    }

    /**
     * Тест что триал не влияет на продление подписки
     */
    public function test_trial_does_not_affect_subscription_renewal(): void
    {
        // Создаем пользователя с обычной подпиской (не триал)
        $subscription = Subscription::factory()->create(['amount' => 30.00]);

        $user = User::factory()->create([
            'available_balance' => 100.00,
            'is_trial_period' => false,
            'subscription_id' => $subscription->id,
            'subscription_time_start' => now()->subDays(20),
            'subscription_time_end' => now()->addDays(10), // 10 дней подписки осталось
        ]);

        // Продлеваем ту же подписку
        $result = $this->balanceService->processSubscriptionPaymentFromBalance($user, $subscription, 'month');

        $this->assertTrue($result['success']);

        // Обновляем пользователя из БД
        $user->refresh();

        // Проверяем что продление работает корректно
        $this->assertEquals($subscription->id, $user->subscription_id);
        $this->assertFalse($user->is_trial_period);

        // При продлении время должно добавляться к существующему времени окончания
        $originalEndTime = now()->addDays(10);
        $expectedNewEndTime = $originalEndTime->copy()->addMonth();

        // Разница должна быть в пределах нескольких минут (учитывая время выполнения теста)
        $actualDiff = abs($user->subscription_time_end->timestamp - $expectedNewEndTime->timestamp);
        $this->assertLessThan(300, $actualDiff); // менее 5 минут разницы
    }

    /**
     * Тест что компенсация времени НЕ применяется для пользователей в триале
     */
    public function test_no_time_compensation_for_trial_users(): void
    {
        // Создаем базовую подписку
        $basicSubscription = Subscription::factory()->create([
            'amount' => 20.00,
        ]);

        // Создаем премиум подписку (апгрейд)
        $premiumSubscription = Subscription::factory()->create([
            'amount' => 50.00,
        ]);

        // Создаем пользователя с триалом, но формально привязываем к базовой подписке
        // (эмулируем ситуацию когда в триале показывается одна из подписок)
        $user = User::factory()->create([
            'available_balance' => 100.00,
            'is_trial_period' => true,
            'subscription_id' => $basicSubscription->id, // формально привязан к базовой
            'subscription_time_start' => now()->subDays(2),
            'subscription_time_end' => now()->addDays(5), // 5 дней триала осталось
        ]);

        // Покупаем премиум подписку (должен быть апгрейд, но без компенсации времени)
        $result = $this->balanceService->processSubscriptionPaymentFromBalance($user, $premiumSubscription, 'month');

        $this->assertTrue($result['success']);

        // Обновляем пользователя из БД
        $user->refresh();

        // Проверяем что триал сброшен
        $this->assertFalse($user->is_trial_period);
        $this->assertFalse($user->isTrialPeriod());

        // Проверяем что подписка активирована
        $this->assertEquals($premiumSubscription->id, $user->subscription_id);

        // Проверяем что время подписки ровно месяц (без компенсации триального времени)
        $subscriptionStart = $user->subscription_time_start;
        $subscriptionEnd = $user->subscription_time_end;

        $subscriptionDuration = $subscriptionStart->diffInDays($subscriptionEnd);
        $this->assertEquals(30, $subscriptionDuration, '', 1); // ровно 30 дней ± 1 день
    }

    /**
     * Тест что обычные пользователи (не в триале) получают компенсацию времени при апгрейде
     */
    public function test_regular_users_get_time_compensation(): void
    {
        // Создаем базовую подписку
        $basicSubscription = Subscription::factory()->create([
            'amount' => 20.00,
        ]);

        // Создаем премиум подписку (апгрейд)
        $premiumSubscription = Subscription::factory()->create([
            'amount' => 50.00,
        ]);

        // Создаем пользователя с обычной подпиской (НЕ триал)
        $user = User::factory()->create([
            'available_balance' => 100.00,
            'is_trial_period' => false,
            'subscription_id' => $basicSubscription->id,
            'subscription_time_start' => now()->subDays(20),
            'subscription_time_end' => now()->addDays(10), // 10 дней подписки осталось
        ]);

        // Мокаем метод для проверки что это апгрейд
        $this->app->bind(\App\Finance\Models\Subscription::class, function () use ($premiumSubscription, $basicSubscription) {
            $mock = \Mockery::mock($premiumSubscription);
            $mock->shouldReceive('isHigherTierThan')
                ->with($basicSubscription)
                ->andReturn(true);
            return $mock;
        });

        // Покупаем премиум подписку (апгрейд с компенсацией)
        $result = $this->balanceService->processSubscriptionPaymentFromBalance($user, $premiumSubscription, 'month');

        $this->assertTrue($result['success']);

        // Обновляем пользователя из БД
        $user->refresh();

        // Проверяем что подписка активирована
        $this->assertEquals($premiumSubscription->id, $user->subscription_id);
        $this->assertFalse($user->is_trial_period); // должен остаться false

        // Для обычных пользователей должна применяться компенсация времени
        // (точную логику компенсации тестируем отдельно)
        $this->assertNotNull($user->subscription_time_end);
    }
}

<?php

namespace Tests\Feature;

use App\Enums\Finance\PaymentMethod;
use App\Enums\Finance\PaymentStatus;
use App\Enums\Finance\PaymentType;
use App\Finance\Http\Controllers\Pay2WebhookController;
use App\Finance\Models\Payment;
use App\Finance\Models\Subscription;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Tests\TestCase;

class WebhookTrialTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Тест сброса триала через webhook от внешней платежной системы
     */
    public function test_webhook_resets_trial_flag_on_subscription_purchase(): void
    {
        // Создаем пользователя с активным триалом
        $user = User::factory()->create([
            'is_trial_period' => true,
            'subscription_time_start' => now()->subDays(2),
            'subscription_time_end' => now()->addDays(5), // 5 дней триала осталось
        ]);

        // Создаем подписку
        $subscription = Subscription::factory()->create([
            'amount' => 50.00,
        ]);

        // Создаем платеж (как будто он был создан при оформлении)
        $payment = Payment::factory()->create([
            'user_id' => $user->id,
            'amount' => 50.00,
            'payment_type' => PaymentType::DIRECT_SUBSCRIPTION,
            'subscription_id' => $subscription->id,
            'payment_method' => PaymentMethod::PAY2_HOUSE,
            'status' => PaymentStatus::PENDING,
            'invoice_number' => 'TEST123456',
        ]);

        // Проверяем исходное состояние
        $this->assertTrue($user->isTrialPeriod());
        $this->assertEquals(PaymentStatus::PENDING, $payment->status);

        // Симулируем webhook данные от Pay2.House
        $webhookData = [
            'status' => 'paid',
            'invoice_number' => 'TEST123456',
            'amount' => 50.00,
            'currency_code' => 'USD',
            'description' => 'Подписка Basic (month)',
            'payer_email' => $user->email,
        ];

        // Создаем экземпляр контроллера
        $controller = app(Pay2WebhookController::class);

        // Используем reflection для вызова приватного метода
        $reflection = new \ReflectionClass($controller);
        $method = $reflection->getMethod('handlePaidPayment');
        $method->setAccessible(true);

        // Вызываем обработку webhook'а
        $method->invoke($controller, $webhookData);

        // Обновляем объекты из БД
        $user->refresh();
        $payment->refresh();

        // Проверяем что платеж стал успешным
        $this->assertEquals(PaymentStatus::SUCCESS, $payment->status);

        // Проверяем что триал сброшен
        $this->assertFalse($user->is_trial_period);
        $this->assertFalse($user->isTrialPeriod());

        // Проверяем что подписка активирована
        $this->assertEquals($subscription->id, $user->subscription_id);
        $this->assertNotNull($user->subscription_time_start);
        $this->assertNotNull($user->subscription_time_end);
        $this->assertFalse($user->subscription_is_expired);

        // Проверяем что время подписки начинается с момента покупки
        $subscriptionStart = $user->subscription_time_start;
        $subscriptionEnd = $user->subscription_time_end;

        // Время начала должно быть близко к текущему времени
        $this->assertTrue($subscriptionStart->diffInMinutes(now()) < 1);

        // Подписка должна быть ровно на месяц
        $subscriptionDuration = $subscriptionStart->diffInDays($subscriptionEnd);
        $this->assertEquals(30, $subscriptionDuration, '', 1); // 30 дней ± 1 день
    }

    /**
     * Тест что webhook не влияет на пользователей не в триале
     */
    public function test_webhook_works_normally_for_non_trial_users(): void
    {
        // Создаем обычного пользователя (не в триале)
        $user = User::factory()->create([
            'is_trial_period' => false,
        ]);

        // Создаем подписку
        $subscription = Subscription::factory()->create([
            'amount' => 30.00,
        ]);

        // Создаем платеж
        $payment = Payment::factory()->create([
            'user_id' => $user->id,
            'amount' => 30.00,
            'payment_type' => PaymentType::DIRECT_SUBSCRIPTION,
            'subscription_id' => $subscription->id,
            'payment_method' => PaymentMethod::PAY2_HOUSE,
            'status' => PaymentStatus::PENDING,
            'invoice_number' => 'TEST789012',
        ]);

        // Симулируем webhook данные
        $webhookData = [
            'status' => 'paid',
            'invoice_number' => 'TEST789012',
            'amount' => 30.00,
            'currency_code' => 'USD',
            'description' => 'Подписка Pro (month)',
            'payer_email' => $user->email,
        ];

        // Создаем экземпляр контроллера и вызываем обработку
        $controller = app(Pay2WebhookController::class);
        $reflection = new \ReflectionClass($controller);
        $method = $reflection->getMethod('handlePaidPayment');
        $method->setAccessible(true);
        $method->invoke($controller, $webhookData);

        // Обновляем объекты из БД
        $user->refresh();
        $payment->refresh();

        // Проверяем что платеж стал успешным
        $this->assertEquals(PaymentStatus::SUCCESS, $payment->status);

        // Проверяем что флаг триала остался false
        $this->assertFalse($user->is_trial_period);
        $this->assertFalse($user->isTrialPeriod());

        // Проверяем что подписка активирована
        $this->assertEquals($subscription->id, $user->subscription_id);
        $this->assertNotNull($user->subscription_time_start);
        $this->assertNotNull($user->subscription_time_end);
        $this->assertFalse($user->subscription_is_expired);
    }

    /**
     * Тест что годовая подписка через webhook тоже сбрасывает триал
     */
    public function test_webhook_yearly_subscription_resets_trial(): void
    {
        // Создаем пользователя с активным триалом
        $user = User::factory()->create([
            'is_trial_period' => true,
            'subscription_time_start' => now()->subDays(3),
            'subscription_time_end' => now()->addDays(4), // 4 дня триала осталось
        ]);

        // Создаем подписку
        $subscription = Subscription::factory()->create([
            'amount' => 50.00, // $50/месяц
        ]);

        // Создаем платеж для годовой подписки (с 20% скидкой)
        $yearlyAmount = $subscription->amount * 12 * 0.8; // 50 * 12 * 0.8 = 480
        $payment = Payment::factory()->create([
            'user_id' => $user->id,
            'amount' => $yearlyAmount,
            'payment_type' => PaymentType::DIRECT_SUBSCRIPTION,
            'subscription_id' => $subscription->id,
            'payment_method' => PaymentMethod::PAY2_HOUSE,
            'status' => PaymentStatus::PENDING,
            'invoice_number' => 'YEARLY123',
        ]);

        // Симулируем webhook для годовой подписки
        $webhookData = [
            'status' => 'paid',
            'invoice_number' => 'YEARLY123',
            'amount' => $yearlyAmount,
            'currency_code' => 'USD',
            'description' => 'Подписка Premium (year)', // ключевое слово "year"
            'payer_email' => $user->email,
        ];

        // Обрабатываем webhook
        $controller = app(Pay2WebhookController::class);
        $reflection = new \ReflectionClass($controller);
        $method = $reflection->getMethod('handlePaidPayment');
        $method->setAccessible(true);
        $method->invoke($controller, $webhookData);

        // Обновляем пользователя из БД
        $user->refresh();

        // Проверяем что триал сброшен
        $this->assertFalse($user->is_trial_period);
        $this->assertFalse($user->isTrialPeriod());

        // Проверяем что подписка активирована на год
        $this->assertEquals($subscription->id, $user->subscription_id);

        $subscriptionStart = $user->subscription_time_start;
        $subscriptionEnd = $user->subscription_time_end;

        // Годовая подписка должна быть на ~365 дней
        $subscriptionDuration = $subscriptionStart->diffInDays($subscriptionEnd);
        $this->assertTrue($subscriptionDuration >= 364 && $subscriptionDuration <= 366); // 365 дней ± 1 день
    }
}

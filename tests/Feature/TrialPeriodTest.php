<?php

namespace Tests\Feature;

use App\Jobs\ExpireTrialPeriodsJob;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class TrialPeriodTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Тест активации триала после подтверждения email
     */
    public function test_trial_period_activated_after_email_verification(): void
    {
        // Создаем пользователя без подтвержденного email
        /** @var User $user */
        $user = User::factory()->create([
            'email_verified_at' => null,
            'is_trial_period' => false,
        ]);

        $this->assertFalse($user->isTrialPeriod());

        // Устанавливаем код верификации в кэш
        $verificationCode = '123456';
        Cache::put('email_verification_code:' . $user->id, $verificationCode, 600);

        // Отправляем POST запрос для подтверждения email
        $response = $this->actingAs($user)->postJson('/email/verify', [
            'verification_code' => $verificationCode,
        ]);

        $response->assertJson(['success' => true]);

        // Обновляем пользователя из БД
        $user->refresh();

        // Проверяем что триал активирован
        $this->assertTrue($user->is_trial_period);
        $this->assertTrue($user->isTrialPeriod());
        $this->assertNotNull($user->subscription_time_start);
        $this->assertNotNull($user->subscription_time_end);
        $this->assertEquals(7, $user->getTrialDaysLeft());
    }

    /**
     * Тест истечения триала
     */
    public function test_trial_period_expires(): void
    {
        // Создаем пользователя с истекшим триалом
        $user = User::factory()->create([
            'is_trial_period' => true,
            'subscription_time_start' => now()->subDays(8),
            'subscription_time_end' => now()->subDays(1),
        ]);

        // Проверяем что триал автоматически считается истекшим
        $this->assertFalse($user->isTrialPeriod());

        // Проверяем что флаг триала сброшен
        $user->refresh();
        $this->assertFalse($user->is_trial_period);
    }

    /**
     * Тест команды истечения триала
     */
    public function test_expire_trial_periods_command(): void
    {
        // Создаем пользователей с активным и истекшим триалом
        $activeTrialUser = User::factory()->create([
            'is_trial_period' => true,
            'subscription_time_start' => now()->subDays(3),
            'subscription_time_end' => now()->addDays(4),
        ]);

        $expiredTrialUser = User::factory()->create([
            'is_trial_period' => true,
            'subscription_time_start' => now()->subDays(8),
            'subscription_time_end' => now()->subDays(1),
        ]);

        // Запускаем команду
        $this->artisan('trial:expire')
            ->assertSuccessful()
            ->expectsOutput('Expired trial periods for 1 users.');

        // Проверяем результаты
        $activeTrialUser->refresh();
        $expiredTrialUser->refresh();

        $this->assertTrue($activeTrialUser->is_trial_period);
        $this->assertFalse($expiredTrialUser->is_trial_period);
    }

    /**
     * Тест job для истечения триала
     */
    public function test_expire_trial_periods_job(): void
    {
        Queue::fake();

        // Запускаем команду с флагом queue
        $this->artisan('trial:expire --queue')
            ->assertSuccessful()
            ->expectsOutput('Trial expiration job dispatched to queue.');

        // Проверяем что job был добавлен в очередь
        Queue::assertPushed(ExpireTrialPeriodsJob::class);
    }

    /**
     * Тест метода currentTariff для триала
     */
    public function test_current_tariff_shows_trial(): void
    {
        $user = User::factory()->create([
            'is_trial_period' => true,
            'subscription_time_start' => now(),
            'subscription_time_end' => now()->addDays(7),
        ]);

        $tariff = $user->currentTariff();

        $this->assertEquals('Trial', $tariff['name']);
        $this->assertEquals('trial', $tariff['css_class']);
        $this->assertTrue($tariff['is_active']);
        $this->assertTrue($tariff['is_trial']);
        $this->assertEquals('Триал активен', $tariff['status']);
    }

    /**
     * Тест что триал не активируется если пользователь уже имеет активную подписку
     */
    public function test_trial_not_activated_if_user_has_active_subscription(): void
    {
        /** @var User $user */
        $user = User::factory()->create([
            'email_verified_at' => null,
            'is_trial_period' => false,
            'subscription_id' => 1,
            'subscription_time_start' => now()->subDays(1),
            'subscription_time_end' => now()->addDays(30),
            'subscription_is_expired' => false,
        ]);

        // Устанавливаем код верификации в кэш
        $verificationCode = '123456';
        Cache::put('email_verification_code:' . $user->id, $verificationCode, 600);

        // Отправляем POST запрос для подтверждения email
        $response = $this->actingAs($user)->postJson('/email/verify', [
            'verification_code' => $verificationCode,
        ]);

        $response->assertJson(['success' => true]);

        // Обновляем пользователя из БД
        $user->refresh();

        // Проверяем что триал НЕ активирован
        $this->assertFalse($user->is_trial_period);
        $this->assertFalse($user->isTrialPeriod());
    }
}

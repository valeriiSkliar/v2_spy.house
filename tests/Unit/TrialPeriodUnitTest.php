<?php

namespace Tests\Unit;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TrialPeriodUnitTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Тест активации триала
     */
    public function test_trial_period_activation(): void
    {
        $user = User::factory()->create([
            'is_trial_period' => false,
        ]);

        $this->assertFalse($user->isTrialPeriod());

        // Активируем триал
        $user->activateTrialPeriod();

        $this->assertTrue($user->is_trial_period);
        $this->assertTrue($user->trial_period_used);
        $this->assertTrue($user->isTrialPeriod());
        $this->assertNotNull($user->subscription_time_start);
        $this->assertNotNull($user->subscription_time_end);
        $this->assertEquals(7, $user->getTrialDaysLeft());
    }

    /**
     * Тест истечения триала
     */
    public function test_trial_period_expiration(): void
    {
        $user = User::factory()->create([
            'is_trial_period' => true,
            'subscription_time_start' => now()->subDays(8),
            'subscription_time_end' => now()->subDays(1),
        ]);

        // Проверяем что триал автоматически считается истекшим
        $this->assertFalse($user->isTrialPeriod());

        // Проверяем что дней не осталось
        $this->assertEquals(0, $user->getTrialDaysLeft());

        // Обновляем пользователя из БД
        $user->refresh();
        $this->assertFalse($user->is_trial_period);
    }

    /**
     * Тест currentTariff для триала
     */
    public function test_current_tariff_trial(): void
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
        $this->assertNotNull($tariff['expires_at']);
    }

    /**
     * Тест currentTariff для free пользователя
     */
    public function test_current_tariff_free(): void
    {
        $user = User::factory()->create([
            'is_trial_period' => false,
            'subscription_id' => null,
        ]);

        $tariff = $user->currentTariff();

        $this->assertEquals('Free', $tariff['name']);
        $this->assertEquals('free', $tariff['css_class']);
        $this->assertFalse($tariff['is_active']);
        $this->assertFalse($tariff['is_trial']);
        $this->assertEquals('Не активно', $tariff['status']);
    }

    /**
     * Тест получения оставшихся дней триала
     */
    public function test_trial_days_left(): void
    {
        // Тест для активного триала
        $user = User::factory()->create([
            'is_trial_period' => true,
            'subscription_time_start' => now()->subDays(2),
            'subscription_time_end' => now()->addDays(5),
        ]);

        $this->assertEquals(5, $user->getTrialDaysLeft());

        // Тест для неактивного триала
        $userNoTrial = User::factory()->create([
            'is_trial_period' => false,
        ]);

        $this->assertEquals(0, $userNoTrial->getTrialDaysLeft());
    }

    /**
     * Тест проверки использования триала
     */
    public function test_has_trial_period_been_used(): void
    {
        // Пользователь без использования триала
        $userWithoutTrial = User::factory()->create([
            'trial_period_used' => false,
        ]);

        $this->assertFalse($userWithoutTrial->hasTrialPeriodBeenUsed());

        // Пользователь с использованным триалом
        $userWithUsedTrial = User::factory()->create([
            'trial_period_used' => true,
        ]);

        $this->assertTrue($userWithUsedTrial->hasTrialPeriodBeenUsed());
    }
}

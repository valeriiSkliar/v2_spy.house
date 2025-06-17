<?php

namespace Tests\Unit\Models;

use App\Finance\Models\Subscription;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserSubscriptionTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_returns_false_for_active_subscription_when_user_has_no_subscription()
    {
        $user = User::factory()->create([
            'subscription_id' => null,
            'subscription_time_end' => null,
        ]);

        $this->assertFalse($user->hasActiveSubscription());
        $this->assertFalse($user->hasTariff());
    }

    /** @test */
    public function it_returns_false_for_active_subscription_when_subscription_is_expired()
    {
        $subscription = Subscription::factory()->create();
        $user = User::factory()->create([
            'subscription_id' => $subscription->id,
            'subscription_time_end' => Carbon::now()->subDay(),
            'subscription_is_expired' => false,
        ]);

        $this->assertFalse($user->hasActiveSubscription());
        $this->assertFalse($user->hasTariff());
    }

    /** @test */
    public function it_returns_false_for_active_subscription_when_subscription_is_marked_expired()
    {
        $subscription = Subscription::factory()->create();
        $user = User::factory()->create([
            'subscription_id' => $subscription->id,
            'subscription_time_end' => Carbon::now()->addDay(),
            'subscription_is_expired' => true,
        ]);

        $this->assertFalse($user->hasActiveSubscription());
        $this->assertFalse($user->hasTariff());
    }

    /** @test */
    public function it_returns_true_for_active_subscription_when_all_conditions_are_met()
    {
        $subscription = Subscription::factory()->create();
        $user = User::factory()->create([
            'subscription_id' => $subscription->id,
            'subscription_time_end' => Carbon::now()->addDays(30),
            'subscription_is_expired' => false,
        ]);

        $this->assertTrue($user->hasActiveSubscription());
        $this->assertTrue($user->hasTariff());
    }

    /** @test */
    public function it_can_check_specific_subscription()
    {
        $subscription1 = Subscription::factory()->create();
        $subscription2 = Subscription::factory()->create();

        $user = User::factory()->create([
            'subscription_id' => $subscription1->id,
            'subscription_time_end' => Carbon::now()->addDays(30),
            'subscription_is_expired' => false,
        ]);

        $this->assertTrue($user->hasTariff($subscription1->id));
        $this->assertFalse($user->hasTariff($subscription2->id));
    }

    /** @test */
    public function it_returns_correct_current_tariff_for_active_subscription()
    {
        $subscription = Subscription::factory()->create(['name' => 'Premium']);
        $user = User::factory()->create([
            'subscription_id' => $subscription->id,
            'subscription_time_end' => Carbon::now()->addDays(30),
            'subscription_is_expired' => false,
        ]);

        $currentTariff = $user->currentTariff();

        $this->assertEquals($subscription->id, $currentTariff['id']);
        $this->assertEquals('Premium', $currentTariff['name']);
        $this->assertEquals('premium', $currentTariff['css_class']);
        $this->assertEquals('Активная', $currentTariff['status']);
        $this->assertTrue($currentTariff['is_active']);
        $this->assertNotNull($currentTariff['expires_at']);
    }

    /** @test */
    public function it_returns_free_tariff_when_no_active_subscription()
    {
        $user = User::factory()->create([
            'subscription_id' => null,
            'subscription_time_end' => null,
        ]);

        $currentTariff = $user->currentTariff();

        $this->assertNull($currentTariff['id']);
        $this->assertEquals('Free', $currentTariff['name']);
        $this->assertEquals('free', $currentTariff['css_class']);
        $this->assertEquals('Не активно', $currentTariff['status']);
        $this->assertFalse($currentTariff['is_active']);
        $this->assertNull($currentTariff['expires_at']);
    }

    /** @test */
    public function it_formats_balance_correctly()
    {
        $user = User::factory()->create(['available_balance' => 123.45]);

        $this->assertEquals('$123.45', $user->getFormattedBalance());
    }

    /** @test */
    public function it_formats_zero_balance_correctly()
    {
        $user = User::factory()->create(['available_balance' => 0]);

        $this->assertEquals('$0.00', $user->getFormattedBalance());
    }

    /** @test */
    public function it_checks_sufficient_balance_correctly()
    {
        $user = User::factory()->create(['available_balance' => 100.00]);

        $this->assertTrue($user->hasSufficientBalance(50.00));
        $this->assertTrue($user->hasSufficientBalance(100.00));
        $this->assertFalse($user->hasSufficientBalance(150.00));
    }

    /** @test */
    public function it_returns_correct_tariff_expiration_date()
    {
        $expirationDate = Carbon::now()->addDays(30);
        $user = User::factory()->create([
            'subscription_time_end' => $expirationDate,
        ]);

        $this->assertEquals($expirationDate->format('d.m.Y'), $user->tariffExpiresAt());
    }

    /** @test */
    public function it_returns_null_when_no_expiration_date()
    {
        $user = User::factory()->create([
            'subscription_time_end' => null,
        ]);

        $this->assertNull($user->tariffExpiresAt());
    }
}

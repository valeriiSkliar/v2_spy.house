<?php

namespace Tests\Unit;

use App\Finance\Models\Subscription;
use App\Models\User;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class UserSimilarCreativesAccessTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function user_without_subscription_cannot_view_similar_creatives()
    {
        $user = User::factory()->create([
            'subscription_id' => null,
            'subscription_time_start' => null,
            'subscription_time_end' => null,
        ]);

        $this->assertFalse($user->canViewSimilarCreatives());
    }

    /** @test */
    public function user_with_trial_period_cannot_view_similar_creatives()
    {
        $user = User::factory()->create([
            'is_trial_period' => true,
            'subscription_time_start' => now(),
            'subscription_time_end' => now()->addDays(7),
        ]);

        $this->assertFalse($user->canViewSimilarCreatives());
    }

    /** @test */
    public function user_with_premium_subscription_can_view_similar_creatives()
    {
        $premiumSubscription = Subscription::factory()->create([
            'name' => 'Premium',
            'status' => 'active',
        ]);

        $user = User::factory()->create([
            'subscription_id' => $premiumSubscription->id,
            'subscription_time_start' => now()->subDay(),
            'subscription_time_end' => now()->addMonth(),
            'subscription_is_expired' => false,
        ]);

        $this->assertTrue($user->canViewSimilarCreatives());
    }

    /** @test */
    public function user_with_enterprise_subscription_can_view_similar_creatives()
    {
        $enterpriseSubscription = Subscription::factory()->create([
            'name' => 'Enterprise',
            'status' => 'active',
        ]);

        $user = User::factory()->create([
            'subscription_id' => $enterpriseSubscription->id,
            'subscription_time_start' => now()->subDay(),
            'subscription_time_end' => now()->addMonth(),
            'subscription_is_expired' => false,
        ]);

        $this->assertTrue($user->canViewSimilarCreatives());
    }

    /** @test */
    public function user_with_basic_subscription_cannot_view_similar_creatives()
    {
        $basicSubscription = Subscription::factory()->create([
            'name' => 'Basic',
            'status' => 'active',
        ]);

        $user = User::factory()->create([
            'subscription_id' => $basicSubscription->id,
            'subscription_time_start' => now()->subDay(),
            'subscription_time_end' => now()->addMonth(),
            'subscription_is_expired' => false,
        ]);

        $this->assertFalse($user->canViewSimilarCreatives());
    }

    /** @test */
    public function user_with_expired_premium_subscription_cannot_view_similar_creatives()
    {
        $premiumSubscription = Subscription::factory()->create([
            'name' => 'Premium',
            'status' => 'active',
        ]);

        $user = User::factory()->create([
            'subscription_id' => $premiumSubscription->id,
            'subscription_time_start' => now()->subMonth(),
            'subscription_time_end' => now()->subDay(), // Истекла вчера
            'subscription_is_expired' => true,
        ]);

        $this->assertFalse($user->canViewSimilarCreatives());
    }

    /** @test */
    public function current_tariff_includes_similar_creatives_access_info()
    {
        // Тест для Premium пользователя
        $premiumSubscription = Subscription::factory()->create([
            'name' => 'Premium',
            'status' => 'active',
        ]);

        $premiumUser = User::factory()->create([
            'subscription_id' => $premiumSubscription->id,
            'subscription_time_start' => now()->subDay(),
            'subscription_time_end' => now()->addMonth(),
            'subscription_is_expired' => false,
        ]);

        $tariffInfo = $premiumUser->currentTariff();
        $this->assertTrue($tariffInfo['show_similar_creatives']);

        // Тест для Free пользователя
        $freeUser = User::factory()->create([
            'subscription_id' => null,
        ]);

        $tariffInfo = $freeUser->currentTariff();
        $this->assertFalse($tariffInfo['show_similar_creatives']);
    }
}

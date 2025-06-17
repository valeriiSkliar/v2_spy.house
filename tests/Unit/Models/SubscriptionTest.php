<?php

namespace Tests\Unit\Models;

use App\Finance\Models\Subscription;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SubscriptionTest extends TestCase
{
    use RefreshDatabase;

    public function test_subscription_can_be_created_with_factory(): void
    {
        $subscription = Subscription::factory()->create();

        $this->assertInstanceOf(Subscription::class, $subscription);
        $this->assertDatabaseHas('subscriptions', [
            'id' => $subscription->id,
            'name' => $subscription->name,
        ]);
    }

    public function test_is_active_returns_true_for_active_subscription(): void
    {
        $subscription = Subscription::factory()->active()->create();

        $this->assertTrue($subscription->isActive());
    }

    public function test_is_active_returns_false_for_inactive_subscription(): void
    {
        $subscription = Subscription::factory()->inactive()->create();

        $this->assertFalse($subscription->isActive());
    }

    public function test_get_formatted_amount_returns_formatted_price(): void
    {
        $subscription = Subscription::factory()->create(['amount' => 99.99]);

        $this->assertEquals('$99.99', $subscription->getFormattedAmount());
    }

    public function test_get_discounted_amount_without_discount(): void
    {
        $subscription = Subscription::factory()->withoutDiscount()->create(['amount' => 100.00]);

        $this->assertEquals(100.00, $subscription->getDiscountedAmount());
    }

    public function test_get_discounted_amount_with_discount(): void
    {
        $subscription = Subscription::factory()->withDiscount(20)->create(['amount' => 100.00]);

        $this->assertEquals(80.00, $subscription->getDiscountedAmount());
    }

    public function test_get_formatted_discounted_amount_returns_formatted_price(): void
    {
        $subscription = Subscription::factory()->withDiscount(15)->create(['amount' => 100.00]);

        $this->assertEquals('$85.00', $subscription->getFormattedDiscountedAmount());
    }

    public function test_casts_are_working_correctly(): void
    {
        $subscription = Subscription::factory()->create([
            'amount' => 99.99,
            'early_discount' => 10.5,
            'api_request_count' => 1000,
            'search_request_count' => 500,
        ]);

        $this->assertIsFloat($subscription->amount);
        $this->assertIsFloat($subscription->early_discount);
        $this->assertIsInt($subscription->api_request_count);
        $this->assertIsInt($subscription->search_request_count);
    }

    public function test_fillable_attributes_can_be_mass_assigned(): void
    {
        $attributes = [
            'name' => 'Test Subscription',
            'amount' => 49.99,
            'early_discount' => 15.0,
            'api_request_count' => 2000,
            'search_request_count' => 1000,
            'status' => 'active',
        ];

        $subscription = new Subscription($attributes);

        $this->assertEquals('Test Subscription', $subscription->name);
        $this->assertEquals(49.99, $subscription->amount);
        $this->assertEquals(15.0, $subscription->early_discount);
        $this->assertEquals(2000, $subscription->api_request_count);
        $this->assertEquals(1000, $subscription->search_request_count);
        $this->assertEquals('active', $subscription->status);
    }
}

<?php

namespace Tests\Feature\Finance;

use App\Finance\Models\Payment;
use App\Finance\Models\Subscription;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserFinancialRelationshipsTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_have_multiple_payments(): void
    {
        $user = User::factory()->create();

        Payment::factory()->count(3)->create(['user_id' => $user->id]);

        $this->assertCount(3, $user->payments);
    }

    public function test_user_successful_payments_scope_works(): void
    {
        $user = User::factory()->create();

        Payment::factory()->successful()->count(2)->create(['user_id' => $user->id]);
        Payment::factory()->pending()->create(['user_id' => $user->id]);
        Payment::factory()->failed()->create(['user_id' => $user->id]);

        $this->assertCount(2, $user->successfulPayments);
    }

    public function test_user_pending_payments_scope_works(): void
    {
        $user = User::factory()->create();

        Payment::factory()->successful()->create(['user_id' => $user->id]);
        Payment::factory()->pending()->count(2)->create(['user_id' => $user->id]);
        Payment::factory()->failed()->create(['user_id' => $user->id]);

        $this->assertCount(2, $user->pendingPayments);
    }

    public function test_user_deposit_payments_scope_works(): void
    {
        $user = User::factory()->create();

        Payment::factory()->deposit()->count(3)->create(['user_id' => $user->id]);
        Payment::factory()->directSubscription()->create(['user_id' => $user->id]);

        $this->assertCount(3, $user->depositPayments);
    }

    public function test_user_subscription_payments_scope_works(): void
    {
        $user = User::factory()->create();

        Payment::factory()->deposit()->create(['user_id' => $user->id]);
        Payment::factory()->directSubscription()->count(2)->create(['user_id' => $user->id]);

        $this->assertCount(2, $user->subscriptionPayments);
    }

    public function test_user_financial_fields_exist_and_have_correct_defaults(): void
    {
        $user = User::factory()->create();

        $this->assertEquals(0.00, $user->available_balance);
        $this->assertNull($user->subscription_id);
        $this->assertNull($user->subscription_time_start);
        $this->assertNull($user->subscription_time_end);
        $this->assertFalse($user->subscription_is_expired);
        $this->assertNull($user->queued_subscription_id);
        $this->assertEquals(1, $user->balance_version);
    }

    public function test_user_can_be_assigned_subscription(): void
    {
        $user = User::factory()->create();
        $subscription = Subscription::factory()->create();

        $user->update([
            'subscription_id' => $subscription->id,
            'subscription_time_start' => now(),
            'subscription_time_end' => now()->addMonth(),
            'subscription_is_expired' => false,
        ]);

        $this->assertEquals($subscription->id, $user->subscription_id);
        $this->assertFalse($user->subscription_is_expired);
        $this->assertNotNull($user->subscription_time_start);
        $this->assertNotNull($user->subscription_time_end);
    }

    public function test_user_balance_version_for_optimistic_locking(): void
    {
        $user = User::factory()->create(['balance_version' => 1]);

        // Simulate optimistic locking update
        $user->update([
            'available_balance' => 100.00,
            'balance_version' => $user->balance_version + 1,
        ]);

        $this->assertEquals(100.00, $user->available_balance);
        $this->assertEquals(2, $user->balance_version);
    }
}

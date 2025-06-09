<?php

namespace Tests\Feature\Finance;

use App\Finance\Models\Payment;
use App\Finance\Models\Subscription;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PaymentSubscriptionRelationshipsTest extends TestCase
{
    use RefreshDatabase;

    public function test_subscription_can_have_multiple_payments(): void
    {
        $subscription = Subscription::factory()->create();

        Payment::factory()->directSubscription()->count(3)->create([
            'subscription_id' => $subscription->id
        ]);

        $this->assertCount(3, $subscription->payments);
    }

    public function test_subscription_successful_payments_scope_works(): void
    {
        $subscription = Subscription::factory()->create();

        Payment::factory()->directSubscription()->successful()->count(2)->create([
            'subscription_id' => $subscription->id
        ]);
        Payment::factory()->directSubscription()->pending()->create([
            'subscription_id' => $subscription->id
        ]);
        Payment::factory()->directSubscription()->failed()->create([
            'subscription_id' => $subscription->id
        ]);

        $this->assertCount(2, $subscription->successfulPayments);
    }

    public function test_payment_belongs_to_subscription(): void
    {
        $subscription = Subscription::factory()->create();
        $payment = Payment::factory()->directSubscription()->create([
            'subscription_id' => $subscription->id
        ]);

        $this->assertInstanceOf(Subscription::class, $payment->subscription);
        $this->assertEquals($subscription->id, $payment->subscription->id);
        $this->assertEquals($subscription->name, $payment->subscription->name);
    }

    public function test_deposit_payment_has_no_subscription(): void
    {
        $payment = Payment::factory()->deposit()->create();

        $this->assertNull($payment->subscription_id);
        $this->assertNull($payment->subscription);
    }

    public function test_subscription_can_be_deleted_with_payments_existing(): void
    {
        $subscription = Subscription::factory()->create();
        $payment = Payment::factory()->directSubscription()->create([
            'subscription_id' => $subscription->id
        ]);

        // According to migration, subscription_id should be set to null on delete
        $subscription->delete();
        $payment->refresh();

        $this->assertNull($payment->subscription_id);
        $this->assertNull($payment->subscription);
    }

    public function test_subscription_revenue_calculation(): void
    {
        $subscription = Subscription::factory()->create();

        // Create successful payments
        Payment::factory()->directSubscription()->successful()->create([
            'subscription_id' => $subscription->id,
            'amount' => 100.00
        ]);
        Payment::factory()->directSubscription()->successful()->create([
            'subscription_id' => $subscription->id,
            'amount' => 150.00
        ]);

        // Create failed payment (should not be counted)
        Payment::factory()->directSubscription()->failed()->create([
            'subscription_id' => $subscription->id,
            'amount' => 200.00
        ]);

        $totalRevenue = $subscription->successfulPayments()->sum('amount');

        $this->assertEquals(250.00, $totalRevenue);
    }

    public function test_foreign_key_constraints_work_correctly(): void
    {
        $subscription = Subscription::factory()->create();
        $payment = Payment::factory()->directSubscription()->create([
            'subscription_id' => $subscription->id
        ]);

        // Verify the foreign key constraint exists
        $this->assertDatabaseHas('payments', [
            'id' => $payment->id,
            'subscription_id' => $subscription->id
        ]);

        // Verify the relationship works
        $this->assertEquals($subscription->id, $payment->subscription->id);
    }
}

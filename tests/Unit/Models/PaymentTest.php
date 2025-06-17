<?php

namespace Tests\Unit\Models;

use App\Enums\Finance\PaymentMethod;
use App\Enums\Finance\PaymentStatus;
use App\Enums\Finance\PaymentType;
use App\Finance\Models\Payment;
use App\Finance\Models\Subscription;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PaymentTest extends TestCase
{
    use RefreshDatabase;

    public function test_payment_can_be_created_with_factory(): void
    {
        $payment = Payment::factory()->create();

        $this->assertInstanceOf(Payment::class, $payment);
        $this->assertDatabaseHas('payments', [
            'id' => $payment->id,
            'user_id' => $payment->user_id,
        ]);
    }

    public function test_payment_generates_webhook_token_automatically(): void
    {
        $payment = Payment::factory()->create();

        $this->assertNotNull($payment->webhook_token);
        $this->assertEquals(64, strlen($payment->webhook_token));
    }

    public function test_payment_generates_idempotency_key_automatically(): void
    {
        $payment = Payment::factory()->create();

        $this->assertNotNull($payment->idempotency_key);
        $this->assertMatchesRegularExpression('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/', $payment->idempotency_key);
    }

    public function test_is_successful_returns_correct_boolean(): void
    {
        $successfulPayment = Payment::factory()->successful()->create();
        $pendingPayment = Payment::factory()->pending()->create();
        $failedPayment = Payment::factory()->failed()->create();

        $this->assertTrue($successfulPayment->isSuccessful());
        $this->assertFalse($pendingPayment->isSuccessful());
        $this->assertFalse($failedPayment->isSuccessful());
    }

    public function test_is_pending_returns_correct_boolean(): void
    {
        $successfulPayment = Payment::factory()->successful()->create();
        $pendingPayment = Payment::factory()->pending()->create();
        $failedPayment = Payment::factory()->failed()->create();

        $this->assertFalse($successfulPayment->isPending());
        $this->assertTrue($pendingPayment->isPending());
        $this->assertFalse($failedPayment->isPending());
    }

    public function test_is_failed_returns_correct_boolean(): void
    {
        $successfulPayment = Payment::factory()->successful()->create();
        $pendingPayment = Payment::factory()->pending()->create();
        $failedPayment = Payment::factory()->failed()->create();

        $this->assertFalse($successfulPayment->isFailed());
        $this->assertFalse($pendingPayment->isFailed());
        $this->assertTrue($failedPayment->isFailed());
    }

    public function test_is_deposit_returns_correct_boolean(): void
    {
        $depositPayment = Payment::factory()->deposit()->create();
        $subscriptionPayment = Payment::factory()->directSubscription()->create();

        $this->assertTrue($depositPayment->isDeposit());
        $this->assertFalse($subscriptionPayment->isDeposit());
    }

    public function test_is_direct_subscription_returns_correct_boolean(): void
    {
        $depositPayment = Payment::factory()->deposit()->create();
        $subscriptionPayment = Payment::factory()->directSubscription()->create();

        $this->assertFalse($depositPayment->isDirectSubscription());
        $this->assertTrue($subscriptionPayment->isDirectSubscription());
    }

    public function test_get_formatted_amount_returns_formatted_price(): void
    {
        $payment = Payment::factory()->create(['amount' => 123.45]);

        $this->assertEquals('$123.45', $payment->getFormattedAmount());
    }

    public function test_mark_as_successful_updates_status_and_processed_time(): void
    {
        $payment = Payment::factory()->pending()->create();

        $result = $payment->markAsSuccessful();

        $this->assertTrue($result);
        $this->assertEquals(PaymentStatus::SUCCESS, $payment->status);
        $this->assertNotNull($payment->webhook_processed_at);
    }

    public function test_mark_as_failed_updates_status_and_processed_time(): void
    {
        $payment = Payment::factory()->pending()->create();

        $result = $payment->markAsFailed();

        $this->assertTrue($result);
        $this->assertEquals(PaymentStatus::FAILED, $payment->status);
        $this->assertNotNull($payment->webhook_processed_at);
    }

    public function test_belongs_to_user_relationship(): void
    {
        $user = User::factory()->create();
        $payment = Payment::factory()->create(['user_id' => $user->id]);

        $this->assertInstanceOf(User::class, $payment->user);
        $this->assertEquals($user->id, $payment->user->id);
    }

    public function test_belongs_to_subscription_relationship(): void
    {
        $subscription = Subscription::factory()->create();
        $payment = Payment::factory()->directSubscription()->create(['subscription_id' => $subscription->id]);

        $this->assertInstanceOf(Subscription::class, $payment->subscription);
        $this->assertEquals($subscription->id, $payment->subscription->id);
    }

    public function test_scope_successful_filters_successful_payments(): void
    {
        Payment::factory()->successful()->create();
        Payment::factory()->pending()->create();
        Payment::factory()->failed()->create();

        $successfulPayments = Payment::successful()->get();

        $this->assertCount(1, $successfulPayments);
        $this->assertEquals(PaymentStatus::SUCCESS, $successfulPayments->first()->status);
    }

    public function test_scope_pending_filters_pending_payments(): void
    {
        Payment::factory()->successful()->create();
        Payment::factory()->pending()->create();
        Payment::factory()->failed()->create();

        $pendingPayments = Payment::pending()->get();

        $this->assertCount(1, $pendingPayments);
        $this->assertEquals(PaymentStatus::PENDING, $pendingPayments->first()->status);
    }

    public function test_scope_failed_filters_failed_payments(): void
    {
        Payment::factory()->successful()->create();
        Payment::factory()->pending()->create();
        Payment::factory()->failed()->create();

        $failedPayments = Payment::failed()->get();

        $this->assertCount(1, $failedPayments);
        $this->assertEquals(PaymentStatus::FAILED, $failedPayments->first()->status);
    }

    public function test_scope_deposits_filters_deposit_payments(): void
    {
        Payment::factory()->deposit()->create();
        Payment::factory()->directSubscription()->create();

        $depositPayments = Payment::deposits()->get();

        $this->assertCount(1, $depositPayments);
        $this->assertEquals(PaymentType::DEPOSIT, $depositPayments->first()->payment_type);
    }

    public function test_scope_subscriptions_filters_subscription_payments(): void
    {
        Payment::factory()->deposit()->create();
        Payment::factory()->directSubscription()->create();

        $subscriptionPayments = Payment::subscriptions()->get();

        $this->assertCount(1, $subscriptionPayments);
        $this->assertEquals(PaymentType::DIRECT_SUBSCRIPTION, $subscriptionPayments->first()->payment_type);
    }

    public function test_casts_are_working_correctly(): void
    {
        $payment = Payment::factory()->create([
            'amount' => 99.99,
            'payment_type' => PaymentType::DEPOSIT,
            'payment_method' => PaymentMethod::USDT,
            'status' => PaymentStatus::SUCCESS,
        ]);

        $this->assertIsFloat($payment->amount);
        $this->assertInstanceOf(PaymentType::class, $payment->payment_type);
        $this->assertInstanceOf(PaymentMethod::class, $payment->payment_method);
        $this->assertInstanceOf(PaymentStatus::class, $payment->status);
    }
}

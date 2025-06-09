<?php

namespace Tests\Unit\Models;

use App\Enums\Finance\PaymentMethod;
use App\Enums\Finance\PaymentStatus;
use App\Enums\Finance\PaymentType;
use App\Finance\Models\Payment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PaymentValidationTest extends TestCase
{
    use RefreshDatabase;

    public function test_deposit_payment_cannot_use_user_balance_method(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Deposit payments can only use USDT or PAY2_HOUSE payment methods.');

        Payment::factory()->create([
            'payment_type' => PaymentType::DEPOSIT,
            'payment_method' => PaymentMethod::USER_BALANCE,
        ]);
    }

    public function test_deposit_payment_can_use_usdt_method(): void
    {
        $payment = Payment::factory()->create([
            'payment_type' => PaymentType::DEPOSIT,
            'payment_method' => PaymentMethod::USDT,
        ]);

        $this->assertEquals(PaymentType::DEPOSIT, $payment->payment_type);
        $this->assertEquals(PaymentMethod::USDT, $payment->payment_method);
    }

    public function test_deposit_payment_can_use_pay2_house_method(): void
    {
        $payment = Payment::factory()->create([
            'payment_type' => PaymentType::DEPOSIT,
            'payment_method' => PaymentMethod::PAY2_HOUSE,
        ]);

        $this->assertEquals(PaymentType::DEPOSIT, $payment->payment_type);
        $this->assertEquals(PaymentMethod::PAY2_HOUSE, $payment->payment_method);
    }

    public function test_subscription_payment_can_use_any_payment_method(): void
    {
        $usdtPayment = Payment::factory()->create([
            'payment_type' => PaymentType::DIRECT_SUBSCRIPTION,
            'payment_method' => PaymentMethod::USDT,
        ]);

        $pay2Payment = Payment::factory()->create([
            'payment_type' => PaymentType::DIRECT_SUBSCRIPTION,
            'payment_method' => PaymentMethod::PAY2_HOUSE,
        ]);

        $balancePayment = Payment::factory()->create([
            'payment_type' => PaymentType::DIRECT_SUBSCRIPTION,
            'payment_method' => PaymentMethod::USER_BALANCE,
        ]);

        $this->assertEquals(PaymentMethod::USDT, $usdtPayment->payment_method);
        $this->assertEquals(PaymentMethod::PAY2_HOUSE, $pay2Payment->payment_method);
        $this->assertEquals(PaymentMethod::USER_BALANCE, $balancePayment->payment_method);
    }

    public function test_webhook_token_is_exactly_64_characters(): void
    {
        $payment = Payment::factory()->create();

        $this->assertIsString($payment->webhook_token);
        $this->assertEquals(64, strlen($payment->webhook_token));
        $this->assertMatchesRegularExpression('/^[a-zA-Z0-9]+$/', $payment->webhook_token);
    }

    public function test_idempotency_key_is_valid_uuid(): void
    {
        $payment = Payment::factory()->create();

        $this->assertNotNull($payment->idempotency_key);

        // Преобразуем в строку для проверки формата UUID
        $uuidString = (string) $payment->idempotency_key;
        $this->assertMatchesRegularExpression('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/', $uuidString);
    }

    public function test_each_payment_has_unique_webhook_token(): void
    {
        $payment1 = Payment::factory()->create();
        $payment2 = Payment::factory()->create();

        $this->assertNotEquals($payment1->webhook_token, $payment2->webhook_token);
    }

    public function test_each_payment_has_unique_idempotency_key(): void
    {
        $payment1 = Payment::factory()->create();
        $payment2 = Payment::factory()->create();

        $this->assertNotEquals((string) $payment1->idempotency_key, (string) $payment2->idempotency_key);
    }

    public function test_custom_webhook_token_is_preserved(): void
    {
        $customToken = 'custom_token_for_testing_12345678901234567890123456789012';

        $payment = Payment::factory()->create([
            'webhook_token' => $customToken,
        ]);

        $this->assertEquals($customToken, $payment->webhook_token);
    }

    public function test_custom_idempotency_key_is_preserved(): void
    {
        $customKey = 'custom-key-12345678-1234-1234-1234-123456789012';

        $payment = Payment::factory()->create([
            'idempotency_key' => $customKey,
        ]);

        $this->assertEquals($customKey, (string) $payment->idempotency_key);
    }

    public function test_amount_is_cast_to_float(): void
    {
        $payment = Payment::factory()->create(['amount' => '123.45']);

        $this->assertIsFloat($payment->amount);
        $this->assertEquals(123.45, $payment->amount);
    }

    public function test_status_changes_with_mark_methods(): void
    {
        $payment = Payment::factory()->pending()->create();

        $this->assertEquals(PaymentStatus::PENDING, $payment->status);

        $payment->markAsSuccessful();
        $this->assertEquals(PaymentStatus::SUCCESS, $payment->status);
        $this->assertNotNull($payment->webhook_processed_at);

        $payment->status = PaymentStatus::PENDING;
        $payment->save();

        $payment->markAsFailed();
        $this->assertEquals(PaymentStatus::FAILED, $payment->status);
        $this->assertNotNull($payment->webhook_processed_at);
    }
}

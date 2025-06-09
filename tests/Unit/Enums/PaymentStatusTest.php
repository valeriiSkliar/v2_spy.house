<?php

namespace Tests\Unit\Enums;

use App\Enums\Finance\PaymentStatus;
use PHPUnit\Framework\TestCase;

class PaymentStatusTest extends TestCase
{
    public function test_values_returns_all_enum_values(): void
    {
        $expectedValues = ['PENDING', 'SUCCESS', 'FAILED'];

        $this->assertEquals($expectedValues, PaymentStatus::values());
    }

    public function test_label_returns_correct_human_readable_labels(): void
    {
        $this->assertEquals('Pending', PaymentStatus::PENDING->label());
        $this->assertEquals('Success', PaymentStatus::SUCCESS->label());
        $this->assertEquals('Failed', PaymentStatus::FAILED->label());
    }

    public function test_is_successful_method(): void
    {
        $this->assertTrue(PaymentStatus::SUCCESS->isSuccessful());
        $this->assertFalse(PaymentStatus::PENDING->isSuccessful());
        $this->assertFalse(PaymentStatus::FAILED->isSuccessful());
    }

    public function test_is_pending_method(): void
    {
        $this->assertTrue(PaymentStatus::PENDING->isPending());
        $this->assertFalse(PaymentStatus::SUCCESS->isPending());
        $this->assertFalse(PaymentStatus::FAILED->isPending());
    }

    public function test_is_failed_method(): void
    {
        $this->assertTrue(PaymentStatus::FAILED->isFailed());
        $this->assertFalse(PaymentStatus::SUCCESS->isFailed());
        $this->assertFalse(PaymentStatus::PENDING->isFailed());
    }



    public function test_enum_has_all_expected_cases(): void
    {
        $cases = PaymentStatus::cases();

        $this->assertCount(3, $cases);
        $this->assertContains(PaymentStatus::PENDING, $cases);
        $this->assertContains(PaymentStatus::SUCCESS, $cases);
        $this->assertContains(PaymentStatus::FAILED, $cases);
    }
}

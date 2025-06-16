<?php

namespace Tests\Unit\Enums;

use App\Enums\Finance\PaymentType;
use PHPUnit\Framework\TestCase;

class PaymentTypeTest extends TestCase
{
    public function test_values_returns_all_enum_values(): void
    {
        $expectedValues = ['DEPOSIT', 'DIRECT_SUBSCRIPTION'];

        $this->assertEquals($expectedValues, PaymentType::values());
    }

    public function test_label_returns_correct_human_readable_labels(): void
    {
        $this->assertEquals('Deposit', PaymentType::DEPOSIT->label());
        $this->assertEquals('Direct Subscription', PaymentType::DIRECT_SUBSCRIPTION->label());
    }

    public function test_enum_has_all_expected_cases(): void
    {
        $cases = PaymentType::cases();

        $this->assertCount(2, $cases);
        $this->assertContains(PaymentType::DEPOSIT, $cases);
        $this->assertContains(PaymentType::DIRECT_SUBSCRIPTION, $cases);
    }
}

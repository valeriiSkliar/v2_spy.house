<?php

namespace Tests\Unit\Enums;

use App\Enums\Finance\PaymentMethod;
use PHPUnit\Framework\TestCase;

class PaymentMethodTest extends TestCase
{
    public function test_values_returns_all_enum_values(): void
    {
        $expectedValues = ['USDT', 'PAY2.HOUSE', 'USER_BALANCE'];

        $this->assertEquals($expectedValues, PaymentMethod::values());
    }

    public function test_label_returns_correct_human_readable_labels(): void
    {
        $this->assertEquals('USDT', PaymentMethod::USDT->label());
        $this->assertEquals('Pay2.House', PaymentMethod::PAY2_HOUSE->label());
        $this->assertEquals('User Balance', PaymentMethod::USER_BALANCE->label());
    }

    public function test_enum_has_all_expected_cases(): void
    {
        $cases = PaymentMethod::cases();

        $this->assertCount(3, $cases);
        $this->assertContains(PaymentMethod::USDT, $cases);
        $this->assertContains(PaymentMethod::PAY2_HOUSE, $cases);
        $this->assertContains(PaymentMethod::USER_BALANCE, $cases);
    }
}

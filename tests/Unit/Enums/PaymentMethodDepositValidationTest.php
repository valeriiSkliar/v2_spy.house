<?php

namespace Tests\Unit\Enums;

use App\Enums\Finance\PaymentMethod;
use PHPUnit\Framework\TestCase;

class PaymentMethodDepositValidationTest extends TestCase
{
    public function test_usdt_is_valid_for_deposits(): void
    {
        $this->assertTrue(PaymentMethod::USDT->isValidForDeposits());
    }

    public function test_pay2_house_is_valid_for_deposits(): void
    {
        $this->assertTrue(PaymentMethod::PAY2_HOUSE->isValidForDeposits());
    }

    public function test_user_balance_is_not_valid_for_deposits(): void
    {
        $this->assertFalse(PaymentMethod::USER_BALANCE->isValidForDeposits());
    }

    public function test_get_valid_for_deposits_returns_correct_methods(): void
    {
        $validMethods = PaymentMethod::getValidForDeposits();

        $this->assertCount(2, $validMethods);
        $this->assertContains(PaymentMethod::USDT, $validMethods);
        $this->assertContains(PaymentMethod::PAY2_HOUSE, $validMethods);
        $this->assertNotContains(PaymentMethod::USER_BALANCE, $validMethods);
    }

    public function test_all_payment_methods_are_tested_for_deposits(): void
    {
        $allMethods = PaymentMethod::cases();
        $validMethods = PaymentMethod::getValidForDeposits();

        // Проверяем что каждый метод либо валиден, либо нет
        foreach ($allMethods as $method) {
            $isValid = $method->isValidForDeposits();
            $inValidList = in_array($method, $validMethods);

            $this->assertEquals(
                $isValid,
                $inValidList,
                "Method {$method->value} validation consistency check failed"
            );
        }
    }

    public function test_deposit_validation_is_deterministic(): void
    {
        // Проверяем что валидация дает одинаковые результаты при повторных вызовах
        for ($i = 0; $i < 10; $i++) {
            $this->assertTrue(PaymentMethod::USDT->isValidForDeposits());
            $this->assertTrue(PaymentMethod::PAY2_HOUSE->isValidForDeposits());
            $this->assertFalse(PaymentMethod::USER_BALANCE->isValidForDeposits());
        }
    }

    public function test_deposit_validation_methods_exist(): void
    {
        // Проверяем что необходимые методы существуют в enum
        $this->assertTrue(method_exists(PaymentMethod::class, 'isValidForDeposits'));
        $this->assertTrue(method_exists(PaymentMethod::class, 'getValidForDeposits'));
    }

    public function test_payment_method_enum_structure_for_deposits(): void
    {
        $allMethods = PaymentMethod::cases();

        // Проверяем что у нас есть ожидаемые методы платежей
        $methodValues = array_map(fn($method) => $method->value, $allMethods);

        $this->assertContains('USDT', $methodValues);
        $this->assertContains('PAY2.HOUSE', $methodValues);
        $this->assertContains('USER_BALANCE', $methodValues);
    }

    public function test_deposit_validation_edge_cases(): void
    {
        // Тест что валидация работает для каждого случая enum
        $testCases = [
            [PaymentMethod::USDT, true],
            [PaymentMethod::PAY2_HOUSE, true],
            [PaymentMethod::USER_BALANCE, false],
        ];

        foreach ($testCases as [$method, $expectedValid]) {
            $this->assertEquals(
                $expectedValid,
                $method->isValidForDeposits(),
                "Validation failed for {$method->value}"
            );
        }
    }
}

<?php

namespace Database\Factories\Finance\Models;

use App\Enums\Finance\PaymentMethod;
use App\Enums\Finance\PaymentStatus;
use App\Enums\Finance\PaymentType;
use App\Finance\Models\Payment;
use App\Finance\Models\Subscription;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Finance\Models\Payment>
 */
class PaymentFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Payment::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'amount' => $this->faker->randomFloat(2, 10, 1000),
            'payment_type' => $this->faker->randomElement(PaymentType::cases()),
            'subscription_id' => null,
            'payment_method' => $this->faker->randomElement(PaymentMethod::cases()),
            'transaction_number' => $this->faker->unique()->numerify('TXN############'),
            'promocode_id' => null,
            'status' => $this->faker->randomElement(PaymentStatus::cases()),
        ];
    }

    /**
     * Indicate that the payment is for deposit.
     */
    public function deposit(): static
    {
        return $this->state(fn(array $attributes) => [
            'payment_type' => PaymentType::DEPOSIT,
            'subscription_id' => null,
        ]);
    }

    /**
     * Indicate that the payment is for direct subscription.
     */
    public function directSubscription(): static
    {
        return $this->state(fn(array $attributes) => [
            'payment_type' => PaymentType::DIRECT_SUBSCRIPTION,
            'subscription_id' => Subscription::factory(),
        ]);
    }

    /**
     * Indicate that the payment is pending.
     */
    public function pending(): static
    {
        return $this->state(fn(array $attributes) => [
            'status' => PaymentStatus::PENDING,
            'webhook_processed_at' => null,
        ]);
    }

    /**
     * Indicate that the payment is successful.
     */
    public function successful(): static
    {
        return $this->state(fn(array $attributes) => [
            'status' => PaymentStatus::SUCCESS,
            'webhook_processed_at' => $this->faker->dateTimeBetween('-1 week', 'now'),
        ]);
    }

    /**
     * Indicate that the payment is failed.
     */
    public function failed(): static
    {
        return $this->state(fn(array $attributes) => [
            'status' => PaymentStatus::FAILED,
            'webhook_processed_at' => $this->faker->dateTimeBetween('-1 week', 'now'),
        ]);
    }

    /**
     * Indicate payment method is USDT.
     */
    public function usdt(): static
    {
        return $this->state(fn(array $attributes) => [
            'payment_method' => PaymentMethod::UDST,
        ]);
    }

    /**
     * Indicate payment method is Pay2.House.
     */
    public function pay2House(): static
    {
        return $this->state(fn(array $attributes) => [
            'payment_method' => PaymentMethod::PAY2_HOUSE,
        ]);
    }

    /**
     * Indicate payment method is User Balance.
     */
    public function userBalance(): static
    {
        return $this->state(fn(array $attributes) => [
            'payment_method' => PaymentMethod::USER_BALANCE,
        ]);
    }
}

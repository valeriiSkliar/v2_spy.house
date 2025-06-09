<?php

namespace Database\Factories\Finance\Models;

use App\Finance\Models\Subscription;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Finance\Models\Subscription>
 */
class SubscriptionFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Subscription::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => $this->faker->randomElement(['Basic', 'Premium', 'Enterprise', 'Starter']),
            'amount' => $this->faker->randomFloat(2, 10, 500),
            'early_discount' => $this->faker->optional(0.3)->randomFloat(2, 5, 50),
            'api_request_count' => $this->faker->randomElement([100, 500, 1000, 5000, 10000]),
            'search_request_count' => $this->faker->randomElement([50, 200, 500, 2000, 5000]),
            'status' => $this->faker->randomElement(['active', 'inactive', 'deprecated']),
        ];
    }

    /**
     * Indicate that the subscription is active.
     */
    public function active(): static
    {
        return $this->state(fn(array $attributes) => [
            'status' => 'active',
        ]);
    }

    /**
     * Indicate that the subscription is inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn(array $attributes) => [
            'status' => 'inactive',
        ]);
    }

    /**
     * Indicate that the subscription has early discount.
     */
    public function withDiscount(float $discount = null): static
    {
        return $this->state(fn(array $attributes) => [
            'early_discount' => $discount ?? $this->faker->randomFloat(2, 10, 30),
        ]);
    }

    /**
     * Indicate that the subscription has no early discount.
     */
    public function withoutDiscount(): static
    {
        return $this->state(fn(array $attributes) => [
            'early_discount' => null,
        ]);
    }
}

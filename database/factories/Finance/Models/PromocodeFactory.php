<?php

namespace Database\Factories\Finance\Models;

use App\Enums\Finance\PromocodeStatus;
use App\Finance\Models\Promocode;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Finance\Models\Promocode>
 */
class PromocodeFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Promocode::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'promocode' => strtoupper($this->faker->unique()->lexify('??????')),
            'discount' => $this->faker->randomFloat(2, 5, 50),
            'status' => PromocodeStatus::ACTIVE,
            'date_start' => $this->faker->optional()->dateTimeBetween('-1 month', 'now'),
            'date_end' => $this->faker->optional()->dateTimeBetween('now', '+1 month'),
            'count_activation' => $this->faker->numberBetween(0, 100),
            'max_per_user' => $this->faker->numberBetween(1, 5),
            'created_by_user_id' => User::factory(),
        ];
    }

    /**
     * Indicate that the promocode is active.
     */
    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => PromocodeStatus::ACTIVE,
        ]);
    }

    /**
     * Indicate that the promocode is inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => PromocodeStatus::INACTIVE,
        ]);
    }

    /**
     * Indicate that the promocode is expired.
     */
    public function expired(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => PromocodeStatus::EXPIRED,
            'date_end' => $this->faker->dateTimeBetween('-1 month', '-1 day'),
        ]);
    }

    /**
     * Indicate that the promocode is exhausted.
     */
    public function exhausted(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => PromocodeStatus::EXHAUSTED,
        ]);
    }

    /**
     * Set specific discount percentage.
     */
    public function withDiscount(float $discount): static
    {
        return $this->state(fn (array $attributes) => [
            'discount' => $discount,
        ]);
    }

    /**
     * Set specific promocode string.
     */
    public function withCode(string $code): static
    {
        return $this->state(fn (array $attributes) => [
            'promocode' => $code,
        ]);
    }

    /**
     * Set date range for validity.
     */
    public function validFrom(string $start, string $end): static
    {
        return $this->state(fn (array $attributes) => [
            'date_start' => $start,
            'date_end' => $end,
        ]);
    }

    /**
     * Set max uses per user.
     */
    public function maxPerUser(int $max): static
    {
        return $this->state(fn (array $attributes) => [
            'max_per_user' => $max,
        ]);
    }
}

<?php

namespace Database\Factories\Finance\Models;

use App\Finance\Models\Payment;
use App\Finance\Models\Promocode;
use App\Finance\Models\PromocodeActivation;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Finance\Models\PromocodeActivation>
 */
class PromocodeActivationFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = PromocodeActivation::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'promocode_id' => Promocode::factory(),
            'user_id' => User::factory(),
            'payment_id' => null, // Make it nullable by default
            'ip_address' => $this->faker->ipv4,
            'user_agent' => $this->faker->userAgent,
        ];
    }

    /**
     * Indicate that the activation has an associated payment.
     */
    public function withPayment(): static
    {
        return $this->state(fn (array $attributes) => [
            'payment_id' => Payment::factory(),
        ]);
    }

    /**
     * Set specific promocode for activation.
     */
    public function forPromocode(Promocode $promocode): static
    {
        return $this->state(fn (array $attributes) => [
            'promocode_id' => $promocode->id,
        ]);
    }

    /**
     * Set specific user for activation.
     */
    public function forUser(User $user): static
    {
        return $this->state(fn (array $attributes) => [
            'user_id' => $user->id,
        ]);
    }

    /**
     * Set specific IP address.
     */
    public function fromIp(string $ipAddress): static
    {
        return $this->state(fn (array $attributes) => [
            'ip_address' => $ipAddress,
        ]);
    }

    /**
     * Set created at timestamp.
     */
    public function createdAt(string $timestamp): static
    {
        return $this->state(fn (array $attributes) => [
            'created_at' => $timestamp,
        ]);
    }
}

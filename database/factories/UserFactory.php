<?php

namespace Database\Factories;

use App\Enums\Frontend\UserExperience;
use App\Enums\Frontend\UserScopeOfActivity;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\User>
 */
class UserFactory extends Factory
{
    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $messengerType = $this->faker->randomElement(['whatsapp', 'viber', 'telegram']);

        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'login' => fake()->unique()->safeEmail(),
            'messenger_type' => $messengerType,
            'messenger_contact' => $messengerType === 'telegram' ? '@'.$this->faker->userName : $this->faker->e164PhoneNumber(),
            'scope_of_activity' => $this->faker->randomElement(UserScopeOfActivity::names()),
            'experience' => $this->faker->randomElement(UserExperience::names()),
            'password' => static::$password ??= Hash::make('password'),
            'remember_token' => Str::random(10),
        ];
    }

    /**
     * Indicate that the model's email address should be unverified.
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }
}

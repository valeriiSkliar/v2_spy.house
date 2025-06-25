<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\AdvertismentNetwork>
 */
class AdvertismentNetworkFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */

    public function definition(): array
    {
        return [
            'network_name' => $this->faker->unique()->company,
            'description' => $this->faker->sentence(10),
            'traffic_type_description' => $this->faker->randomElement(['in_page', 'native', 'push', 'pop']),
            'network_url' => $this->faker->url,
            'network_logo' => $this->faker->imageUrl(),
            'total_clicks' => $this->faker->numberBetween(0, 100000),
            'is_adult' => $this->faker->boolean,
        ];
    }
}

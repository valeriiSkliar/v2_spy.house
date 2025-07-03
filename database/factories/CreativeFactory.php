<?php

namespace Database\Factories;

use App\Enums\Frontend\AdvertisingFormat;
use App\Enums\Frontend\AdvertisingStatus;
use App\Enums\Frontend\OperationSystem;
use App\Models\Browser;
use App\Models\Frontend\IsoEntity;
use App\Models\AdvertismentNetwork;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Creative>
 */
class CreativeFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'format' => $this->faker->randomElement(AdvertisingFormat::cases())->value,
            'status' => $this->faker->randomElement(AdvertisingStatus::cases())->value,
            'country_id' => IsoEntity::where('type', 'country')->inRandomOrder()->first()?->id,
            'language_id' => IsoEntity::where('type', 'language')->inRandomOrder()->first()?->id,
            'browser_id' => Browser::active()->forFilter()->inRandomOrder()->first()?->id,
            'operation_system' => $this->faker->randomElement(OperationSystem::cases())->value,
            'advertisment_network_id' => $this->faker->optional(0.7)->randomElement(
                AdvertismentNetwork::active()->pluck('id')->toArray()
            ),
            'is_adult' => $this->faker->boolean(20),
            'external_id' => $this->faker->unique()->randomNumber(8),
            'title' => $this->faker->sentence(3, true),
            'description' => $this->faker->text(200),
            'combined_hash' => hash('sha256', $this->faker->uuid()),
            'landing_url' => $this->faker->optional(0.8)->url(),
            'last_seen_at' => $this->faker->optional(0.9)->dateTimeBetween('-30 days', 'now'),
        ];
    }
}

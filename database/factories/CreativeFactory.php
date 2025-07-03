<?php

namespace Database\Factories;

use App\Enums\Frontend\AdvertisingFormat;
use App\Enums\Frontend\AdvertisingStatus;
use App\Enums\Frontend\OperationSystem;
use App\Models\Browser;
use App\Models\Frontend\IsoEntity;
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
        ];
    }
}

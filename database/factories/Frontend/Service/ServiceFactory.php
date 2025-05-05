<?php

namespace Database\Factories\Frontend\Service;

use App\Models\Frontend\Service\Service;
use App\Models\Frontend\Service\ServiceCategories;
use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Service\Service>
 */
class ServiceFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Service::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => [
                'en' => $this->faker->company(),
                'ru' => $this->faker->company(),
            ],
            'description' => [
                'en' => $this->faker->paragraph(10),
                'ru' => $this->faker->paragraph(10),
            ],
            'logo' => '/storage/assets/images/services/' . $this->faker->numberBetween(1, 73) . '.jpeg',
            'url' => 'https://example.com/' . Str::slug($this->faker->unique()->words(3, true)),
            'redirect_url' => '/services/redirect/' . $this->faker->unique()->numberBetween(1, 1000),
            'status' => 'Active',
            'category_id' => function () {
                return ServiceCategories::factory()->create()->id;
            },
            'views' => $this->faker->numberBetween(0, 1000),
            'transitions' => $this->faker->numberBetween(0, 500),
            'reviews_count' => $this->faker->numberBetween(0, 100),
            'rating' => $this->faker->randomFloat(1, 0, 5),
            'code' => strtoupper($this->faker->unique()->word()),
            'code_description' => [
                'en' => $this->faker->sentence(),
                'ru' => $this->faker->sentence(),
            ],
            'is_active_code' => $this->faker->boolean(),
            'code_valid_from' => now(),
            'code_valid_until' => now()->addYear(),
        ];
    }
}

<?php

namespace Database\Factories\Frontend\Service;

use App\Models\Frontend\Service\ServiceCategories;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ServiceCategories>
 */
class ServiceCategoriesFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = ServiceCategories::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => [
                'en' => $this->faker->words(2, true),
                'ru' => $this->faker->words(2, true),
            ],
            'slug' => $this->faker->slug(),
            'description' => [
                'en' => $this->faker->paragraph(),
                'ru' => $this->faker->paragraph(),
            ],
            'group_name' => $this->faker->word(),
            'image' => null,
            'order' => $this->faker->numberBetween(1, 10),
            'status' => 'Active',
        ];
    }
}

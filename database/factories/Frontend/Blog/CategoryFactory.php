<?php

namespace Database\Factories\Frontend\Blog;

use App\Models\Frontend\Blog\PostCategory;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Frontend\Blog\PostCategory>
 */
class CategoryFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = PostCategory::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $nameEn = $this->faker->unique()->words(2, true);
        $nameRu = $this->faker->unique()->words(2, true);

        return [
            'name' => [
                'en' => ucfirst($nameEn),
                'ru' => ucfirst($nameRu),
            ],
            'parent_id' => null,
            'posts_count' => 0,
        ];
    }
}

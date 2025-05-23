<?php

namespace Database\Factories\Frontend\Blog;

use App\Models\Frontend\Blog\Author;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Frontend\Blog\Author>
 */
class AuthorFactory extends Factory
{
    protected $model = Author::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => [
                'en' => $this->faker->name,
                'ru' => $this->faker->name,
            ],
            'avatar' => 'https://picsum.photos/200/200',
            'bio' => [
                'en' => $this->faker->paragraphs(2, true),
                'ru' => $this->faker->paragraphs(2, true),
            ],
        ];
    }
}

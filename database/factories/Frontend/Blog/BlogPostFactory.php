<?php

namespace Database\Factories\Frontend\Blog;

use App\Models\Frontend\Blog\Author;
use App\Models\Frontend\Blog\BlogPost;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Frontend\Blog\BlogPost>
 */
class BlogPostFactory extends Factory
{
    protected $model = BlogPost::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $titleEn = $this->faker->sentence;
        $titleRu = $this->faker->sentence;

        return [
            'title' => [
                'en' => $titleEn,
                'ru' => $titleRu,
            ],
            'summary' => [
                'en' => $this->faker->paragraph(2),
                'ru' => $this->faker->paragraph(2),
            ],
            'content' => [
                'en' => $this->faker->paragraphs(5, true),
                'ru' => $this->faker->paragraphs(5, true),
            ],
            'slug' => Str::slug($titleEn),
            'views_count' => $this->faker->numberBetween(0, 10000),
            'author_id' => Author::factory(),
            'featured_image' => 'https://picsum.photos/300/200',
            'is_published' => $this->faker->boolean(80),
        ];
    }
}

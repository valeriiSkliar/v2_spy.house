<?php

namespace Database\Factories\Frontend\Blog;

use App\Enums\Frontend\PostStatus;
use App\Models\Frontend\Blog\PostCategory;
use Illuminate\Database\Eloquent\Factories\Factory;
use App\Enums\Frontend\PostType;
use Carbon\Carbon;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Frontend\Blog\Post>
 */
class PostFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $postType = $this->faker->randomElement(PostType::getAllValues());
        return [
            'name' => $this->faker->sentence(10),
            'slug' => $this->faker->slug,
            'intro' => $this->faker->paragraph(3),
            'content' => $this->faker->text(500),
            'type' => $postType,
            'is_featured' => $this->faker->boolean,
            'image' => $this->faker->imageUrl(640, 480),
            'hits' => $this->faker->numberBetween(0, 1000),
            'status' => PostStatus::Published,
            'published_at' => Carbon::now(),
            'category_id' => $this->faker->randomElement(PostCategory::where('group_name', $postType)->pluck('id')->toArray()),
        ];
    }
}

<?php

namespace Database\Factories\Frontend\Blog;

use App\Enums\Frontend\CommentStatus;
use App\Models\Frontend\Blog\BlogComment;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Frontend\Blog\BlogComment>
 */
class BlogCommentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'author_name' => $this->faker->name,
            'email' => $this->faker->safeEmail,
            // 'avatar' => $this->faker->imageUrl(100, 100, 'people'),
            'content' => $this->faker->paragraph,
            'status' => CommentStatus::PENDING->value,
            'parent_id' => null,
        ];
    }

    /**
     * Configure the comment as a reply to another comment
     */
    public function asReply(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'parent_id' => BlogComment::factory(),
            ];
        });
    }

    /**
     * Configure the comment as approved
     */
    public function approved(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'status' => CommentStatus::APPROVED->value,
            ];
        });
    }
}

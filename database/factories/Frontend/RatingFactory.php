<?php

namespace Database\Factories\Frontend;

use App\Models\Frontend\Rating;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class RatingFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Rating::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        // Ensure users exist, create one if not
        if (User::count() === 0) {
            User::factory()->create();
        }

        return [
            'user_id' => User::inRandomOrder()->first()->id,
            'blog_id' => null, // Will be set explicitly in the seeder
            'service_id' => null,
            'rating' => $this->faker->numberBetween(1, 5),
            'review' => $this->faker->optional()->sentence(10), // Optional review
            'created_at' => now()->subDays(rand(1, 365))->subHours(rand(0, 23))->subMinutes(rand(0, 59)),
            'updated_at' => now(),
        ];
    }

    /**
     * Indicate that the rating is for a specific blog post.
     *
     * @return \Illuminate\Database\Eloquent\Factories\Factory
     */
    public function forBlog(int $blogId)
    {
        return $this->state(function (array $attributes) use ($blogId) {
            return [
                'blog_id' => $blogId,
                'service_id' => null, // Ensure service_id is null for blog ratings
            ];
        });
    }
}

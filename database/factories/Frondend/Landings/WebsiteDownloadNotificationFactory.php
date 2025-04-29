<?php

namespace Database\Factories\Frondend\Landings;

use App\Models\Frondend\Landings\WebsiteDownloadNotification;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Frondend\Landings\WebsiteDownloadNotification>
 */
class WebsiteDownloadNotificationFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = WebsiteDownloadNotification::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $status = $this->faker->randomElement(['completed', 'failed']);

        return [
            'url' => $this->faker->url,
            'status' => $status,
            'error' => $status === 'failed' ? $this->faker->sentence : null,
            'user_id' => User::factory(), // Ensure User factory exists or use an existing user ID
            'read_at' => $this->faker->optional()->dateTimeThisMonth,
        ];
    }
}

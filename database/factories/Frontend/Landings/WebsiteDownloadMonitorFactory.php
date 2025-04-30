<?php

namespace Database\Factories\Frontend\Landings;

use App\Models\Frontend\Landings\WebsiteDownloadMonitor;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Frontend\Landings\WebsiteDownloadMonitor>
 */
class WebsiteDownloadMonitorFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = WebsiteDownloadMonitor::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $status = $this->faker->randomElement(['pending', 'in_progress', 'completed', 'failed']);
        $outputPathUuid = Str::uuid()->toString();

        return [
            'url' => $this->faker->url,
            'output_path' => "private/website-downloads/{$outputPathUuid}",
            'status' => $status,
            'progress' => $status === 'completed' ? 100 : ($status === 'in_progress' ? $this->faker->numberBetween(1, 99) : 0),
            'error' => $status === 'failed' ? $this->faker->sentence : null,
            'user_id' => User::factory(), // Ensure User factory exists or use an existing user ID
            'started_at' => $status !== 'pending' ? $this->faker->dateTimeThisMonth : null,
            'completed_at' => in_array($status, ['completed', 'failed']) ? $this->faker->dateTimeThisMonth : null,
        ];
    }
}

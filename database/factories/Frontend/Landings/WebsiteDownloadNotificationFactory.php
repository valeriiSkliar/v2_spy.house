<?php

namespace Database\Factories\Frontend\Landings;

use App\Enums\Frontend\WebSiteDownloadMonitorStatus;
use App\Models\Frontend\Landings\WebsiteDownloadNotification;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Frontend\Landings\WebsiteDownloadNotification>
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
        $status = $this->faker->randomElement(WebSiteDownloadMonitorStatus::values());

        return [
            'url' => $this->faker->url,
            'status' => $status,
            'error' => $status === WebSiteDownloadMonitorStatus::FAILED ? $this->faker->sentence : null,
            'user_id' => User::factory(),
            'read_at' => $this->faker->optional()->dateTimeThisMonth,
        ];
    }
}

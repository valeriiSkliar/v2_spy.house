<?php

namespace Database\Factories\Frontend\Landings;

use App\Enums\Frontend\WebSiteDownloadMonitorStatus;
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
        $status = $this->faker->randomElement(WebSiteDownloadMonitorStatus::values());
        // $status = WebSiteDownloadMonitorStatus::COMPLETED;
        $outputPathUuid = Str::uuid()->toString();

        return [
            'url' => $this->faker->url,
            'output_path' => "private/website-downloads/{$outputPathUuid}",
            'status' => $status,
            'progress' => $status === WebSiteDownloadMonitorStatus::COMPLETED ? 100 : ($status === WebSiteDownloadMonitorStatus::PENDING ? $this->faker->numberBetween(1, 99) : 0),
            'error' => $status === WebSiteDownloadMonitorStatus::FAILED ? $this->faker->sentence : null,
            'user_id' => User::factory(),
            'started_at' => $status !== WebSiteDownloadMonitorStatus::PENDING ? null : $this->faker->dateTimeThisMonth,
            'completed_at' => in_array($status, [WebSiteDownloadMonitorStatus::COMPLETED, WebSiteDownloadMonitorStatus::FAILED]) ? $this->faker->dateTimeThisMonth : null,
        ];
    }
}

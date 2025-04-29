<?php

namespace Database\Seeders;

use App\Models\Frondend\Landings\WebsiteDownloadMonitor;
use App\Models\Frondend\Landings\WebsiteDownloadNotification;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class WebsiteDownloadSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Ensure at least one user exists or create one
        $user = User::first() ?? User::factory()->create();

        // Create 10 monitor records
        WebsiteDownloadMonitor::factory()
            ->count(10)
            ->for($user)
            ->create();

        // Create 5 notification records
        WebsiteDownloadNotification::factory()
            ->count(5)
            ->for($user)
            ->create();
    }
}

<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();

        $this->call([
            UserSeeder::class,
            BlogPostSeeder::class,
            BlogCommentSeeder::class,
            WebsiteDownloadSeeder::class,
            ServiceSeeder::class,
            NotificationTypesSeeder::class,
            NotificationsSeeder::class,
            SubscriptionSeeder::class,
            PaymentSeeder::class,
            PromocodeSeeder::class,
            PromocodeActivationSeeder::class,
            CountryAndLanguageSeeder::class,
            AdvertismentNetworkSeeder::class,
            BrowserSeeder::class,

            // Add other seeders here if needed
        ]);
    }
}

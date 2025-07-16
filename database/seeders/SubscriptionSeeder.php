<?php

namespace Database\Seeders;

use App\Finance\Models\Subscription;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SubscriptionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Check if subscriptions already exist to prevent duplication
        if (Subscription::count() > 0) {
            $this->command->info('Subscriptions already exist. Skipping...');

            return;
        }

        // Disable foreign key checks temporarily
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');

        // Clear existing subscriptions completely
        DB::table('subscriptions')->delete();

        // Reset auto increment to start from 1
        DB::statement('ALTER TABLE subscriptions AUTO_INCREMENT = 1');

        // Re-enable foreign key checks
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        $subscriptions = [
            [
                'name' => 'Free',
                'amount' => 0.00,
                'amount_yearly' => 0.00,
                'early_discount' => null,
                'api_request_count' => 100,
                'search_request_count' => 50,
                'status' => 'active',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Starter',
                'amount' => 29,
                'amount_yearly' => 299,
                'early_discount' => 35,
                'api_request_count' => 1000,
                'search_request_count' => 500,
                'status' => 'active',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Basic',
                'amount' => 59,
                'amount_yearly' => 599,
                'early_discount' => 35,
                'api_request_count' => 5000,
                'search_request_count' => 2500,
                'status' => 'active',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Premium',
                'amount' => 99,
                'amount_yearly' => 999,
                'early_discount' => 35,
                'api_request_count' => 15000,
                'search_request_count' => 7500,
                'status' => 'active',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Enterprise',
                'amount' => 199,
                'amount_yearly' => 1999,
                'early_discount' => 35,
                'api_request_count' => 50000,
                'search_request_count' => 25000,
                'status' => 'active',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];

        // Bulk insert for better performance
        Subscription::insert($subscriptions);

        $this->command->info('Created ' . count($subscriptions) . ' subscription plans');
    }
}

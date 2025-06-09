<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Support\Str;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        User::factory()->create([
            'name' => 'test',
            'email' => 'test@test.com',
            'password' => Hash::make('password'),
            'email_verified_at' => now(),
            'login' => 'test',
            'messenger_type' => 'telegram',
            'messenger_contact' => '@test',
            'scope_of_activity' => 'GAMBLING',
            'experience' => 'BEGINNER',
            'remember_token' => Str::random(10),
            // Financial system fields with defaults
            'available_balance' => 0.00,
            'subscription_id' => null,
            'subscription_time_start' => null,
            'subscription_time_end' => null,
            'subscription_is_expired' => false,
            'queued_subscription_id' => null,
            'balance_version' => 1,

        ]);
    }
}

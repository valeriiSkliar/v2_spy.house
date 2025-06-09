<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\User;

class TestingSeeder extends Seeder
{
    /**
     * Seed the testing database with minimal data.
     */
    public function run(): void
    {
        // Create a test user
        User::create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'email_verified_at' => now(),
            'login' => 'test@example.com',
            'messenger_type' => 'telegram',
            'messenger_contact' => '@testuser',
            'scope_of_activity' => 'FINANCE',
            'experience' => 'PROFESSIONAL',
            'password' => Hash::make('password'),
            'remember_token' => null,
        ]);

        // Create an admin user for testing
        User::create([
            'name' => 'Test Admin',
            'email' => 'admin@example.com',
            'email_verified_at' => now(),
            'login' => 'admin@example.com',
            'messenger_type' => 'telegram',
            'messenger_contact' => '@admin',
            'scope_of_activity' => 'FINANCE',
            'experience' => 'PROFESSIONAL',
            'password' => Hash::make('password'),
            'remember_token' => null,
        ]);

        // Seed only essential notification types for testing
        $this->call([
            NotificationTypesSeeder::class,
        ]);
    }
}

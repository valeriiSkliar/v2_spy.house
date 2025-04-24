<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Auth;
use App\Models\MockUser;

class MockAuthServiceProvider extends ServiceProvider
{
    public function register()
    {
        //
    }

    public function boot()
    {
        if (app()->environment('local')) {
            $mockUser = new MockUser([
                'id' => 1,
                'name' => 'John Doe',
                'email' => 'john@example.com',
                'phone' => '+1234567890',
                'company' => 'Example Corp',
                'position' => 'Senior Developer',
                'country' => 'United States',
                'city' => 'New York',
                'timezone' => 'America/New_York',
                'language' => 'en',
                'photo' => 'https://ui-avatars.com/api/?name=John+Doe&background=random',
                'notification_email' => true,
                'notification_sms' => false,
                'notification_telegram' => true,
                'scope_of_activity' => 'Arbitrage (solo)',
                'created_at' => now()->subDays(30),
                'last_login_at' => now()->subHours(2),
                'two_factor_enabled' => false,
                'pin_enabled' => true,
                'ip_restriction_enabled' => false,
                'personal_greeting' => 'Welcome back, John!',
                'avatar' => 'https://ui-avatars.com/api/?name=John+Doe&background=random',
            ]);

            Auth::setUser($mockUser);
        }
    }
}

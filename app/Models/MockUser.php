<?php

namespace App\Models;

use Illuminate\Auth\GenericUser;

class MockUser extends GenericUser
{
    public function hasTariff(): bool
    {
        return true;
    }

    public function currentTariff(): array
    {
        return [
            'id' => 3,
            'slug' => 'premium',
            'name' => 'Premium',
            'css_class' => 'premium',
            'monthly_price' => 200,
            'yearly_price' => 300,
            'active_flows' => 'Unlimited',
            'api_requests' => 'Unlimited',
            'expires_at' => '12.06.2028',
            'features' => [
                'Unlimited clicks',
                'Protection from bots',
                'Priority support',
            ],
        ];
    }
}

<?php

namespace App\Http\Controllers\Test;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class TariffController extends Controller
{
    public function index()
    {
        $tariffs = [
            [
                'id' => 1,
                'name' => 'Free',
                'css_class' => 'free',
                'price' => 0,
                'period' => 'month',
                'features' => [
                    'Basic access',
                    'Limited support',
                    'Basic analytics'
                ]
            ],
            [
                'id' => 2,
                'name' => 'Premium',
                'css_class' => 'premium',
                'price' => 99.99,
                'period' => 'month',
                'features' => [
                    'Unlimited access',
                    'Priority support',
                    'Advanced analytics'
                ]
            ]
        ];

        return view('tariffs.index', [
            'tariffs' => $tariffs
        ]);
    }
}

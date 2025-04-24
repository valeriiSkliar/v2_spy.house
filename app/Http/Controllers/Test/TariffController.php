<?php

namespace App\Http\Controllers\Test;

use Illuminate\Http\Request;
use App\Models\Tariff;
use App\Models\Payment;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\Controller;

class TariffController extends Controller
{
    /**
     * Display all available tariffs
     */
    public function index()
    {
        // Get all available tariffs
        $tariffs = $this->getTariffs();

        // Get user's current tariff
        $currentTariff = Auth::user()->currentTariff();

        if (!isset($userTariff['expires_at'])) {
            $currentTariff['expires_at'] = '12.06.2024'; // Значение по умолчанию
        }


        // Get user's payment history
        $payments = $this->getPaymentHistory();

        return view('tariffs.index', [
            'tariffs' => $tariffs,
            'currentTariff' => $currentTariff,
            'payments' => $payments
        ]);
    }

    /**
     * Show the payment page for a specific tariff
     */
    public function payment($slug)
    {
        $tariff = collect($this->getTariffs())->firstWhere('slug', $slug);

        if (!$tariff) {
            abort(404);
        }

        // Get payment methods
        $paymentMethods = $this->getPaymentMethods();

        return view('tariffs.payment', [
            'tariff' => $tariff,
            'paymentMethods' => $paymentMethods
        ]);
    }

    /**
     * Process the payment
     */
    public function processPayment(Request $request)
    {
        $request->validate([
            'tariff_id' => 'required|string',
            'payment_method' => 'required|string',
            'promo_code' => 'nullable|string'
        ]);

        // In a real app, you would process the payment here
        // For this demo, we'll just simulate a successful payment

        // Redirect to success page or show modal
        return redirect()->route('tariffs.index')->with('success', 'Subscription activated successfully');
    }

    /**
     * Mock data for tariffs
     */
    private function getTariffs()
    {
        return [
            [
                'id' => 1,
                'slug' => 'start',
                'name' => 'Start',
                'css_class' => 'start',
                'monthly_price' => 30,
                'yearly_price' => 300,
                'active_flows' => 3,
                'api_requests' => 6,
                'features' => [
                    'Unlimited clicks',
                    'Protection from bots and all advertising sources',
                    'Protection from spy services',
                    'Protection from VPN/Proxy',
                    'Real-time statistics',
                    'PHP Integration',
                    'Premium GEO Databases',
                    'IPv4 Support',
                    'IPv6 Support',
                    'ISP Support',
                    'Referrer Support',
                    'Device filtering',
                    'Operating system filtering',
                    'Browser filtering',
                    'Blacklist filtering',
                    'Support for all traffic sources',
                    'Support service'
                ]
            ],
            [
                'id' => 2,
                'slug' => 'basic',
                'name' => 'Basic',
                'css_class' => 'basic',
                'monthly_price' => 100,
                'yearly_price' => 300,
                'active_flows' => 10,
                'api_requests' => 20,
                'features' => [
                    'Unlimited clicks',
                    'Protection from bots and all advertising sources',
                    'Protection from spy services',
                    'Protection from VPN/Proxy',
                    'Real-time statistics',
                    'PHP Integration',
                    'Premium GEO Databases',
                    'IPv4 Support',
                    'IPv6 Support',
                    'ISP Support',
                    'Referrer Support',
                    'Device filtering',
                    'Operating system filtering',
                    'Browser filtering',
                    'Blacklist filtering',
                    'Support for all traffic sources',
                    'Support service'
                ]
            ],
            [
                'id' => 3,
                'slug' => 'premium',
                'name' => 'Premium',
                'css_class' => 'premium',
                'monthly_price' => 200,
                'yearly_price' => 300,
                'active_flows' => 'Unlimited',
                'api_requests' => 'Unlimited',
                'features' => [
                    'Unlimited clicks',
                    'Protection from bots and all advertising sources',
                    'Protection from spy services',
                    'Protection from VPN/Proxy',
                    'Real-time statistics',
                    'PHP Integration',
                    'Premium GEO Databases',
                    'IPv4 Support',
                    'IPv6 Support',
                    'ISP Support',
                    'Referrer Support',
                    'Device filtering',
                    'Operating system filtering',
                    'Browser filtering',
                    'Blacklist filtering',
                    'Support for all traffic sources',
                    'Priority support service'
                ]
            ],
            [
                'id' => 4,
                'slug' => 'enterprise',
                'name' => 'Enterprise',
                'css_class' => 'enterprise',
                'monthly_price' => 499,
                'yearly_price' => 300,
                'active_flows' => 'Unlimited',
                'api_requests' => 'Unlimited',
                'features' => [
                    'Unlimited clicks',
                    'Protection from bots and all advertising sources',
                    'Protection from spy services',
                    'Protection from VPN/Proxy',
                    'Real-time statistics',
                    'PHP Integration',
                    'Premium GEO Databases',
                    'IPv4 Support',
                    'IPv6 Support',
                    'ISP Support',
                    'Referrer Support',
                    'Device filtering',
                    'Operating system filtering',
                    'Browser filtering',
                    'Blacklist filtering',
                    'Support for all traffic sources',
                    'Priority support service'
                ]
            ]
        ];
    }

    /**
     * Mock data for payment history
     */
    private function getPaymentHistory()
    {
        return [
            [
                'id' => 1,
                'date' => '12.02.22',
                'tariff' => 'Start',
                'tariff_class' => 'start',
                'type' => '1 месяц',
                'payment_method' => 'WebMoney (WMZ)',
                'amount' => 60,
                'status' => 'Активный',
                'status_class' => 'successful'
            ],
            [
                'id' => 2,
                'date' => '12.02.22',
                'tariff' => 'Basic',
                'tariff_class' => 'basic',
                'type' => '1 месяц',
                'payment_method' => 'WebMoney (WMZ)',
                'amount' => 60,
                'status' => 'Завершен',
                'status_class' => 'rejected'
            ],
            [
                'id' => 3,
                'date' => '12.02.22',
                'tariff' => 'Premium',
                'tariff_class' => 'premium',
                'type' => '1 месяц',
                'payment_method' => 'WebMoney (WMZ)',
                'amount' => 60,
                'status' => 'Завершен',
                'status_class' => 'rejected'
            ],
            [
                'id' => 4,
                'date' => '12.02.22',
                'tariff' => 'Enterprise',
                'tariff_class' => 'enterprise',
                'type' => '1 месяц',
                'payment_method' => 'WebMoney (WMZ)',
                'amount' => 60,
                'status' => 'Завершен',
                'status_class' => 'rejected'
            ]
        ];
    }

    /**
     * Get available payment methods
     */
    private function getPaymentMethods()
    {
        return [
            ['name' => 'Tether', 'img' => 'img/pay/tether.svg'],
            ['name' => 'Capitalist', 'img' => 'img/pay/capitalist.svg'],
            ['name' => 'Bitcoin', 'img' => 'img/pay/bitcoin.svg'],
            ['name' => 'Ethereum', 'img' => 'img/pay/ethereum.svg'],
            ['name' => 'Litecoin', 'img' => 'img/pay/litecoin.png'],
            ['name' => 'Pay2', 'img' => 'img/pay/pay2.svg'],
        ];
    }
}

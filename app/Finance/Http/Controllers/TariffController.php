<?php

namespace App\Finance\Http\Controllers;

use App\Enums\Finance\PaymentMethod;
use App\Finance\Models\Subscription;
use App\Http\Controllers\Controller;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;

class TariffController extends Controller
{
    use AuthorizesRequests;

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $user = $request->user()->load('subscription');
        $tariffs = Subscription::where('status', 'active')
            ->where('amount', '>', 0)
            ->where('name', '!=', 'free')
            ->get();

        $payments = $user->subscriptionPayments()->with('subscription')->paginate(10);
        // dd($payments);
        return view('pages.tariffs.index', [
            'currentTariff' => $user->currentTariff(),
            'tariffs' => $tariffs,
            'payments' => $payments,
            'activeSubscriptions' => $user->activeSubscriptions(),
        ]);
    }

    /**
     * Process the payment for a specific tariff
     */
    public function processPayment(Request $request)
    {
        $requestData = $request->all();
        $requestData['billing_type'] = $request->get('billing_type', 'month');

        dd($requestData);
    }

    /**
     * Show the payment page for a specific tariff
     */
    public function payment($slug, Request $request)
    {
        $tariff = Subscription::where('id', $slug)->first();

        if (! $tariff) {
            abort(404);
        }

        $billingType = $request->get('billing_type', 'month'); // По умолчанию месячная подписка

        // Проверяем корректность типа подписки
        if (!in_array($billingType, ['month', 'year'])) {
            $billingType = 'month';
        }

        $paymentMethods = PaymentMethod::cases();

        return view('pages.tariffs.payment', [
            'tariff' => $tariff,
            'billingType' => $billingType,
            'paymentMethods' => $paymentMethods,
        ]);
    }

    /**
     * AJAX endpoint for payments pagination.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function ajaxPayments(Request $request)
    {
        $view = $this->index($request);

        if ($request->ajax()) {
            $data = $view->getData();
            $payments = $data['payments'];

            // Render payments table HTML
            $paymentsHtml = view('components.tariffs.payments-list', ['payments' => $payments])->render();

            // Render pagination HTML  
            $paginationHtml = '';
            $hasPagination = $payments->hasPages();
            if ($hasPagination) {
                $paginationHtml = view('components.tariffs.payments-pagination', [
                    'currentPage' => $payments->currentPage(),
                    'totalPages' => $payments->lastPage(),
                    'pagination' => $payments
                ])->render();
            }

            return response()->json([
                'html' => $paymentsHtml,
                'pagination' => $paginationHtml,
                'hasPagination' => $hasPagination,
                'currentPage' => $payments->currentPage(),
                'totalPages' => $payments->lastPage(),
                'count' => $payments->count(),
            ]);
        }

        return $view;
    }
}

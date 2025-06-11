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
        // Получаем данные из запроса
        $tariffId = $request->get('tariff_id');
        $billingType = $request->get('billing_type', 'month');
        $isRenewal = (bool) $request->get('is_renewal', false);
        $paymentMethod = $request->get('payment_method');
        $promoCode = $request->get('promo_code');

        // Валидация основных полей
        if (!$tariffId) {
            return response()->json(['error' => 'Tariff ID is required'], 400);
        }

        if (!$paymentMethod) {
            return response()->json(['error' => 'Payment method is required'], 400);
        }

        // Проверяем существование тарифа
        $tariff = Subscription::find($tariffId);
        if (!$tariff) {
            return response()->json(['error' => 'Tariff not found'], 404);
        }

        // Подготавливаем данные для обработки
        $paymentData = [
            'tariff_id' => $tariffId,
            'billing_type' => $billingType,
            'is_renewal' => $isRenewal,
            'payment_method' => $paymentMethod,
            'promo_code' => $promoCode,
            'user_id' => $request->user()->id,
        ];

        // TODO: Добавить логику обработки платежа

        return response()->json(['success' => true, 'data' => $paymentData]);
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

        // Определяем является ли это продлением текущей подписки
        $user = $request->user();
        $currentTariff = $user->currentTariff();
        $isRenewal = $currentTariff && $currentTariff['id'] === $tariff->id;

        $paymentMethods = PaymentMethod::cases();

        return view('pages.tariffs.payment', [
            'tariff' => $tariff,
            'billingType' => $billingType,
            'isRenewal' => $isRenewal,
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

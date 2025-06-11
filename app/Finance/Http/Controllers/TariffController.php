<?php

namespace App\Finance\Http\Controllers;

use App\Enums\Finance\PaymentMethod;
use App\Enums\Finance\PaymentType;
use App\Enums\Finance\PaymentStatus;
use App\Finance\Models\Subscription;
use App\Finance\Models\Payment;
use App\Finance\Services\Pay2Service;
use App\Http\Controllers\Controller;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class TariffController extends Controller
{
    use AuthorizesRequests;

    protected $pay2Service;

    public function __construct(Pay2Service $pay2Service)
    {
        $this->pay2Service = $pay2Service;
    }

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

        $user = $request->user();

        // Вычисляем сумму платежа
        $amount = $tariff->amount;
        if ($billingType === 'year') {
            $amount = $tariff->amount * 12 * 0.8; // 20% скидка за годовую подписку
        }

        // Подготавливаем данные для Pay2.House
        $paymentData = [
            'external_number' => $this->pay2Service->generateExternalNumber($user->id, $tariff->id),
            'amount' => $amount,
            'currency_code' => 'USD',
            'description' => "Оплата тарифа {$tariff->name} ({$billingType})",
            'payer_email' => $user->email,
            'payment_method' => $paymentMethod,
            'handling_fee' => 0,
        ];

        // Создаем платеж через Pay2.House
        $paymentResult = $this->pay2Service->createPayment($paymentData);

        if (!$paymentResult['success']) {
            Log::error('TariffController: Ошибка создания платежа', [
                'user_id' => $user->id,
                'tariff_id' => $tariffId,
                'error' => $paymentResult['error']
            ]);

            return response()->json([
                'error' => 'Failed to create payment',
                'details' => $paymentResult['error']
            ], 500);
        }

        // Сохраняем информацию о платеже в локальной базе данных
        $payment = Payment::create([
            'user_id' => $user->id,
            'amount' => $amount,
            'payment_type' => PaymentType::DIRECT_SUBSCRIPTION,
            'subscription_id' => $tariff->id,
            'payment_method' => PaymentMethod::from($paymentMethod),
            'status' => PaymentStatus::PENDING,
            'invoice_number' => $paymentResult['data']['invoice_number'],
            'external_number' => $paymentData['external_number'],
        ]);

        Log::info('TariffController: Платеж создан успешно', [
            'user_id' => $user->id,
            'tariff_id' => $tariffId,
            'external_number' => $paymentData['external_number'],
            'invoice_number' => $paymentResult['data']['invoice_number'],
            'amount' => $amount,
            'payment_result' => $paymentResult
        ]);

        return response()->json([
            'success' => true,
            'payment_url' => $paymentResult['data']['approval_url'],
            'invoice_number' => $paymentResult['data']['invoice_number'],
            'external_number' => $paymentData['external_number']
        ]);
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
     * Handle successful payment return from Pay2.House
     */
    public function paymentSuccess(Request $request)
    {
        $invoiceNumber = $request->get('invoice_number');

        if ($invoiceNumber) {
            // Получаем детали платежа
            $paymentDetails = $this->pay2Service->getPaymentDetails($invoiceNumber);

            if ($paymentDetails['success']) {
                Log::info('TariffController: Успешное возвращение с платежа', [
                    'invoice_number' => $invoiceNumber,
                    'payment_data' => $paymentDetails['data']
                ]);
            }
        }

        return view('pages.tariffs.payment-success', [
            'invoice_number' => $invoiceNumber
        ]);
    }

    /**
     * Handle cancelled payment return from Pay2.House
     */
    public function paymentCancel(Request $request)
    {
        $invoiceNumber = $request->get('invoice_number');

        Log::info('TariffController: Отмена платежа', [
            'invoice_number' => $invoiceNumber
        ]);

        return view('pages.tariffs.payment-cancel', [
            'invoice_number' => $invoiceNumber
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

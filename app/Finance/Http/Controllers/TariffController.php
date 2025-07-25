<?php

namespace App\Finance\Http\Controllers;

use App\Enums\Finance\PaymentMethod;
use App\Enums\Finance\PaymentStatus;
use App\Enums\Finance\PaymentType;
use App\Finance\Models\Payment;
use App\Finance\Models\Subscription;
use App\Finance\Services\BalanceService;
use App\Finance\Services\Pay2Service;
use App\Http\Controllers\Controller;
use App\Finance\Http\Requests\ProcessPaymentRequest;
use App\Models\User;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class TariffController extends Controller
{
    use AuthorizesRequests;

    protected $pay2Service;

    protected $balanceService;

    public function __construct(Pay2Service $pay2Service, BalanceService $balanceService)
    {
        $this->pay2Service = $pay2Service;
        $this->balanceService = $balanceService;
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

        return view('pages.tariffs.index', [
            'currentTariff' => $user->currentTariff(),
            'tariffs' => $tariffs,
            'payments' => $payments,
            'activeSubscriptions' => $user->activeSubscriptions(),
            'userBalance' => $user->available_balance,
        ]);
    }

    /**
     * Validate payment form fields (AJAX endpoint)
     */
    public function validatePayment(ProcessPaymentRequest $request)
    {
        return response()->json([
            'success' => true,
            'message' => __('validation.validation_passed')
        ]);
    }

    /**
     * Process the payment for a specific tariff
     */
    public function processPayment(ProcessPaymentRequest $request)
    {
        $user = $request->user();

        // Если валидация уже была пройдена асинхронно, пропускаем повторную проверку
        if ($request->has('_validation_passed')) {
            Log::info('TariffController: Валидация была пройдена асинхронно', [
                'user_id' => $user->id,
            ]);
        }

        // Получаем данные из HTTP Referer для извлечения slug и billingType
        $referer = $request->headers->get('referer');
        $paymentMethod = $request->get('payment_method');
        $promoCode = $request->get('promo_code');
        $isRenewal = (bool) $request->get('is_renewal', false);

        // Парсим URL для получения slug и billingType
        if (!$referer || !preg_match('/\/tariffs\/payment\/([^\/]+)\/([^\/\?]+)/', $referer, $matches)) {
            Log::warning('TariffController: Неверный referer URL', [
                'user_id' => $user->id,
                'referer' => $referer
            ]);
            return response()->json(['error' => __('validation.tariffs.invalid_payment_page_url')], 400);
        }

        $slug = $matches[1];
        $billingType = $matches[2];

        Log::info('TariffController: Начало обработки платежа тарифа', [
            'user_id' => $user->id,
            'slug' => $slug,
            'billing_type' => $billingType,
            'is_renewal' => $isRenewal,
            'payment_method' => $paymentMethod,
            'promo_code' => $promoCode,
            'current_subscription_id' => $user->subscription_id ?? null,
            'current_subscription_end' => $user->subscription_time_end ?? null,
        ]);

        // Валидация основных полей
        if (!$paymentMethod) {
            Log::warning('TariffController: Payment method отсутствует', [
                'user_id' => $user->id,
                'slug' => $slug
            ]);
            return response()->json(['error' => __('validation.tariffs.payment_method_required')], 400);
        }

        // Проверяем корректность типа подписки
        if (!in_array($billingType, ['month', 'year'])) {
            Log::warning('TariffController: Неверный billing type', [
                'user_id' => $user->id,
                'billing_type' => $billingType
            ]);
            return response()->json(['error' => __('validation.tariffs.invalid_billing_type')], 400);
        }

        // Ищем тариф по slug
        $tariff = Subscription::findBySlug($slug);
        if (!$tariff) {
            Log::error('TariffController: Тариф не найден', [
                'user_id' => $user->id,
                'slug' => $slug
            ]);
            return response()->json(['error' => __('validation.tariffs.tariff_not_found')], 404);
        }

        // Анализ изменения тарифа
        $this->logTariffChangeAnalysis($user, $tariff);

        // Обрабатываем платеж с баланса пользователя
        if ($paymentMethod === 'USER_BALANCE') {
            return $this->processBalancePayment($user, $tariff, $billingType);
        }

        // Вычисляем сумму платежа для внешних платежных систем
        $amount = $tariff->amount;
        if ($billingType === 'year') {
            $amount = $tariff->amount * 12 * 0.8; // 20% скидка за годовую подписку
        }

        Log::info('TariffController: Расчет суммы платежа', [
            'user_id' => $user->id,
            'tariff_id' => $tariff->id,
            'billing_type' => $billingType,
            'base_amount' => $tariff->amount,
            'calculated_amount' => $amount,
            'discount_applied' => $billingType === 'year' ? '20%' : 'none',
        ]);

        // Подготавливаем данные для Pay2.House
        $paymentData = [
            'external_number' => $this->pay2Service->generateExternalNumber($user->id, $tariff->id),
            'amount' => $amount,
            'currency_code' => 'USD',
            'description' => __('tariffs.payment_description', ['name' => $tariff->name]),
            'payer_email' => $user->email,
            'payment_method' => $paymentMethod,
            'handling_fee' => 0,
            'return_url' => config('pay2.tariffs_return_url'),
            'cancel_url' => config('pay2.tariffs_cancel_url'),
        ];

        Log::info('TariffController: Подготовленные данные для платежа', [
            'paymentData' => $paymentData,
        ]);

        // Создаем платеж через Pay2.House
        $paymentResult = $this->pay2Service->createPayment($paymentData);

        if (! $paymentResult['success']) {
            Log::error('TariffController: Ошибка создания платежа', [
                'user_id' => $user->id,
                'tariff_id' => $tariff->id,
                'error' => $paymentResult['error'],
            ]);

            return response()->json([
                'error' => 'Failed to create payment',
                'details' => $paymentResult['error'],
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
            'approval_url' => $paymentResult['data']['approval_url'],
        ]);

        Log::info('TariffController: Платеж создан успешно', [
            'user_id' => $user->id,
            'tariff_id' => $tariff->id,
            'payment_id' => $payment->id,
            'external_number' => $paymentData['external_number'],
            'invoice_number' => $paymentResult['data']['invoice_number'],
            'amount' => $amount,
        ]);

        return response()->json([
            'success' => true,
            'payment_url' => $paymentResult['data']['approval_url'],
            'invoice_number' => $paymentResult['data']['invoice_number'],
            'external_number' => $paymentData['external_number'],
            'continue_payment_url' => $payment->getContinuePaymentUrl(), // Ссылка для продолжения платежа
        ]);
    }

    /**
     * Анализ и логирование изменения тарифа
     */
    private function logTariffChangeAnalysis(User $user, Subscription $newTariff): void
    {
        $currentSubscriptionId = $user->subscription_id;
        $currentSubscriptionEnd = $user->subscription_time_end;
        $currentTime = time();

        if (! $currentSubscriptionId) {
            Log::info('TariffController: Первая покупка тарифа', [
                'user_id' => $user->id,
                'new_tariff_id' => $newTariff->id,
                'new_tariff_name' => $newTariff->name,
            ]);

            return;
        }

        $currentSubscription = Subscription::find($currentSubscriptionId);
        if (! $currentSubscription) {
            Log::warning('TariffController: Текущий тариф не найден в базе', [
                'user_id' => $user->id,
                'current_subscription_id' => $currentSubscriptionId,
                'new_tariff_id' => $newTariff->id,
            ]);

            return;
        }

        // Исправляем ошибку типов: конвертируем Carbon в timestamp
        $timeLeft = $currentSubscriptionEnd ? ($currentSubscriptionEnd->timestamp - $currentTime) : 0;
        $isUpgrade = $newTariff->isHigherTierThan($currentSubscription);
        $isDowngrade = $newTariff->isLowerTierThan($currentSubscription);
        $isRenewal = $currentSubscriptionId === $newTariff->id;

        Log::info('TariffController: Анализ смены тарифа', [
            'user_id' => $user->id,
            'current_subscription' => [
                'id' => $currentSubscription->id,
                'name' => $currentSubscription->name,
                'amount' => $currentSubscription->amount,
                'priority' => $currentSubscription->getTariffPriority(),
            ],
            'new_subscription' => [
                'id' => $newTariff->id,
                'name' => $newTariff->name,
                'amount' => $newTariff->amount,
                'priority' => $newTariff->getTariffPriority(),
            ],
            'change_type' => $isRenewal ? 'renewal' : ($isUpgrade ? 'upgrade' : ($isDowngrade ? 'downgrade' : 'same_tier')),
            'time_left_seconds' => $timeLeft,
            'time_left_days' => round($timeLeft / 86400, 2),
            'current_subscription_end' => $currentSubscriptionEnd,
        ]);

        // Расчет компенсации времени если есть оставшееся время
        if ($timeLeft > 0 && ! $isRenewal) {
            Log::info('TariffController: Компенсация времени будет рассчитана в BalanceService', [
                'user_id' => $user->id,
                'time_left_seconds' => $timeLeft,
                'time_left_days' => round($timeLeft / 86400, 2),
                'is_upgrade' => $isUpgrade,
                'current_subscription' => $currentSubscription->name,
                'new_subscription' => $newTariff->name,
            ]);
        }
    }

    /**
     * Обработка платежа с баланса пользователя
     *
     * @param  User  $user
     * @param  Subscription  $tariff
     * @return \Illuminate\Http\JsonResponse
     */
    protected function processBalancePayment($user, $tariff, string $billingType)
    {
        // Генерируем идемпотентный ключ на основе параметров запроса
        $idempotencyKey = md5($user->id . $tariff->id . $billingType . time());
        
        Log::info('TariffController: Начало обработки платежа с баланса', [
            'user_id' => $user->id,
            'tariff_id' => $tariff->id,
            'billing_type' => $billingType,
            'user_balance' => $user->available_balance,
            'idempotency_key' => $idempotencyKey,
        ]);

        try {
            // Обрабатываем платеж через BalanceService с идемпотентным ключом
            $result = $this->balanceService->processSubscriptionPaymentFromBalance($user, $tariff, $billingType, $idempotencyKey);

            if (! $result['success']) {
                Log::warning('TariffController: Ошибка платежа с баланса', [
                    'user_id' => $user->id,
                    'tariff_id' => $tariff->id,
                    'error' => $result['error'],
                    'idempotency_key' => $idempotencyKey,
                ]);

                return response()->json([
                    'success' => false,
                    'error' => $result['error'],
                ], 400);
            }

            Log::info('TariffController: Платеж с баланса выполнен успешно', [
                'user_id' => $user->id,
                'tariff_id' => $tariff->id,
                'payment_id' => $result['payment']->id,
                'message' => $result['message'],
                'idempotency_key' => $idempotencyKey,
            ]);

            return response()->json([
                'success' => true,
                'message' => $result['message'],
                'payment_id' => $result['payment']->id,
                'redirect_url' => config('pay2.tariffs_return_url') . '?invoice_number=' . $result['payment']->invoice_number,
            ]);
        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('TariffController: Ошибка при обработке платежа с баланса', [
                'user_id' => $user->id,
                'tariff_id' => $tariff->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'idempotency_key' => $idempotencyKey,
            ]);

            return response()->json([
                'success' => false,
                'error' => __('tariffs.errors.payment_processing_failed'),
            ], 500);
        }
    }

    /**
     * Show the payment page for a specific tariff
     */
    public function payment($slug, Request $request, $billingType = null)
    {
        // Проверяем корректность типа подписки
        if (! in_array($billingType, ['month', 'year'])) {
            abort(404, 'Invalid billing type');
        }

        // Ищем тариф по slug
        $tariff = Subscription::findBySlug($slug);

        if (! $tariff) {
            abort(404, 'Tariff not found');
        }

        // Определить тип операции
        $user = $request->user();
        $currentTariff = $user->currentTariff();
        $isRenewal = $currentTariff && $currentTariff['id'] === $tariff->id;
        $isUpgrade = $currentTariff && $currentTariff['id'] !== $tariff->id && $currentTariff['id'] !== null;

        $paymentMethods = PaymentMethod::getForFrontend();

        // Вычисляем стоимость подписки
        $monthlyAmount = $tariff->amount;
        $yearlyAmount = $tariff->amount * 12 * 0.8; // 20% скидка
        $selectedAmount = $billingType === 'year' ? $yearlyAmount : $monthlyAmount;

        return view('pages.tariffs.payment', [
            'tariff' => $tariff,
            'billingType' => $billingType,
            'isRenewal' => $isRenewal,
            'isUpgrade' => $isUpgrade,
            'currentEndDate' => $user->subscription_time_end,
            'paymentMethods' => $paymentMethods,
            'userBalance' => $user->available_balance,
            'monthlyAmount' => $monthlyAmount,
            'yearlyAmount' => $yearlyAmount,
            'selectedAmount' => $selectedAmount,
            'hasInsufficientBalance' => $user->available_balance < $selectedAmount,
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
                    'payment_data' => $paymentDetails['data'],
                ]);
            }
        }

        return view('pages.tariffs.payment-success', [
            'invoice_number' => $invoiceNumber,
        ]);
    }

    /**
     * Handle cancelled payment return from Pay2.House
     */
    public function paymentCancel(Request $request)
    {
        $invoiceNumber = $request->get('invoice_number');

        Log::info('TariffController: Отмена платежа', [
            'invoice_number' => $invoiceNumber,
        ]);

        return view('pages.tariffs.payment-cancel', [
            'invoice_number' => $invoiceNumber,
        ]);
    }

    /**
     * Continue payment - redirect to original approval_url
     */
    public function continuePayment(Request $request, $invoiceNumber)
    {
        Log::info('TariffController: Запрос на продолжение платежа', [
            'invoice_number' => $invoiceNumber,
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        // Находим платеж по invoice_number
        $payment = Payment::where('invoice_number', $invoiceNumber)->first();

        if (!$payment) {
            Log::warning('TariffController: Платеж не найден для продолжения', [
                'invoice_number' => $invoiceNumber,
            ]);

            return redirect()->route('tariffs.index')->with('error', __('tariffs.errors.payment_not_found'));
        }

        // Проверяем что есть approval_url
        if (empty($payment->approval_url)) {
            Log::warning('TariffController: Отсутствует approval_url для продолжения', [
                'payment_id' => $payment->id,
                'invoice_number' => $invoiceNumber,
            ]);

            return redirect()->route('tariffs.index')->with('error', __('tariffs.errors.payment_not_found'));
        }

        Log::info('TariffController: Перенаправление на approval_url', [
            'payment_id' => $payment->id,
            'invoice_number' => $invoiceNumber,
            'user_id' => $payment->user_id,
            'status' => $payment->status->value,
        ]);

        // Перенаправляем на approval_url от платежной системы
        return redirect($payment->approval_url);
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
                    'pagination' => $payments,
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

    /**
     * Get pending payments for current user (for future reminders functionality)
     */
    public function getPendingPayments(Request $request)
    {
        $user = $request->user();

        $pendingPayments = $user->payments()
            ->where('status', PaymentStatus::PENDING)
            ->whereNotNull('approval_url')
            ->orderBy('created_at', 'desc')
            ->get()
            ->filter(fn($payment) => !empty($payment->approval_url))
            ->map(fn($payment) => [
                'id' => $payment->id,
                'amount' => $payment->getFormattedAmount(),
                'payment_type' => $payment->payment_type->value,
                'created_at' => $payment->created_at->format('d.m.Y H:i'),
                'hours_old' => $payment->created_at->diffInHours(now()),
                'continue_url' => $payment->getContinuePaymentUrl(),
                'subscription_name' => $payment->subscription?->name,
            ])
            ->values();

        Log::info('TariffController: Получен список незавершенных платежей', [
            'user_id' => $user->id,
            'pending_count' => $pendingPayments->count(),
        ]);

        return response()->json([
            'success' => true,
            'pending_payments' => $pendingPayments,
        ]);
    }
}

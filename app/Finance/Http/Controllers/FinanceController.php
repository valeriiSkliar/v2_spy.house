<?php

namespace App\Finance\Http\Controllers;

use App\Enums\Finance\PaymentMethod;
use App\Enums\Finance\PaymentStatus;
use App\Enums\Finance\PaymentType;
use App\Finance\Http\Requests\DepositValidationRequest;
use App\Finance\Models\Payment;
use App\Finance\Services\Pay2Service;
use App\Http\Controllers\Controller;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class FinanceController extends Controller
{
    use AuthorizesRequests;

    protected $pay2Service;

    public function __construct(Pay2Service $pay2Service)
    {
        $this->pay2Service = $pay2Service;
    }

    /**
     * Отображение страницы финансов
     */
    public function index(Request $request)
    {
        $transactions = $request->user()->deposits()->orderBy('created_at', 'desc')->paginate(10);
        $paymentMethods = collect(PaymentMethod::getForFrontend())
            ->filter(fn($method) => $method['value'] !== 'USER_BALANCE')
            ->values()
            ->all();

        return view('pages.finances.index', [
            'transactions' => $transactions,
            'paymentMethods' => $paymentMethods,
        ]);
    }

    /**
     * AJAX endpoint для асинхронной загрузки истории транзакций
     */
    public function ajaxList(Request $request)
    {
        // Переиспользование логики index()
        $view = $this->index($request);

        if ($request->ajax()) {
            $transactions = $view->getData()['transactions'];

            // Рендерим HTML фрагменты
            $transactionsHtml = view('components.finances.transactions-list', [
                'transactions' => $transactions,
            ])->render();

            $paginationHtml = '';
            $hasPagination = $transactions->hasPages();
            if ($hasPagination) {
                $paginationHtml = $transactions->links()->render();
            }

            // Возврат JSON в формате совместимом с ajaxFetcher
            return response()->json([
                'success' => true,
                'data' => [
                    'html' => $transactionsHtml,
                    'pagination' => $paginationHtml,
                    'hasPagination' => $hasPagination,
                    'currentPage' => $transactions->currentPage(),
                    'totalPages' => $transactions->lastPage(),
                    'count' => $transactions->count(),
                ],
            ]);
        }

        return $view;
    }

    /**
     * Отображение формы пополнения баланса
     */
    public function depositForm(Request $request)
    {
        $paymentMethods = collect(PaymentMethod::getForFrontend())
            ->filter(fn($method) => $method['value'] !== 'USER_BALANCE')
            ->values()
            ->all();

        return view('pages.finances.deposit', [
            'paymentMethods' => $paymentMethods,
        ]);
    }

    /**
     * Validate deposit form fields (AJAX endpoint)
     */
    public function validateDeposit(DepositValidationRequest $request)
    {
        // Валидация выполняется автоматически через DepositValidationRequest
        return response()->json([
            'success' => true,
            'message' => 'Validation passed'
        ]);
    }

    /**
     * Обработка запроса на депозит
     */
    public function deposit(DepositValidationRequest $request)
    {
        $user = $request->user();

        // Если валидация уже была пройдена асинхронно, пропускаем повторную проверку
        if ($request->has('_validation_passed')) {
            Log::info('FinanceController: Валидация была пройдена асинхронно', [
                'user_id' => $user->id,
            ]);
        }

        // Используем методы Request класса для получения валидированных данных
        $amount = $request->getValidatedAmount();
        $paymentMethod = $request->getValidatedPaymentMethod();

        // Логирование для отладки
        Log::info('FinanceController: Получены данные депозита', [
            'user_id' => $user->id,
            'amount' => $amount,
            'payment_method' => $paymentMethod,
            'request_all' => $request->all(),
        ]);

        // Подготавливаем данные для Pay2.House
        $paymentData = [
            'external_number' => $this->pay2Service->generateExternalNumber($user->id, 0), // 0 для депозитов
            'amount' => $amount,
            'currency_code' => 'USD',
            'description' => "Пополнение баланса на ${amount}",
            'payer_email' => $user->email,
            'payment_method' => $paymentMethod,
            'handling_fee' => 0,
            'return_url' => config('pay2.finance_return_url'),
            'cancel_url' => config('pay2.finance_cansel_url'),
        ];

        // Создаем платеж через Pay2.House
        $paymentResult = $this->pay2Service->createPayment($paymentData);

        if (! $paymentResult['success']) {
            Log::error('FinanceController: Ошибка создания депозита', [
                'user_id' => $user->id,
                'amount' => $amount,
                'error' => $paymentResult['error'],
            ]);

            if ($request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'error' => 'Не удалось создать платеж. Попробуйте позже.',
                ], 400);
            }

            return back()->withErrors([
                'amount' => 'Не удалось создать платеж. Попробуйте позже.',
            ]);
        }

        // Проверяем что в ответе есть необходимые данные
        if (! isset($paymentResult['data']['invoice_number']) || ! isset($paymentResult['data']['approval_url'])) {
            Log::error('FinanceController: Неполный ответ от API', [
                'user_id' => $user->id,
                'amount' => $amount,
                'response_data' => $paymentResult['data'] ?? 'no data',
            ]);

            if ($request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'error' => 'Некорректный ответ от платежной системы. Попробуйте позже.',
                ], 400);
            }

            return back()->withErrors([
                'amount' => 'Некорректный ответ от платежной системы. Попробуйте позже.',
            ]);
        }

        // Сохраняем информацию о платеже в локальной базе данных
        $payment = Payment::create([
            'user_id' => $user->id,
            'amount' => $amount,
            'payment_type' => PaymentType::DEPOSIT,
            'subscription_id' => null, // Для депозитов нет подписки
            'payment_method' => PaymentMethod::from($paymentMethod),
            'status' => PaymentStatus::PENDING,
            'invoice_number' => $paymentResult['data']['invoice_number'],
            'external_number' => $paymentData['external_number'],
            'approval_url' => $paymentResult['data']['approval_url'],
        ]);

        Log::info('FinanceController: Депозит создан успешно', [
            'user_id' => $user->id,
            'amount' => $amount,
            'payment_id' => $payment->id,
            'external_number' => $paymentData['external_number'],
            'invoice_number' => $paymentResult['data']['invoice_number'],
        ]);

        // Возвращаем JSON для AJAX или редирект для обычных запросов
        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'payment_url' => $paymentResult['data']['approval_url'],
                'invoice_number' => $paymentResult['data']['invoice_number'],
                'external_number' => $paymentData['external_number'],
            ]);
        }

        // Перенаправляем на платежную страницу
        return redirect($paymentResult['data']['approval_url']);
    }

    /**
     * Обработка успешного возврата после пополнения
     */
    public function depositSuccess(Request $request)
    {
        $invoiceNumber = $request->get('invoice_number');

        return view('pages.finances.deposit-success', [
            'invoice_number' => $invoiceNumber,
        ]);
    }

    /**
     * Обработка отмененного пополнения
     */
    public function depositCancel(Request $request)
    {
        $invoiceNumber = $request->get('invoice_number');

        return view('pages.finances.deposit-cancel', [
            'invoice_number' => $invoiceNumber,
        ]);
    }

    /**
     * Continue deposit payment - redirect to original approval_url
     */
    public function continueDeposit(Request $request, $invoiceNumber)
    {
        Log::info('FinanceController: Запрос на продолжение депозита', [
            'invoice_number' => $invoiceNumber,
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        // Находим платеж по invoice_number
        $payment = Payment::where('invoice_number', $invoiceNumber)->first();

        if (!$payment) {
            Log::warning('FinanceController: Депозит не найден для продолжения', [
                'invoice_number' => $invoiceNumber,
            ]);

            return redirect()->route('finances.index')->with('error', 'Платеж не найден');
        }

        // Проверяем что это депозит
        if ($payment->payment_type !== PaymentType::DEPOSIT) {
            Log::warning('FinanceController: Платеж не является депозитом', [
                'payment_id' => $payment->id,
                'invoice_number' => $invoiceNumber,
                'payment_type' => $payment->payment_type->value,
            ]);

            return redirect()->route('finances.index')->with('error', 'Недействительный тип платежа');
        }

        // Проверяем что есть approval_url
        if (empty($payment->approval_url)) {
            Log::warning('FinanceController: Отсутствует approval_url для продолжения депозита', [
                'payment_id' => $payment->id,
                'invoice_number' => $invoiceNumber,
            ]);

            return redirect()->route('finances.index')->with('error', 'Ссылка для оплаты недоступна');
        }

        Log::info('FinanceController: Перенаправление на approval_url для депозита', [
            'payment_id' => $payment->id,
            'invoice_number' => $invoiceNumber,
            'user_id' => $payment->user_id,
            'status' => $payment->status->value,
        ]);

        // Перенаправляем на approval_url от платежной системы
        return redirect($payment->approval_url);
    }
}

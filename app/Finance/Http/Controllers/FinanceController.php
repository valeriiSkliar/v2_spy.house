<?php

namespace App\Finance\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use App\Finance\Services\Pay2Service;
use App\Finance\Models\Payment;
use App\Enums\Finance\PaymentType;
use App\Enums\Finance\PaymentMethod;
use App\Enums\Finance\PaymentStatus;
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

        return view('pages.finances.index', [
            'transactions' => $transactions,
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
                'transactions' => $transactions
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
                ]
            ]);
        }

        return $view;
    }

    /**
     * Отображение формы пополнения баланса
     */
    public function depositForm(Request $request)
    {
        // $paymentMethods = PaymentMethod::cases();

        // return view('pages.finances.index', [
        //     'paymentMethods' => $paymentMethods,
        // ]);
        return redirect()->route('finances.index');
    }

    /**
     * Обработка запроса на депозит
     */
    public function deposit(Request $request)
    {
        $request->validate([
            'amount' => 'required|numeric|min:50|max:1000',
            'payment_method' => 'required|string',
        ], [
            'amount.required' => 'Сумма пополнения обязательна',
            'amount.numeric' => 'Сумма должна быть числом',
            'amount.min' => 'Минимальная сумма пополнения: $50',
            'amount.max' => 'Максимальная сумма пополнения: $1,000',
            'payment_method.required' => 'Выберите способ оплаты',
        ]);

        $user = $request->user();
        $amount = floatval($request->amount);
        $paymentMethod = $request->payment_method;

        // Логирование для отладки
        Log::info('FinanceController: Получены данные депозита', [
            'user_id' => $user->id,
            'amount' => $amount,
            'payment_method' => $paymentMethod,
            'request_all' => $request->all()
        ]);

        // TODO:  delete  this Временное исправление для совместимости со старыми формами
        if ($paymentMethod === 'Tether') {
            $paymentMethod = 'USDT';
            Log::warning('FinanceController: Исправлено значение Tether на USDT');
        } elseif ($paymentMethod === 'Pay2.House' || $paymentMethod === 'pay2') {
            $paymentMethod = 'PAY2.HOUSE';
            Log::warning('FinanceController: Исправлено значение на PAY2.HOUSE');
        }

        // Подготавливаем данные для Pay2.House
        $paymentData = [
            'external_number' => $this->pay2Service->generateExternalNumber($user->id, 0), // 0 для депозитов
            'amount' => $amount,
            'currency_code' => 'USD',
            'description' => "Пополнение баланса на ${amount}",
            'payer_email' => $user->email,
            'payment_method' => $paymentMethod,
            'handling_fee' => 0,
            'return_url' => route('finances.deposit.success'),
            'cancel_url' => route('finances.deposit.cancel'),
        ];

        // Создаем платеж через Pay2.House
        $paymentResult = $this->pay2Service->createPayment($paymentData);

        if (!$paymentResult['success']) {
            Log::error('FinanceController: Ошибка создания депозита', [
                'user_id' => $user->id,
                'amount' => $amount,
                'error' => $paymentResult['error']
            ]);

            return back()->withErrors([
                'amount' => 'Не удалось создать платеж. Попробуйте позже.'
            ]);
        }

        // Проверяем что в ответе есть необходимые данные
        if (!isset($paymentResult['data']['invoice_number']) || !isset($paymentResult['data']['approval_url'])) {
            Log::error('FinanceController: Неполный ответ от API', [
                'user_id' => $user->id,
                'amount' => $amount,
                'response_data' => $paymentResult['data'] ?? 'no data'
            ]);

            return back()->withErrors([
                'amount' => 'Некорректный ответ от платежной системы. Попробуйте позже.'
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
        ]);

        Log::info('FinanceController: Депозит создан успешно', [
            'user_id' => $user->id,
            'amount' => $amount,
            'payment_id' => $payment->id,
            'external_number' => $paymentData['external_number'],
            'invoice_number' => $paymentResult['data']['invoice_number']
        ]);

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
            'invoice_number' => $invoiceNumber
        ]);
    }

    /**
     * Обработка отмененного пополнения
     */
    public function depositCancel(Request $request)
    {
        $invoiceNumber = $request->get('invoice_number');

        return view('pages.finances.deposit-cancel', [
            'invoice_number' => $invoiceNumber
        ]);
    }
}

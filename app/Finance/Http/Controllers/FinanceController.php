<?php

namespace App\Finance\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;

class FinanceController extends Controller
{
    use AuthorizesRequests;

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
     * Обработка запроса на депозит
     */
    public function deposit(Request $request)
    {
        $request->validate([
            'amount' => 'required|numeric|min:1',
            'payment_method' => 'required|string',
        ], [
            'amount.required' => __('finances.deposit_form.validation.amount_required'),
            'amount.numeric' => __('finances.deposit_form.validation.amount_numeric'),
            'amount.min' => __('finances.deposit_form.validation.amount_min', ['min' => 1]),
            'payment_method.required' => __('finances.deposit_form.validation.payment_method_required'),
        ]);

        // Логика создания депозита
        // TODO: Реализовать сохранение в базу данных

        return redirect()->route('finances.index')
            ->with('success', __('finances.messages.deposit_success'));
    }
}

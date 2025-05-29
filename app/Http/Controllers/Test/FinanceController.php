<?php

namespace App\Http\Controllers\Test;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class FinanceController extends Controller
{
    /**
     * Отображение страницы финансов
     */
    public function index()
    {
        // Временные данные для демонстрации
        $transactions = [
            [
                'date' => '08.04.2025 / 11:10',
                'transactionNumber' => 'TN5780107516',
                'paymentMethod' => __('finances.payment_methods.tether'),
                'amount' => '60',
                'status' => __('finances.history_table.statuses.pending'),
                'statusClass' => ''
            ],
            [
                'date' => '08.04.2025 / 11:10',
                'transactionNumber' => 'TN5780107516',
                'paymentMethod' => __('finances.payment_methods.tether'),
                'amount' => '60',
                'status' => __('finances.history_table.statuses.successful'),
                'statusClass' => '_successful'
            ],
            [
                'date' => '08.04.2025 / 11:10',
                'transactionNumber' => 'TN5780107516',
                'paymentMethod' => __('finances.payment_methods.tether'),
                'amount' => '60',
                'status' => __('finances.history_table.statuses.rejected'),
                'statusClass' => '_rejected'
            ]
        ];

        return view('pages.finances.index', compact('transactions'));
    }

    /**
     * Обработка запроса на депозит
     */
    public function deposit(Request $request)
    {
        // Валидация
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

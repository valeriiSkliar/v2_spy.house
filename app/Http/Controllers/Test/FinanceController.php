<?php

namespace App\Http\Controllers\Test;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class FinanceController extends Controller
{
    /**
     * Отображение страницы финансов
     */
    public function index()
    {
        // Здесь будет логика получения истории депозитов и других финансовых данных
        return view('finances.index');
    }

    /**
     * Обработка запроса на депозит
     */
    public function deposit(Request $request)
    {
        // Валидация
        $request->validate([
            'amount' => 'required|numeric|min:1',
            'payment_method' => 'required|string'
        ]);

        // Логика создания депозита

        return redirect()->route('finances.index')
            ->with('success', 'Deposit request submitted successfully');
    }
}

<?php

namespace App\Finance\Http\Requests;

use App\Enums\Finance\PaymentMethod;
use App\Http\Requests\BaseRequest;
use Illuminate\Validation\Rule;

class DepositValidationRequest extends BaseRequest
{
    /**
     * Определяет авторизацию пользователя для выполнения запроса
     */
    public function authorize(): bool
    {
        return \Illuminate\Support\Facades\Auth::check();
    }

    /**
     * Правила валидации для депозита
     */
    public function rules(): array
    {
        return [
            'amount' => [
                'required',
                'numeric',
                'min:50',
                'max:1000'
            ],
            'payment_method' => [
                'required',
                'string',
                Rule::in(collect(PaymentMethod::getValidForDeposits())->pluck('value')->toArray())
            ],
        ];
    }

    /**
     * Пользовательские сообщения об ошибках
     */
    public function messages(): array
    {
        return [
            'amount.required' => 'Сумма пополнения обязательна',
            'amount.numeric' => 'Сумма должна быть числом',
            'amount.min' => 'Минимальная сумма пополнения: $50',
            'amount.max' => 'Максимальная сумма пополнения: $1,000',
            'payment_method.required' => 'Выберите способ оплаты',
            'payment_method.in' => 'Некорректный способ оплаты',
        ];
    }

    /**
     * Подготовка данных перед валидацией
     */
    protected function prepareForValidation(): void
    {
        // Временное исправление для совместимости со старыми формами
        $paymentMethod = $this->input('payment_method');

        if ($paymentMethod === 'Tether') {
            $this->merge(['payment_method' => 'USDT']);
        } elseif ($paymentMethod === 'Pay2.House' || $paymentMethod === 'pay2') {
            $this->merge(['payment_method' => 'PAY2.HOUSE']);
        }

        // Преобразуем amount в float если это строка
        if ($this->has('amount')) {
            $this->merge([
                'amount' => floatval($this->input('amount'))
            ]);
        }
    }

    /**
     * Получить валидированную сумму
     */
    public function getValidatedAmount(): float
    {
        return floatval($this->validated()['amount']);
    }

    /**
     * Получить валидированный способ оплаты
     */
    public function getValidatedPaymentMethod(): string
    {
        return $this->validated()['payment_method'];
    }
}

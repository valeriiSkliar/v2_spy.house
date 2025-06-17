<?php

namespace App\Finance\Http\Requests;

use App\Enums\Finance\PaymentMethod;
use App\Http\Requests\BaseRequest;
use Illuminate\Validation\Rule;

class ProcessPaymentRequest extends BaseRequest
{
    /**
     * Определяет авторизацию пользователя для выполнения запроса
     */
    public function authorize(): bool
    {
        return \Illuminate\Support\Facades\Auth::check();
    }

    /**
     * Правила валидации для обработки платежа
     */
    public function rules(): array
    {
        return [
            'payment_method' => [
                'required',
                'string',
                Rule::in(PaymentMethod::values())
            ],
            'promo_code' => [
                'nullable',
                'string',
                'min:3',
                'max:50',
                'regex:/^[A-Za-z0-9_-]+$/'
            ],
            'is_renewal' => [
                'required',
                'boolean'
            ],
            'is_upgrade' => [
                'nullable',
                'boolean'
            ]
        ];
    }

    /**
     * Пользовательские сообщения об ошибках
     */
    public function messages(): array
    {
        return [
            'payment_method.required' => 'Выберите способ оплаты',
            'payment_method.in' => 'Некорректный способ оплаты',
            'promo_code.min' => 'Промокод должен содержать минимум :min символов',
            'promo_code.max' => 'Промокод не может содержать более :max символов',
            'promo_code.regex' => 'Промокод может содержать только латинские буквы, цифры, дефисы и подчеркивания',
            'is_renewal.required' => 'Не указан тип операции',
            'is_renewal.boolean' => 'Некорректное значение типа операции'
        ];
    }

    /**
     * Подготовка данных перед валидацией
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'is_renewal' => (bool) $this->input('is_renewal', false),
            'is_upgrade' => (bool) $this->input('is_upgrade', false),
        ]);

        // Очищаем промокод от лишних пробелов
        if ($this->has('promo_code') && $this->input('promo_code')) {
            $this->merge([
                'promo_code' => trim(strtoupper($this->input('promo_code')))
            ]);
        }
    }
}

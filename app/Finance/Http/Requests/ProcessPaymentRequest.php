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
            'payment_method.required' => __('validation.tariffs.payment_method_required'),
            'payment_method.in' => __('validation.tariffs.payment_method_invalid'),
            'promo_code.min' => __('validation.tariffs.promo_code_min'),
            'promo_code.max' => __('validation.tariffs.promo_code_max'),
            'promo_code.regex' => __('validation.tariffs.promo_code_regex'),
            'is_renewal.required' => __('validation.tariffs.is_renewal_required'),
            'is_renewal.boolean' => __('validation.tariffs.is_renewal_boolean')
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

<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

class EmailVerificationRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'code' => 'required|array|size:6',
            'code.*' => 'required|string|size:1|regex:/^[0-9]$/',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'code.required' => __('validation.custom.code.required'),
            'code.array' => __('validation.custom.code.array'),
            'code.size' => __('validation.custom.code.size'),
            'code.*.required' => __('validation.custom.code.*.required'),
            'code.*.regex' => __('validation.custom.code.*.regex'),
        ];
    }

    /**
     * Get the verification code as a string.
     */
    public function getVerificationCode(): string
    {
        return implode('', $this->input('code'));
    }
}

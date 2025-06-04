<?php

namespace App\Http\Requests\Profile;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class UpdatePersonalGreetingSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Auth::check();
    }

    public function rules(): array
    {
        return [
            'personal_greeting' => [
                'required',
                'string',
                'min:3',
                'max:100',
            ],
            'confirmation_method' => [
                'required',
                'string',
                Rule::in(['email', 'authenticator']),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'personal_greeting.required' => __('validation.personal_greeting.required'),
            'personal_greeting.string' => __('validation.personal_greeting.string'),
            'personal_greeting.min' => __('validation.personal_greeting.min', ['min' => 3]),
            'personal_greeting.max' => __('validation.personal_greeting.max', ['max' => 100]),
            'confirmation_method.required' => __('validation.confirmation_method.required'),
            'confirmation_method.in' => __('validation.confirmation_method.in'),
        ];
    }

    public function attributes(): array
    {
        return [
            'personal_greeting' => __('validation.personal_greeting.label'),
            'confirmation_method' => __('validation.confirmation_method.label'),
        ];
    }
}

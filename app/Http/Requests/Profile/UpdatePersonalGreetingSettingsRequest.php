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
                Rule::in(['email', 'authenticator'])
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'personal_greeting.required' => __('validation.required'),
            'personal_greeting.string' => __('validation.string'),
            'personal_greeting.min' => __('validation.min.string', ['min' => 3]),
            'personal_greeting.max' => __('validation.max.string', ['max' => 100]),
            'confirmation_method.required' => __('profile.security_settings.confirmation_method_required'),
            'confirmation_method.in' => __('profile.security_settings.invalid_confirmation_method'),
        ];
    }

    public function attributes(): array
    {
        return [
            'personal_greeting' => __('profile.personal_greeting_label'),
            'confirmation_method' => __('profile.security_settings.confirmation_method_label'),
        ];
    }
}

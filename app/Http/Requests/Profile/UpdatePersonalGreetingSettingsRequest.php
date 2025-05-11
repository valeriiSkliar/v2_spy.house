<?php

namespace App\Http\Requests\Profile;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule; // Add this

class UpdatePersonalGreetingSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Auth::check();
    }

    public function rules(): array
    {
        return [
            'personal_greeting' => 'nullable|string|max:255|min:3',
            'confirmation_method' => ['required', 'string', Rule::in(['email', 'authenticator'])],
        ];
    }

    public function messages(): array
    {
        return [
            'confirmation_method.required' => __('profile.security_settings.confirmation_method_required'),
            'confirmation_method.in' => __('profile.security_settings.invalid_confirmation_method'),
        ];
    }
}

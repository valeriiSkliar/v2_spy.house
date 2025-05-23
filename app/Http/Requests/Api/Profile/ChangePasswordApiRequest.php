<?php

namespace App\Http\Requests\Api\Profile;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Hash;

class ChangePasswordApiRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Авторизация через auth:sanctum
    }

    public function rules(): array
    {
        return [
            'current_password' => ['required', 'string', 'current_password'],
            'password' => [
                'required',
                'string',
                'min:8',
                'confirmed',
                function ($attribute, $value, $fail) {
                    if (Hash::check($value, $this->user()->password)) {
                        $fail(__('profile.validation.new_password_same_as_current'));
                    }
                },
            ],
            'password_confirmation' => ['required', 'string'],
            'confirmation_method' => ['required', 'string', 'in:email,authenticator'],
        ];
    }

    public function messages(): array // Опционально, для кастомных сообщений
    {
        return [
            'current_password.required' => __('profile.validation.current_password_required'),
            'password.required' => __('profile.validation.new_password_required'),
            'password.confirmed' => __('profile.validation.passwords_do_not_match'),
        ];
    }
}

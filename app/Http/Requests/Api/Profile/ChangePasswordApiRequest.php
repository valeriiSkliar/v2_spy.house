<?php

namespace App\Http\Requests\Api\Profile;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class ChangePasswordApiRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Авторизация через auth:sanctum
    }

    public function rules(): array
    {
        return [
            'current_password' => [
                'required',
                'string',
                function ($attribute, $value, $fail) {
                    if (!Hash::check($value, $this->user()->password)) {
                        // Используйте ваши ключи локализации
                        $fail(__('profile.messages.current_password_incorrect'));
                    }
                },
            ],
            'password' => ['required', 'string', Password::defaults(), 'confirmed'],
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

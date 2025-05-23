<?php

namespace App\Http\Requests\Profile;

use App\Enums\Frontend\UserExperience;
use App\Enums\Frontend\UserScopeOfActivity;
use App\Http\Requests\BaseRequest;
use App\Models\User;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class RegisteredUserRequest extends BaseRequest
{
    // /**
    //  * Determine if the user is authorized to make this request.
    //  */
    // public function authorize(): bool
    // {
    //     return false;
    // }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'login' => ['required', 'string', 'max:255', 'regex:/^[a-zA-Z0-9_]+$/', Rule::unique('users', 'login')],
            'messenger_type' => ['required', 'string', Rule::in(['whatsapp', 'viber', 'telegram'])],
            'messenger_contact' => [
                'required',
                'string',
                Rule::unique('users')->where(function ($query) {
                    return $query->where('messenger_type', $this->input('messenger_type'));
                }),
                function ($attribute, $value, $fail) {
                    $messengerType = $this->input('messenger_type');

                    switch ($messengerType) {
                        case 'telegram':
                            if ($value && ! $this->validation_telegram_login($value)) {
                                $fail('Invalid Telegram username format. Must start with @ and contain 5-32 characters (letters, numbers, underscore).');
                            }
                            break;
                        case 'viber':
                            if ($value && ! $this->validation_viber_identifier($value)) {
                                $fail('Invalid Viber phone number format. Must contain 10-15 digits.');
                            }
                            break;
                        case 'whatsapp':
                            if ($value && ! $this->validation_whatsapp_identifier($value)) {
                                $fail('Invalid WhatsApp phone number format. Must contain 10-15 digits.');
                            }
                            break;
                        default:
                            $fail('Invalid messenger type. Must be one of: telegram, viber, whatsapp.');
                            break;
                    }
                },
            ],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:'.User::class],
            'password' => ['required', 'confirmed', Password::defaults()],
            // Use values instead of names for validation - the dropdown sends value not enum name
            'experience' => ['required', 'string', 'in:'.implode(',', UserExperience::names())],
            // Use values instead of names for validation
            'scope_of_activity' => ['required', 'string', 'in:'.implode(',', UserScopeOfActivity::names())],
        ];
    }

    protected function prepareForValidation(): void
    {
        // Sanitize all input fields
        if ($this->has('login') && $this->input('login') !== null) {
            $this->merge(['login' => $this->sanitizeInput($this->input('login'))]);
        }

        if ($this->has('messenger_type') && $this->input('messenger_type') !== null) {
            $this->merge(['messenger_type' => $this->sanitizeInput($this->input('messenger_type'))]);
        }

        if ($this->has('messenger_contact') && $this->input('messenger_contact') !== null) {
            $this->merge(['messenger_contact' => $this->sanitizeInput($this->input('messenger_contact'))]);
        }

        if ($this->has('email') && $this->input('email') !== null) {
            $this->merge(['email' => $this->sanitizeInput($this->input('email'))]);
        }

        if ($this->has('password') && $this->input('password') !== null) {
            $this->merge(['password' => $this->sanitizeInput($this->input('password'))]);
        }

        if ($this->has('experience') && $this->input('experience') !== null) {
            $this->merge(['experience' => $this->sanitizeInput($this->input('experience'))]);
        }

        if ($this->has('scope_of_activity') && $this->input('scope_of_activity') !== null) {
            $this->merge(['scope_of_activity' => $this->sanitizeInput($this->input('scope_of_activity'))]);
        }
    }

    protected function sanitizeInput($input): string
    {
        return trim($input);
    }

    /**
     * Get the error messages for the defined validation rules.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'login.required' => 'Логин обязателен',
            'login.string' => 'Логин должен быть строкой',
            'login.max' => 'Логин не должен превышать 255 символов',
            'login.regex' => 'Логин должен содержать только латинские буквы, цифры и символ подчеркивания',
            'login.unique' => 'Этот логин уже занят',
            'messenger_type.required' => 'Тип мессенджера обязателен',
            'messenger_type.string' => 'Тип мессенджера должен быть строкой',
            'messenger_type.in' => 'Выбран недопустимый тип мессенджера',
            'messenger_contact.required' => 'Контакт мессенджера обязателен',
            'messenger_contact.string' => 'Контакт мессенджера должен быть строкой',
            'messenger_contact.unique' => 'Этот контакт уже зарегистрирован с указанным типом мессенджера',
            'email.required' => 'Email обязателен',
            'email.string' => 'Email должен быть строкой',
            'email.lowercase' => 'Email должен быть в нижнем регистре',
            'email.email' => 'Введите корректный email адрес',
            'email.max' => 'Email не должен превышать 255 символов',
            'email.unique' => 'Этот email уже зарегистрирован',
            'password.required' => 'Пароль обязателен',
            'password.confirmed' => 'Пароли не совпадают',
            'experience.required' => 'Опыт обязателен',
            'experience.string' => 'Опыт должен быть строкой',
            'experience.in' => 'Выбрано недопустимое значение опыта',
            'scope_of_activity.required' => 'Сфера деятельности обязательна',
            'scope_of_activity.string' => 'Сфера деятельности должна быть строкой',
            'scope_of_activity.in' => 'Выбрано недопустимое значение сферы деятельности',
        ];
    }
}

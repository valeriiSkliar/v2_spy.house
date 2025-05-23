<?php

namespace App\Http\Requests\Profile;

use App\Enums\Frontend\UserExperience;
use App\Enums\Frontend\UserScopeOfActivity;
use App\Http\Requests\BaseRequest;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class ProfileSettingsUpdateRequest extends BaseRequest
{
    public function authorize(): bool
    {
        return Auth::check();
    }

    public function rules(): array
    {
        $user = Auth::user();

        return [
            'login' => [
                'required',
                'string',
                'max:255',
                'regex:/^[a-zA-Z0-9_]+$/',
                Rule::unique('users', 'login')->ignore($user->id),
            ],
            'messenger_type' => ['required', 'string', Rule::in(['whatsapp', 'viber', 'telegram'])],
            'messenger_contact' => ['required', 'string', function ($attribute, $value, $fail) {
                $messengerType = $this->input('messenger_type');

                switch ($messengerType) {
                    case 'telegram':
                        if ($value && ! $this->validation_telegram_login($value)) {
                            $fail('Неверный формат имени пользователя Telegram. Должен начинаться с @ и содержать 5-32 символа (буквы, цифры, подчеркивание).');
                        }
                        break;
                    case 'viber':
                        if ($value && ! $this->validation_viber_identifier($value)) {
                            $fail('Неверный формат номера телефона Viber. Должен содержать 10-15 цифр.');
                        }
                        break;
                    case 'whatsapp':
                        if ($value && ! $this->validation_whatsapp_identifier($value)) {
                            $fail('Неверный формат номера телефона WhatsApp. Должен содержать 10-15 цифр.');
                        }
                        break;
                    default:
                        $fail('Неверный тип мессенджера. Должен быть один из: telegram, viber, whatsapp.');
                        break;
                }
            }],
            // Use values instead of names for validation - the dropdown sends value not enum name
            'experience' => ['nullable', 'string', 'in:'.implode(',', UserExperience::names())],
            // Use values instead of names for validation
            'scope_of_activity' => ['nullable', 'string', 'in:'.implode(',', UserScopeOfActivity::names())],
        ];
    }

    public function validated($key = null, $default = null): array
    {
        $validated = parent::validated();

        // Преобразуем дату рождения в формат Y-m-d
        if (isset($validated['date_of_birth']) && $validated['date_of_birth']) {
            $validated['date_of_birth'] = Carbon::parse($validated['date_of_birth'])->format('Y-m-d');
        }

        // Исключаем пустые значения
        $fields = ['messenger_type', 'messenger_contact', 'login', 'experience', 'scope_of_activity'];
        foreach ($fields as $field) {
            if (isset($validated[$field]) && empty(trim($validated[$field]))) {
                unset($validated[$field]);
            }
        }

        return $validated;
    }

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
            'experience.required' => 'Опыт обязателен',
            'experience.string' => 'Опыт должен быть строкой',
            'experience.in' => 'Выбрано недопустимое значение опыта',
            'scope_of_activity.required' => 'Сфера деятельности обязательна',
            'scope_of_activity.string' => 'Сфера деятельности должна быть строкой',
            'scope_of_activity.in' => 'Выбрано недопустимое значение сферы деятельности',
        ];
    }

    protected function prepareForValidation(): void
    {
        // Sanitize all input fields
        if ($this->has('login') && $this->input('login') !== null) {
            $this->merge(['login' => $this->sanitizeInput($this->input('login'))]);
        }

        if ($this->has('experience') && $this->input('experience') !== null) {
            $this->merge(['experience' => $this->sanitizeInput($this->input('experience'))]);
        }

        if ($this->has('scope_of_activity') && $this->input('scope_of_activity') !== null) {
            $this->merge(['scope_of_activity' => $this->sanitizeInput($this->input('scope_of_activity'))]);
        }

        if ($this->has('messenger_type') && $this->input('messenger_type') !== null) {
            $this->merge(['messenger_type' => $this->sanitizeInput($this->input('messenger_type'))]);
        }

        if ($this->has('messenger_contact') && $this->input('messenger_contact') !== null) {
            $this->merge(['messenger_contact' => $this->sanitizeInput($this->input('messenger_contact'))]);
        }
    }
}

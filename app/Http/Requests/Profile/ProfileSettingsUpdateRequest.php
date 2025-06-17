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
                            $fail(__('validation.profile.telegram_format'));
                        }
                        break;
                    case 'viber':
                        if ($value && ! $this->validation_viber_identifier($value)) {
                            $fail(__('validation.profile.phone_format'));
                        }
                        break;
                    case 'whatsapp':
                        if ($value && ! $this->validation_whatsapp_identifier($value)) {
                            $fail(__('validation.profile.phone_format'));
                        }
                        break;
                    default:
                        $fail(__('validation.validation_error'));
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
            'login.required' => __('validation.registered_user.required'),
            'login.string' => __('validation.registered_user.string'),
            'login.max' => __('validation.registered_user.max'),
            'login.regex' => __('validation.registered_user.regex'),
            'login.unique' => __('validation.registered_user.unique'),
            'messenger_type.required' => __('validation.registered_user.required'),
            'messenger_type.string' => __('validation.registered_user.string'),
            'messenger_type.in' => __('validation.registered_user.in'),
            'messenger_contact.required' => __('validation.registered_user.required'),
            'messenger_contact.string' => __('validation.registered_user.string'),
            'experience.required' => __('validation.registered_user.required'),
            'experience.string' => __('validation.registered_user.string'),
            'experience.in' => __('validation.registered_user.in'),
            'scope_of_activity.required' => __('validation.registered_user.required'),
            'scope_of_activity.string' => __('validation.registered_user.string'),
            'scope_of_activity.in' => __('validation.registered_user.in'),
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

<?php

namespace App\Http\Requests\Profile;

use App\Enums\Frontend\UserExperience;
use App\Enums\Frontend\UserScopeOfActivity;
use App\Http\Requests\BaseRequest;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;

class ProfileSettingsUpdateRequest extends BaseRequest
{
    public function authorize(): bool
    {
        return Auth::check();
    }

    public function rules(): array
    {
        return [
            'login' => ['nullable', 'string', 'max:255'],
            // Use values instead of names for validation - the dropdown sends value not enum name
            'experience' => ['nullable', 'string', 'in:' . implode(',', UserExperience::names())],
            // Use values instead of names for validation
            'scope_of_activity' => ['nullable', 'string', 'in:' . implode(',', UserScopeOfActivity::names())],
            // Avatar is now handled by the API endpoint
            'telegram' => ['nullable', 'string', function ($attribute, $value, $fail) {
                if ($value && !$this->validation_telegram_login($value)) {
                    $fail('Invalid Telegram username format');
                }
            }],
            'viber_phone' => ['nullable', 'string', function ($attribute, $value, $fail) {
                if ($value && !$this->validation_viber_identifier($value)) {
                    $fail('Invalid Viber phone number format');
                }
            }],
            'whatsapp_phone' => ['nullable', 'string', function ($attribute, $value, $fail) {
                if ($value && !$this->validation_whatsapp_identifier($value)) {
                    $fail('Invalid WhatsApp phone number format');
                }
            }],
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
        $fields = ['telegram', 'viber_phone', 'whatsapp_phone', 'login', 'experience', 'scope_of_activity'];
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
            'login.required' => 'Login is required',
            'login.string' => 'Login must be a string',
            'login.max' => 'Login must be less than 255 characters',
            'experience.required' => 'Experience is required',
            'experience.string' => 'Experience must be a string',
            'experience.in' => 'Invalid experience value',
            'scope_of_activity.required' => 'Scope of activity is required',
            'scope_of_activity.string' => 'Scope of activity must be a string',
            'scope_of_activity.in' => 'Invalid scope of activity value',
            'telegram.string' => 'Telegram username must be a string',
            'viber_phone.string' => 'Viber phone number must be a string',
            'whatsapp_phone.string' => 'WhatsApp phone number must be a string',
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

        if ($this->has('telegram') && $this->input('telegram') !== null) {
            $this->merge(['telegram' => $this->sanitizeInput($this->input('telegram'))]);
        }

        if ($this->has('viber_phone') && $this->input('viber_phone') !== null) {
            $this->merge(['viber_phone' => $this->sanitizeInput($this->input('viber_phone'))]);
        }

        if ($this->has('whatsapp_phone') && $this->input('whatsapp_phone') !== null) {
            $this->merge(['whatsapp_phone' => $this->sanitizeInput($this->input('whatsapp_phone'))]);
        }
    }

    private function validation_telegram_login(string $str): bool
    {
        if (!str_starts_with($str, '@')) {
            return false;
        }

        $clean_str = substr($str, 1);
        $length = strlen($clean_str);

        if ($length < 5 || $length > 32) {
            return false;
        }

        return preg_match('/^[A-Za-z0-9_]{5,32}$/', $clean_str) === 1;
    }

    private function validation_whatsapp_identifier(string $str): bool
    {
        $clean_str  = preg_replace('/[^0-9+]/', '', $str);
        $identifier = str_starts_with($clean_str, '+') ? substr($clean_str, 1) : trim($clean_str);

        $length = strlen($identifier);
        if ($length < 10 || $length > 15) {
            return false;
        }

        return preg_match('/^[0-9]{10,15}$/', $identifier) === 1;
    }

    private function validation_viber_identifier(string $str): bool
    {
        $clean_str  = preg_replace('/[^0-9+]/', '', $str);
        $identifier = str_starts_with($clean_str, '+') ? substr($clean_str, 1) : trim($clean_str);

        $length = strlen($identifier);
        if ($length < 10 || $length > 15) {
            return false;
        }

        return preg_match('/^[0-9]{10,15}$/', $identifier) === 1;
    }
}

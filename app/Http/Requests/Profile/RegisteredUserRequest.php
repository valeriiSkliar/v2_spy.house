<?php

namespace App\Http\Requests\Profile;

use App\Enums\Frontend\UserExperience;
use App\Enums\Frontend\UserScopeOfActivity;
use App\Http\Requests\BaseRequest;
use App\Models\User;
use App\Rules\Recaptcha;
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
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:' . User::class],
            'password' => ['required', 'confirmed', Password::defaults()],
            // Use values instead of names for validation - the dropdown sends value not enum name
            'experience' => ['required', 'string', 'in:' . implode(',', UserExperience::names())],
            // Use values instead of names for validation
            'scope_of_activity' => ['required', 'string', 'in:' . implode(',', UserScopeOfActivity::names())],
            'g-recaptcha-response' => ['required', new Recaptcha],
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

    /**
     * Get the error messages for the defined validation rules.
     *
     * @return array<string, string>
     */
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
            'messenger_contact.unique' => __('validation.registered_user.unique'),
            'email.required' => __('validation.registered_user.required'),
            'email.string' => __('validation.registered_user.string'),
            'email.lowercase' => __('validation.registered_user.lowercase'),
            'email.email' => __('validation.registered_user.email'),
            'email.max' => __('validation.registered_user.max'),
            'email.unique' => __('validation.registered_user.unique'),
            'password.required' => __('validation.registered_user.required'),
            'password.confirmed' => __('validation.registered_user.confirmed'),
            'experience.required' => __('validation.registered_user.required'),
            'experience.string' => __('validation.registered_user.string'),
            'experience.in' => __('validation.registered_user.in'),
            'scope_of_activity.required' => __('validation.registered_user.required'),
            'scope_of_activity.string' => __('validation.registered_user.string'),
            'scope_of_activity.in' => __('validation.registered_user.in'),
            'g-recaptcha-response.required' => __('validation.registered_user.recaptcha'),
        ];
    }
}

<?php

namespace App\Http\Requests\Profile;

use App\Enums\Frontend\UserExperience;
use App\Enums\Frontend\UserScopeOfActivity;
use App\Http\Requests\BaseRequest;
use App\Models\User;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Validation\Rule;

class ProfileUpdateRequest extends BaseRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $userId = $this->user()->id;

        return [
            'login' => [
                'required',
                'string',
                'max:255',
                'regex:/^[a-zA-Z0-9_]+$/',
                Rule::unique('users', 'login')->ignore($userId),
            ],
            'experience' => 'nullable|string|in:'.implode(',', UserExperience::names()),
            'scope_of_activity' => 'nullable|string|in:'.implode(',', UserScopeOfActivity::names()),
            'messenger_type' => 'nullable|string|in:telegram,viber,whatsapp',
            'messenger_contact' => [
                'nullable',
                'string',
                'max:255',
                function ($attribute, $value, $fail) {
                    $messengerType = $this->input('messenger_type');

                    if ($messengerType && $value) {
                        switch ($messengerType) {
                            case 'telegram':
                                if (! $this->validation_telegram_login($value)) {
                                    $fail(__('validation.telegram_format'));
                                }
                                break;
                            case 'viber':
                                if (! $this->validation_viber_identifier($value)) {
                                    $fail(__('validation.phone_format'));
                                }
                                break;
                            case 'whatsapp':
                                if (! $this->validation_whatsapp_identifier($value)) {
                                    $fail(__('validation.phone_format'));
                                }
                                break;
                        }
                    }
                },
            ],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'login.required' => __('validation.required', ['attribute' => __('profile.login')]),
            'login.regex' => __('validation.login_format'),
            'login.unique' => __('validation.login_taken'),
            'login.max' => __('validation.max.string', ['attribute' => __('profile.login'), 'max' => 255]),
            'messenger_type.in' => __('validation.messenger_type_invalid'),
            'messenger_contact.max' => __('validation.max.string', ['attribute' => __('profile.messenger_contact'), 'max' => 255]),
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'login' => __('profile.login'),
            'experience' => __('profile.experience'),
            'scope_of_activity' => __('profile.scope_of_activity'),
            'messenger_type' => __('profile.messenger_type'),
            'messenger_contact' => __('profile.messenger_contact'),
        ];
    }

    /**
     * Handle a failed validation attempt.
     */
    protected function failedValidation(Validator $validator)
    {
        if ($this->expectsJson()) {
            $response = response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);

            throw new \Illuminate\Validation\ValidationException($validator, $response);
        }

        parent::failedValidation($validator);
    }
}

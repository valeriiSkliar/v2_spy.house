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
        $user = $this->user();

        $loginRules = [
            'required',
            'string',
            'max:255',
            'regex:/^[a-zA-Z0-9_]+$/',
            Rule::unique('users', 'login')->ignore($userId),
        ];

        return [
            'login' => $loginRules,
            'experience' => 'nullable|string|in:' . implode(',', UserExperience::names()),
            'scope_of_activity' => 'nullable|string|in:' . implode(',', UserScopeOfActivity::names()),
            'messenger_type' => 'nullable|string|in:telegram,viber,whatsapp',
            'messenger_contact' => [
                'nullable',
                'string',
                'max:255',
                function ($attribute, $value, $fail) use ($userId) {
                    $messengerType = $this->input('messenger_type');

                    if ($messengerType && $value) {
                        // Check for unique combination of messenger_type + messenger_contact
                        $exists = User::where('messenger_type', $messengerType)
                            ->where('messenger_contact', $value)
                            ->where('id', '!=', $userId)
                            ->exists();

                        if ($exists) {
                            $fail(__('validation.messenger_contact_taken'));
                            return;
                        }

                        // Format validation
                        switch ($messengerType) {
                            case 'telegram':
                                if (!parent::validation_telegram_login($value)) {
                                    $fail(__('validation.telegram_format'));
                                }
                                break;
                            case 'viber':
                                if (!parent::validation_viber_identifier($value)) {
                                    $fail(__('validation.phone_format'));
                                }
                                break;
                            case 'whatsapp':
                                if (!parent::validation_whatsapp_identifier($value)) {
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
            'messenger_contact.messenger_contact_taken' => __('validation.messenger_contact_taken'),
            'experience.in' => __('validation.experience_invalid'),
            'scope_of_activity.in' => __('validation.scope_of_activity_invalid'),
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
}

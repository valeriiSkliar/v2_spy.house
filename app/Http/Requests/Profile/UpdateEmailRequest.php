<?php

namespace App\Http\Requests\Profile;

use App\Http\Requests\BaseRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;

class UpdateEmailRequest extends BaseRequest
{
    public function authorize(): bool
    {
        return Auth::check();
    }

    public function rules(): array
    {
        $user = Auth::user();

        return [
            'current_email' => [
                'required',
                'string',
                'email',
                'max:255',
                Rule::exists('users', 'email')->where(function ($query) use ($user) {
                    $query->where('id', $user->id);
                }),
            ],
            'new_email' => [
                'required',
                'string',
                'email',
                'max:255',
                'different:current_email',
                Rule::unique('users', 'email')->ignore($user->id),
            ],
            'password' => ['required', 'string', 'current_password'],
            'confirmation_method' => ['required', 'string', Rule::in(['email', 'authenticator'])],
            // 'verification_code' => ['nullable', 'string', 'required_if:confirmation_method,email|required_if:confirmation_method,authenticator', 'max:6'],
        ];
    }

    public function messages(): array
    {
        return [
            'new_email.unique' => __('validation.validation_error'),
            'password.current_password' => __('validation.validation_error'),
            'verification_code.required_if' => __('validation.validation_error'),
        ];
    }

    protected function prepareForValidation(): void
    {
        // dd($this->all());
        Log::info('Update email request', $this->all());
        $currentEmail = $this->input('current_email');
        $newEmail = $this->input('new_email');
        $password = $this->input('password');
        $confirmationMethod = $this->input('confirmation_method');
        $verificationCode = $this->input('verification_code');

        $this->merge([
            'current_email' => $this->sanitizeInput($currentEmail ?? ''),
            'new_email' => $this->sanitizeInput($newEmail ?? ''),
            'password' => $this->sanitizeInput($password ?? ''),
            'confirmation_method' => $this->sanitizeInput($confirmationMethod ?? ''),
            'verification_code' => $this->sanitizeInput($verificationCode ?? ''),
        ]);
    }
}

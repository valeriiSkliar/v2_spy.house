<?php

namespace App\Http\Requests;

use App\Rules\Recaptcha;
use Illuminate\Foundation\Http\FormRequest;

class ContactFormRequest extends FormRequest
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
     */
    public function rules(): array
    {
        $rules = [
            'name' => ['required', 'string', 'max:255', 'min:2'],
            'email' => ['required', 'email', 'max:255'],
            'message' => ['required', 'string', 'max:2000', 'min:10'],
        ];

        // Добавляем валидацию reCAPTCHA только если она включена
        if (config('captcha.enabled', true)) {
            $rules['g-recaptcha-response'] = ['required', new Recaptcha()];
        }

        return $rules;
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'name.required' => trans('validation.required', ['attribute' => trans('frontend.contact.name')]),
            'name.min' => trans('validation.min.string', ['attribute' => trans('frontend.contact.name'), 'min' => 2]),
            'name.max' => trans('validation.max.string', ['attribute' => trans('frontend.contact.name'), 'max' => 255]),

            'email.required' => trans('validation.required', ['attribute' => trans('frontend.contact.email')]),
            'email.email' => trans('validation.email', ['attribute' => trans('frontend.contact.email')]),
            'email.max' => trans('validation.max.string', ['attribute' => trans('frontend.contact.email'), 'max' => 255]),

            'message.required' => trans('validation.required', ['attribute' => trans('frontend.contact.message')]),
            'message.min' => trans('validation.min.string', ['attribute' => trans('frontend.contact.message'), 'min' => 10]),
            'message.max' => trans('validation.max.string', ['attribute' => trans('frontend.contact.message'), 'max' => 2000]),

            'g-recaptcha-response.required' => trans('frontend.form.recaptcha_required'),
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'name' => trans('frontend.contact.name'),
            'email' => trans('frontend.contact.email'),
            'message' => trans('frontend.contact.message'),
        ];
    }
}

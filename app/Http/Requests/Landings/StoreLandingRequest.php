<?php

namespace App\Http\Requests\Landings;

use App\Http\Requests\BaseRequest;

use function App\Helpers\sanitize_url;

class StoreLandingRequest extends BaseRequest
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
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'url' => [
                'required',
                'string',
                'max:300',
                function ($attribute, $value, $fail) {
                    $sanitizedUrl = sanitize_url($value);
                    $processedUrl = preg_replace('/\\{[^}]+\\}/', 'dummy', $sanitizedUrl);
                    if (! filter_var($processedUrl, FILTER_VALIDATE_URL)) {
                        $fail(__('validation.landing.regex'));
                    }
                },
            ],
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        if ($this->has('url')) {
            $this->merge([
                'url' => sanitize_url($this->input('url')),
            ]);
        }
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'url.required' => __('validation.landing.required'),
            'url.string' => __('validation.landing.string'),
            'url.max' => __('validation.landing.max', ['max' => 300]),
        ];
    }
}

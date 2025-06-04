<?php

namespace App\Http\Requests\Profile;

use App\Http\Requests\BaseRequest;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

class UpdateNotificationSettingsRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // Убедимся, что пользователь аутентифицирован
        return Auth::check();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $rules = [
            'notification_settings' => ['nullable', 'array'],
            'notification_settings.system' => ['nullable', 'boolean'],
        ];

        return $rules;
    }

    /**
     * Prepare the data for validation.
     *
     * Laravel автоматически обрабатывает 'on'/'off' от чекбоксов в 'boolean' при валидации,
     * но для явности можно добавить этот метод.
     * Также он гарантирует, что если массив не пришел, он будет пустым.
     */
    protected function prepareForValidation(): void
    {
        $settings = $this->input('notification_settings', []); // If array is not provided, make it empty
        $mergedSettings = [];

        // For simplified version, we only handle the 'system' key
        $mergedSettings['system'] = filter_var($settings['system'] ?? false, FILTER_VALIDATE_BOOLEAN);

        $this->merge([
            'notification_settings' => $mergedSettings,
        ]);
    }
}

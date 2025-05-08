<?php

namespace App\Http\Requests\Profile;

use App\Http\Requests\BaseRequest;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

class UpdateNotificationSettingsRequest extends FormRequest // Наследуемся от FormRequest, BaseRequest можно убрать если он не добавляет общую логику
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
        return [
            'notification_settings' => ['nullable', 'array'],
            'notification_settings.system' => ['nullable', 'boolean'],
            'notification_settings.news' => ['nullable', 'boolean'],
            'notification_settings.promotions' => ['nullable', 'boolean'],
            'notification_settings.security' => ['nullable', 'boolean'],
            'notification_settings.updates' => ['nullable', 'boolean'],
        ];
    }

    /**
     * Prepare the data for validation.
     *
     * Laravel автоматически обрабатывает 'on'/'off' от чекбоксов в 'boolean' при валидации,
     * но для явности можно добавить этот метод.
     * Также он гарантирует, что если массив не пришел, он будет пустым.
     *
     * @return void
     */
    protected function prepareForValidation(): void
    {
        $settings = $this->input('notification_settings', []); // Если массив не пришел, делаем его пустым

        $this->merge([
            'notification_settings' => [
                'system' => filter_var($settings['system'] ?? false, FILTER_VALIDATE_BOOLEAN),
                'news' => filter_var($settings['news'] ?? false, FILTER_VALIDATE_BOOLEAN),
                'promotions' => filter_var($settings['promotions'] ?? false, FILTER_VALIDATE_BOOLEAN),
                'security' => filter_var($settings['security'] ?? false, FILTER_VALIDATE_BOOLEAN),
                'updates' => filter_var($settings['updates'] ?? false, FILTER_VALIDATE_BOOLEAN),
            ],
        ]);
    }
}

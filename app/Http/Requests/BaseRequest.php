<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

use function App\Helpers\sanitize_input;

class BaseRequest extends FormRequest
{
    /**
     * Handle a failed validation attempt for AJAX requests.
     */
    protected function failedValidation(Validator $validator)
    {
        if ($this->expectsJson() || $this->ajax()) {
            throw new HttpResponseException(
                response()->json([
                    'status' => 'error',
                    'message' => __('validation.validation_error'),
                    'errors' => $validator->errors()->toArray(),
                ], 422)
            );
        }

        parent::failedValidation($validator);
    }

    protected function sanitizeInput(string $input): string
    {
        if (! $input) {
            return '';
        }
        $input = trim($input);
        // $input = strtolower($input);

        return sanitize_input($input);
    }

    protected function validation_telegram_login(string $str): bool
    {
        if (! str_starts_with($str, '@')) {
            return false;
        }

        $clean_str = substr($str, 1);
        $length = strlen($clean_str);

        if ($length < 5 || $length > 32) {
            return false;
        }

        return preg_match('/^[A-Za-z0-9_]{5,32}$/', $clean_str) === 1;
    }

    protected function validation_whatsapp_identifier(string $str): bool
    {
        $clean_str = preg_replace('/[^0-9+]/', '', $str);
        $identifier = str_starts_with($clean_str, '+') ? substr($clean_str, 1) : trim($clean_str);

        $length = strlen($identifier);
        if ($length < 10 || $length > 15) {
            return false;
        }

        return preg_match('/^[0-9]{10,15}$/', $identifier) === 1;
    }

    protected function validation_viber_identifier(string $str): bool
    {
        $clean_str = preg_replace('/[^0-9+]/', '', $str);
        $identifier = str_starts_with($clean_str, '+') ? substr($clean_str, 1) : trim($clean_str);

        $length = strlen($identifier);
        if ($length < 10 || $length > 15) {
            return false;
        }

        return preg_match('/^[0-9]{10,15}$/', $identifier) === 1;
    }
}

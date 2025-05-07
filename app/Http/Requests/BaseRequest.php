<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

use function App\Helpers\sanitize_input;

class BaseRequest extends FormRequest
{
    protected function sanitizeInput(string $input): string
    {
        if (!$input) {
            return '';
        }
        $input = trim($input);

        return sanitize_input($input);
    }
}

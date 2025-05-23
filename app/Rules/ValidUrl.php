<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

use function App\Helpers\sanitize_url;

class ValidUrl implements ValidationRule
{
    /**
     * Run the validation rule.
     *
     * @param  \Closure(string, ?string=): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (empty($value)) {
            $fail('The :attribute field is required.');

            return;
        }

        if (strlen($value) > 2048) {
            $fail('The :attribute field must not exceed 2048 characters.');

            return;
        }

        $sanitizedUrl = sanitize_url($value);

        if (! filter_var($sanitizedUrl, FILTER_VALIDATE_URL)) {
            $fail('The :attribute field must be a valid URL.');

            return;
        }

        $parsedUrl = parse_url($sanitizedUrl);
        $allowedProtocols = ['http', 'https'];

        if (! isset($parsedUrl['scheme']) || ! in_array($parsedUrl['scheme'], $allowedProtocols)) {
            $fail('The :attribute field must use a valid protocol (http or https).');

            return;
        }
    }
}

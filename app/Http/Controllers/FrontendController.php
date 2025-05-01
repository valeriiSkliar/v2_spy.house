<?php

namespace App\Http\Controllers;

use function App\Helpers\sanitize_input;


class FrontendController extends Controller
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

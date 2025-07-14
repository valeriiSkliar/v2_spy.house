<?php

namespace App\Helpers;

use voku\helper\AntiXSS;

if (! function_exists('App\Helpers\sanitize_input')) {
    /**
     * Sanitize input to prevent XSS attacks.
     */
    function sanitize_input(string $input): string
    {
        $antiXss = new AntiXSS;

        return $antiXss->xss_clean($input);
    }
}

if (! function_exists('App\Helpers\sanitize_url')) {
    /**
     * Sanitize a URL by removing potentially dangerous characters and normalizing the format
     *
     * @param  string  $url  The URL to sanitize
     * @return string The sanitized URL
     */
    function sanitize_url(string $url): string
    {
        // Remove any whitespace
        $url = trim($url);

        // Remove dangerous characters
        $url = filter_var($url, FILTER_SANITIZE_URL);

        // Remove multiple forward slashes except after protocol
        $url = preg_replace('#(?<!:)//+#', '/', $url);

        // Ensure protocol exists, default to https if not present
        if (! preg_match('~^(?:f|ht)tps?://~i', $url)) {
            $url = 'https://' . $url;
        }

        // Convert protocol to lowercase
        $url = preg_replace_callback(
            '~^(https?)://~i',
            function ($matches) {
                return strtolower($matches[0]);
            },
            $url
        );

        // Remove fragments as they're not needed for downloading
        $url = preg_replace('/#.*$/', '', $url);

        // Decode and then encode URL to ensure consistent encoding
        $url = rawurldecode($url);
        $url = rawurlencode($url);

        // Restore special characters that should not be encoded
        $url = str_replace(
            ['%3A', '%2F', '%3F', '%3D', '%26', '%25', '%23'],
            [':', '/', '?', '=', '&', '%', '#'],
            $url
        );

        return $url;
    }
}

if (! function_exists('App\Helpers\validation_json')) {
    /**
     * Validate if string is valid JSON
     *
     * @param  string  $string  The string to validate
     * @return bool
     */
    function validation_json($string)
    {
        if (! is_string($string)) {
            return false;
        }

        json_decode($string);

        return json_last_error() === JSON_ERROR_NONE;
    }
}

if (! function_exists('App\Helpers\format_count')) {
    /**
     * Format count numbers to shortened format (4500 -> 4.5k, 1300 -> 1.3k)
     *
     * @param  int|float  $count  The count to format
     * @return string The formatted count string
     */
    function format_count($count): string
    {
        if (!is_numeric($count) || $count < 0) {
            return '0';
        }

        $count = (float) $count;

        if ($count >= 1000000) {
            $formatted = $count / 1000000;
            return ($formatted == floor($formatted)) ?
                number_format($formatted, 0) . 'M' :
                rtrim(number_format($formatted, 1), '0') . 'M';
        }

        if ($count >= 1000) {
            $formatted = $count / 1000;
            return ($formatted == floor($formatted)) ?
                number_format($formatted, 0) . 'k' :
                rtrim(number_format($formatted, 1), '0') . 'k';
        }

        return number_format($count, 0);
    }
}

if (! function_exists('App\Helpers\get_tabs_order')) {

    /**
     * Возвращает фиксированный порядок отображения вкладок
     * Должен соответствовать TABS_ORDER в types/creatives.d.ts
     * 
     * @return array
     */
    function get_tabs_order(): array
    {
        return ['push', 'inpage', 'facebook', 'tiktok'];
    }
}

if (! function_exists('App\Helpers\get_tabs_data')) {

    /**
     * Возвращает данные по вкладкам для отображения
     * 
     * @return array
     */
    function get_tabs_data(): array
    {
        return [
            'push' => ['label' => 'Push'],
            'inpage' => ['label' => 'Inpage'],
            'facebook' => ['label' => 'Facebook'],
            'tiktok' => ['label' => 'Tiktok']
        ];
    }
}

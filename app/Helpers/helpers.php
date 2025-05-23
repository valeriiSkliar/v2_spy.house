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

if (! function_exists('sanitize_url')) {
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
            $url = 'https://'.$url;
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

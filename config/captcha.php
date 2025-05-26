<?php

return [
    'secret' => env('NOCAPTCHA_SECRET'),
    'sitekey' => env('NOCAPTCHA_SITEKEY'),
    'options' => [
        'timeout' => 30,
    ],

    // Глобальное включение/отключение reCAPTCHA
    'enabled' => env('RECAPTCHA_ENABLED', true),

    // Включение reCAPTCHA в локальной среде разработки
    'enabled_in_local' => env('RECAPTCHA_ENABLED_IN_LOCAL', true),
];

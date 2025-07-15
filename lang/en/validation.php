<?php

return [
    'validation_passed' => 'Validation successful',
    'validation_error' => 'Please check that the fields are filled in correctly.',
    'login_taken' => 'The data did not pass validation.',
    'messenger_contact_taken' => 'The data did not pass validation.',
    'unique_login_required' => 'The data did not pass validation.',
    'login_not_unique' => 'The data did not pass validation.',

    /*
    |--------------------------------------------------------------------------
    | Custom Validation Language Lines
    |--------------------------------------------------------------------------
    |
    | Here you may specify custom validation messages for attributes using the
    | convention "attribute.rule" to name the lines. This makes it quick to
    | specify a specific custom language line for a given attribute rule.
    |
    */

    'custom' => [
        'attribute-name' => [
            'rule-name' => 'Please check that the fields are filled in correctly.',
        ],
        'code' => [
            'required' => 'Please check that the fields are filled in correctly',
            'array' => 'Please check that the fields are filled in correctly',
            'size' => 'Please check that the fields are filled in correctly',
        ],
        'code.*' => [
            'required' => 'Please check that the fields are filled in correctly',
            'regex' => 'Please check that the fields are filled in correctly',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Custom Validation Attributes
    |--------------------------------------------------------------------------
    |
    | The following language lines are used to swap our attribute placeholder
    | with something more reader friendly such as "E-Mail Address" instead
    | of "email". This simply helps us make our message more expressive.
    |
    */

    'attributes' => [],

    // Custom messages
    'password_reset_throttled' => 'Too many attempts. Please try again later.',

    /*
    |--------------------------------------------------------------------------
    | Profile Validation Messages (Anti-phishing protection)
    |--------------------------------------------------------------------------
    |
    | These messages use generic wording to prevent revealing specific
    | information that could be used for social engineering attacks.
    |
    */
    'confirmation_method' => [
        'label' => 'Please check that the fields are filled in correctly.',
        'required' => 'Please check that the fields are filled in correctly.',
        'in' => 'Please check that the fields are filled in correctly.',
    ],

    'profile' => [
        'current_password_required' => 'Please check that the fields are filled in correctly.',
        'new_password_required' => 'Please check that the fields are filled in correctly.',
        'passwords_do_not_match' => 'Please check that the fields are filled in correctly.',
        'new_password_same_as_current' => 'Please check that the fields are filled in correctly.',
        'telegram_format' => 'Please check that the fields are filled in correctly.',
        'phone_format' => 'Please check that the fields are filled in correctly.',
        'invalid_verification_code' => 'Please check that the fields are filled in correctly.',
    ],
    'personal_greeting' => [
        'required' => 'Please check that the fields are filled in correctly.',
        'string' => 'Please check that the fields are filled in correctly.',
        'min' => 'Please check that the fields are filled in correctly.',
        'max' => 'Please check that the fields are filled in correctly.',
        'label' => 'Please check that the fields are filled in correctly.',
        'label_confirmation_method' => 'Please check that the fields are filled in correctly.',
    ],
    'ip_restrictions' => [
        'invalid' => 'Please check that the fields are filled in correctly.',
    ],
    'registered_user' => [
        'required' => 'Please check that the fields are filled in correctly.',
        'string' => 'Please check that the fields are filled in correctly.',
        'max' => 'Please check that the fields are filled in correctly.',
        'regex' => 'Please check that the fields are filled in correctly.',
        'unique' => 'Please check that the fields are filled in correctly.',
        'in' => 'Please check that the fields are filled in correctly.',
        'lowercase' => 'Email must be in lowercase',
        'email' => 'Please enter a valid email address',
        'confirmed' => 'Passwords do not match',
        'recaptcha' => 'Please confirm that you are not a robot',
    ],
    'landing' => [
        'required' => 'URL is required',
        'string' => 'URL is required',
        'max' => 'URL must not exceed :max characters',
        'regex' => 'URL must be in the format https://example.com',
        'unique' => 'URL is required',
    ],
    'auth' => [
        'failed' => 'Please check that the fields are filled in correctly.',
        'throttle' => 'Too many attempts. Please try again later.',
        '2fa_failed' => 'Invalid 2FA code',
        '2fa_required' => '2FA code is required',
    ],
    'blog' => [
        'page.integer' => 'Please check that the fields are filled in correctly.',
        'page.min' => 'Please check that the fields are filled in correctly.',
        'page.max' => 'Please check that the fields are filled in correctly.',
        'category.regex' => 'Please check that the fields are filled in correctly.',
        'category.max' => 'Please check that the fields are filled in correctly.',
        'search.max' => 'Please check that the fields are filled in correctly.',
        'search.min' => 'Please check that the fields are filled in correctly.',
        'q.min' => 'Please check that the fields are filled in correctly.',
        'q.max' => 'Please check that the fields are filled in correctly.',
        'sort.in' => 'Please check that the fields are filled in correctly.',
        'order.in' => 'Please check that the fields are filled in correctly.',
        'limit.min' => 'Please check that the fields are filled in correctly.',
        'limit.max' => 'Please check that the fields are filled in correctly.',
        'validation_failed' => 'Please check that the fields are filled in correctly.',
        'search.combination' => 'Please check that the fields are filled in correctly.',
        'category.not_found' => 'Please check that the fields are filled in correctly.',
        'page.search' => 'Please check that the fields are filled in correctly.',
    ],
    'tariffs' => [
        'invalid_payment_page_url' => 'Invalid payment page URL',
        'payment_method_required' => 'Payment method is required',
        'invalid_billing_type' => 'Please check that the fields are filled in correctly.',
        'tariff_not_found' => 'Please check that the fields are filled in correctly.',
        'payment_method_invalid' => 'Please check that the fields are filled in correctly.',
        'promo_code_min' => 'Please check that the fields are filled in correctly.',
        'promo_code_max' => 'Please check that the fields are filled in correctly.',
        'promo_code_regex' => 'Please check that the fields are filled in correctly.',
        'is_renewal_required' => 'Please check that the fields are filled in correctly.',
        'is_renewal_boolean' => 'Please check that the fields are filled in correctly.',
    ],

];

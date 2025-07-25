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

    /*
    |--------------------------------------------------------------------------
    | Standard Laravel Validation Language Lines
    |--------------------------------------------------------------------------
    |
    | The following language lines contain the default error messages used by
    | the validator class. Some of these rules have multiple versions such
    | as the size rules. Feel free to tweak each of these messages here.
    |
    */

    'accepted' => 'The :attribute field must be accepted.',
    'accepted_if' => 'The :attribute field must be accepted when :other is :value.',
    'active_url' => 'The :attribute field must be a valid URL.',
    'after' => 'The :attribute field must be a date after :date.',
    'after_or_equal' => 'The :attribute field must be a date after or equal to :date.',
    'alpha' => 'The :attribute field must only contain letters.',
    'alpha_dash' => 'The :attribute field must only contain letters, numbers, dashes, and underscores.',
    'alpha_num' => 'The :attribute field must only contain letters and numbers.',
    'array' => 'The :attribute field must be an array.',
    'ascii' => 'The :attribute field must only contain single-byte alphanumeric characters and symbols.',
    'before' => 'The :attribute field must be a date before :date.',
    'before_or_equal' => 'The :attribute field must be a date before or equal to :date.',
    'between' => [
        'array' => 'The :attribute field must have between :min and :max items.',
        'file' => 'The :attribute field must be between :min and :max kilobytes.',
        'numeric' => 'The :attribute field must be between :min and :max.',
        'string' => 'The :attribute field must be between :min and :max characters.',
    ],
    'boolean' => 'The :attribute field must be true or false.',
    'can' => 'The :attribute field contains an unauthorized value.',
    'confirmed' => 'The :attribute field confirmation does not match.',
    'current_password' => 'The password is incorrect.',
    'date' => 'The :attribute field must be a valid date.',
    'date_equals' => 'The :attribute field must be a date equal to :date.',
    'date_format' => 'The :attribute field must match the format :format.',
    'decimal' => 'The :attribute field must have :decimal decimal places.',
    'declined' => 'The :attribute field must be declined.',
    'declined_if' => 'The :attribute field must be declined when :other is :value.',
    'different' => 'The :attribute field and :other must be different.',
    'digits' => 'The :attribute field must be :digits digits.',
    'digits_between' => 'The :attribute field must be between :min and :max digits.',
    'dimensions' => 'The :attribute field has invalid image dimensions.',
    'distinct' => 'The :attribute field has a duplicate value.',
    'doesnt_end_with' => 'The :attribute field must not end with one of the following: :values.',
    'doesnt_start_with' => 'The :attribute field must not start with one of the following: :values.',
    'email' => 'The :attribute field must be a valid email address.',
    'ends_with' => 'The :attribute field must end with one of the following: :values.',
    'enum' => 'The selected :attribute is invalid.',
    'exists' => 'The selected :attribute is invalid.',
    'extensions' => 'The :attribute field must have one of the following extensions: :values.',
    'file' => 'The :attribute field must be a file.',
    'filled' => 'The :attribute field must have a value.',
    'gt' => [
        'array' => 'The :attribute field must have more than :value items.',
        'file' => 'The :attribute field must be greater than :value kilobytes.',
        'numeric' => 'The :attribute field must be greater than :value.',
        'string' => 'The :attribute field must be greater than :value characters.',
    ],
    'gte' => [
        'array' => 'The :attribute field must have :value items or more.',
        'file' => 'The :attribute field must be greater than or equal to :value kilobytes.',
        'numeric' => 'The :attribute field must be greater than or equal to :value.',
        'string' => 'The :attribute field must be greater than or equal to :value characters.',
    ],
    'hex_color' => 'The :attribute field must be a valid hexadecimal color.',
    'image' => 'The :attribute field must be an image.',
    'in' => 'The selected :attribute is invalid.',
    'in_array' => 'The :attribute field must exist in :other.',
    'integer' => 'The :attribute field must be an integer.',
    'ip' => 'The :attribute field must be a valid IP address.',
    'ipv4' => 'The :attribute field must be a valid IPv4 address.',
    'ipv6' => 'The :attribute field must be a valid IPv6 address.',
    'json' => 'The :attribute field must be a valid JSON string.',
    'list' => 'The :attribute field must be a list.',
    'lowercase' => 'The :attribute field must be lowercase.',
    'lt' => [
        'array' => 'The :attribute field must have less than :value items.',
        'file' => 'The :attribute field must be less than :value kilobytes.',
        'numeric' => 'The :attribute field must be less than :value.',
        'string' => 'The :attribute field must be less than :value characters.',
    ],
    'lte' => [
        'array' => 'The :attribute field must not have more than :value items.',
        'file' => 'The :attribute field must be less than or equal to :value kilobytes.',
        'numeric' => 'The :attribute field must be less than or equal to :value.',
        'string' => 'The :attribute field must be less than or equal to :value characters.',
    ],
    'mac_address' => 'The :attribute field must be a valid MAC address.',
    'max' => [
        'array' => 'The :attribute field must not have more than :max items.',
        'file' => 'The :attribute field must not be greater than :max kilobytes.',
        'numeric' => 'The :attribute field must not be greater than :max.',
        'string' => 'The :attribute field must not be greater than :max characters.',
    ],
    'max_digits' => 'The :attribute field must not have more than :max digits.',
    'mimes' => 'The :attribute field must be a file of type: :values.',
    'mimetypes' => 'The :attribute field must be a file of type: :values.',
    'min' => [
        'array' => 'The :attribute field must have at least :min items.',
        'file' => 'The :attribute field must be at least :min kilobytes.',
        'numeric' => 'The :attribute field must be at least :min.',
        'string' => 'The :attribute field must be at least :min characters.',
    ],
    'min_digits' => 'The :attribute field must have at least :min digits.',
    'missing' => 'The :attribute field must be missing.',
    'missing_if' => 'The :attribute field must be missing when :other is :value.',
    'missing_unless' => 'The :attribute field must be missing unless :other is :value.',
    'missing_with' => 'The :attribute field must be missing when :values is present.',
    'missing_with_all' => 'The :attribute field must be missing when :values are present.',
    'multiple_of' => 'The :attribute field must be a multiple of :value.',
    'not_in' => 'The selected :attribute is invalid.',
    'not_regex' => 'The :attribute field format is invalid.',
    'numeric' => 'The :attribute field must be a number.',
    'password' => [
        'letters' => 'The :attribute field must contain at least one letter.',
        'mixed' => 'The :attribute field must contain at least one uppercase and one lowercase letter.',
        'numbers' => 'The :attribute field must contain at least one number.',
        'symbols' => 'The :attribute field must contain at least one symbol.',
        'uncompromised' => 'The given :attribute has appeared in a data leak. Please choose a different :attribute.',
    ],
    'present' => 'The :attribute field must be present.',
    'present_if' => 'The :attribute field must be present when :other is :value.',
    'present_unless' => 'The :attribute field must be present unless :other is :value.',
    'present_with' => 'The :attribute field must be present when :values is present.',
    'present_with_all' => 'The :attribute field must be present when :values are present.',
    'prohibited' => 'The :attribute field is prohibited.',
    'prohibited_if' => 'The :attribute field is prohibited when :other is :value.',
    'prohibited_unless' => 'The :attribute field is prohibited unless :other is in :values.',
    'prohibits' => 'The :attribute field prohibits :other from being present.',
    'regex' => 'The :attribute field format is invalid.',
    'required' => 'The :attribute field is required.',
    'required_array_keys' => 'The :attribute field must contain entries for: :values.',
    'required_if' => 'The :attribute field is required when :other is :value.',
    'required_if_accepted' => 'The :attribute field is required when :other is accepted.',
    'required_if_declined' => 'The :attribute field is required when :other is declined.',
    'required_unless' => 'The :attribute field is required unless :other is in :values.',
    'required_with' => 'The :attribute field is required when :values is present.',
    'required_with_all' => 'The :attribute field is required when :values are present.',
    'required_without' => 'The :attribute field is required when :values is not present.',
    'required_without_all' => 'The :attribute field is required when none of :values are present.',
    'same' => 'The :attribute field must match :other.',
    'size' => [
        'array' => 'The :attribute field must contain :size items.',
        'file' => 'The :attribute field must be :size kilobytes.',
        'numeric' => 'The :attribute field must be :size.',
        'string' => 'The :attribute field must be :size characters.',
    ],
    'starts_with' => 'The :attribute field must start with one of the following: :values.',
    'string' => 'The :attribute field must be a string.',
    'timezone' => 'The :attribute field must be a valid timezone.',
    'unique' => 'The :attribute has already been taken.',
    'uploaded' => 'The :attribute failed to upload.',
    'uppercase' => 'The :attribute field must be uppercase.',
    'url' => 'The :attribute field must be a valid URL.',
    'ulid' => 'The :attribute field must be a valid ULID.',
    'uuid' => 'The :attribute field must be a valid UUID.',

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

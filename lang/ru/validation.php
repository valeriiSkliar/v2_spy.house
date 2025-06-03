<?php

return [
    'validation_error' => 'Проверьте правильность заполнения полей.',
    'login_taken' => 'Данные не прошли проверку.',
    'messenger_contact_taken' => 'Данные не прошли проверку.',
    'unique_login_required' => 'Данные не прошли проверку.',
    'login_not_unique' => 'Данные не прошли проверку.',

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
            'rule-name' => 'Проверьте правильность заполнения.',
        ],
        'code' => [
            'required' => 'Проверьте правильность заполнения полей',
            'array' => 'Проверьте правильность заполнения полей',
            'size' => 'Проверьте правильность заполнения полей',
        ],
        'code.*' => [
            'required' => 'Проверьте правильность заполнения полей',
            'regex' => 'Проверьте правильность заполнения полей',
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
    'password_reset_throttled' => 'Слишком много попыток. Повторите позже.',
];

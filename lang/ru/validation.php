<?php

return [
    'validation_error' => 'Ошибка валидации.',
    'login_taken' => 'Логин уже занят.',
    'messenger_contact_taken' => 'Контакт уже занят.',
    'unique_login_required' => 'Логин должен быть уникальным.',
    'login_not_unique' => 'Логин должен быть уникальным.',

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
            'rule-name' => 'custom-message',
        ],
        'code' => [
            'required' => 'Код подтверждения обязателен',
            'array' => 'Неверный формат кода',
            'size' => 'Код должен состоять из 6 цифр',
        ],
        'code.*' => [
            'required' => 'Все поля кода должны быть заполнены',
            'regex' => 'Код должен содержать только цифры',
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
    'password_reset_throttled' => 'Вы можете запросить восстановление пароля только через :hours часов после последнего успешного сброса.',
];

<?php

return [
    'validation_passed' => 'Успешная проверка',
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

    'accepted' => 'Поле :attribute должно быть принято.',
    'accepted_if' => 'Поле :attribute должно быть принято, когда :other равно :value.',
    'active_url' => 'Поле :attribute должно быть действительным URL.',
    'after' => 'Поле :attribute должно быть датой после :date.',
    'after_or_equal' => 'Поле :attribute должно быть датой после или равной :date.',
    'alpha' => 'Поле :attribute должно содержать только буквы.',
    'alpha_dash' => 'Поле :attribute должно содержать только буквы, цифры, дефисы и подчеркивания.',
    'alpha_num' => 'Поле :attribute должно содержать только буквы и цифры.',
    'array' => 'Поле :attribute должно быть массивом.',
    'ascii' => 'Поле :attribute должно содержать только однобайтовые буквенно-цифровые символы.',
    'before' => 'Поле :attribute должно быть датой до :date.',
    'before_or_equal' => 'Поле :attribute должно быть датой до или равной :date.',
    'between' => [
        'array' => 'Поле :attribute должно содержать от :min до :max элементов.',
        'file' => 'Поле :attribute должно быть от :min до :max килобайт.',
        'numeric' => 'Поле :attribute должно быть от :min до :max.',
        'string' => 'Поле :attribute должно содержать от :min до :max символов.',
    ],
    'boolean' => 'Поле :attribute должно быть true или false.',
    'can' => 'Поле :attribute содержит недопустимое значение.',
    'confirmed' => 'Подтверждение поля :attribute не совпадает.',
    'current_password' => 'Неправильный пароль.',
    'date' => 'Поле :attribute должно быть действительной датой.',
    'date_equals' => 'Поле :attribute должно быть датой, равной :date.',
    'date_format' => 'Поле :attribute должно соответствовать формату :format.',
    'decimal' => 'Поле :attribute должно содержать :decimal десятичных знаков.',
    'declined' => 'Поле :attribute должно быть отклонено.',
    'declined_if' => 'Поле :attribute должно быть отклонено, когда :other равно :value.',
    'different' => 'Поля :attribute и :other должны отличаться.',
    'digits' => 'Поле :attribute должно содержать :digits цифр.',
    'digits_between' => 'Поле :attribute должно содержать от :min до :max цифр.',
    'dimensions' => 'Поле :attribute имеет недопустимые размеры изображения.',
    'distinct' => 'Поле :attribute имеет повторяющееся значение.',
    'doesnt_end_with' => 'Поле :attribute не должно заканчиваться одним из следующих: :values.',
    'doesnt_start_with' => 'Поле :attribute не должно начинаться с одного из следующих: :values.',
    'email' => 'Поле :attribute должно быть действительным email адресом.',
    'ends_with' => 'Поле :attribute должно заканчиваться одним из следующих: :values.',
    'enum' => 'Выбранное значение для :attribute недопустимо.',
    'exists' => 'Выбранное значение для :attribute недопустимо.',
    'extensions' => 'Поле :attribute должно иметь одно из следующих расширений: :values.',
    'file' => 'Поле :attribute должно быть файлом.',
    'filled' => 'Поле :attribute должно иметь значение.',
    'gt' => [
        'array' => 'Поле :attribute должно содержать более :value элементов.',
        'file' => 'Поле :attribute должно быть больше :value килобайт.',
        'numeric' => 'Поле :attribute должно быть больше :value.',
        'string' => 'Поле :attribute должно содержать более :value символов.',
    ],
    'gte' => [
        'array' => 'Поле :attribute должно содержать :value элементов или более.',
        'file' => 'Поле :attribute должно быть :value килобайт или больше.',
        'numeric' => 'Поле :attribute должно быть :value или больше.',
        'string' => 'Поле :attribute должно содержать :value символов или более.',
    ],
    'hex_color' => 'Поле :attribute должно быть действительным шестнадцатеричным цветом.',
    'image' => 'Поле :attribute должно быть изображением.',
    'in' => 'Выбранное значение для :attribute недопустимо.',
    'in_array' => 'Поле :attribute должно существовать в :other.',
    'integer' => 'Поле :attribute должно быть целым числом.',
    'ip' => 'Поле :attribute должно быть действительным IP адресом.',
    'ipv4' => 'Поле :attribute должно быть действительным IPv4 адресом.',
    'ipv6' => 'Поле :attribute должно быть действительным IPv6 адресом.',
    'json' => 'Поле :attribute должно быть действительной JSON строкой.',
    'list' => 'Поле :attribute должно быть списком.',
    'lowercase' => 'Поле :attribute должно быть в нижнем регистре.',
    'lt' => [
        'array' => 'Поле :attribute должно содержать менее :value элементов.',
        'file' => 'Поле :attribute должно быть менее :value килобайт.',
        'numeric' => 'Поле :attribute должно быть менее :value.',
        'string' => 'Поле :attribute должно содержать менее :value символов.',
    ],
    'lte' => [
        'array' => 'Поле :attribute не должно содержать более :value элементов.',
        'file' => 'Поле :attribute должно быть :value килобайт или меньше.',
        'numeric' => 'Поле :attribute должно быть :value или меньше.',
        'string' => 'Поле :attribute должно содержать :value символов или меньше.',
    ],
    'mac_address' => 'Поле :attribute должно быть действительным MAC адресом.',
    'max' => [
        'array' => 'Поле :attribute не должно содержать более :max элементов.',
        'file' => 'Поле :attribute не должно быть больше :max килобайт.',
        'numeric' => 'Поле :attribute не должно быть больше :max.',
        'string' => 'Поле :attribute не должно содержать более :max символов.',
    ],
    'max_digits' => 'Поле :attribute не должно содержать более :max цифр.',
    'mimes' => 'Поле :attribute должно быть файлом типа: :values.',
    'mimetypes' => 'Поле :attribute должно быть файлом типа: :values.',
    'min' => [
        'array' => 'Поле :attribute должно содержать не менее :min элементов.',
        'file' => 'Поле :attribute должно быть не менее :min килобайт.',
        'numeric' => 'Поле :attribute должно быть не менее :min.',
        'string' => 'Поле :attribute должно содержать не менее :min символов.',
    ],
    'min_digits' => 'Поле :attribute должно содержать не менее :min цифр.',
    'missing' => 'Поле :attribute должно отсутствовать.',
    'missing_if' => 'Поле :attribute должно отсутствовать, когда :other равно :value.',
    'missing_unless' => 'Поле :attribute должно отсутствовать, если :other не равно :value.',
    'missing_with' => 'Поле :attribute должно отсутствовать, когда присутствует :values.',
    'missing_with_all' => 'Поле :attribute должно отсутствовать, когда присутствуют :values.',
    'multiple_of' => 'Поле :attribute должно быть кратным :value.',
    'not_in' => 'Выбранное значение для :attribute недопустимо.',
    'not_regex' => 'Формат поля :attribute недопустим.',
    'numeric' => 'Поле :attribute должно быть числом.',
    'password' => [
        'letters' => 'Поле :attribute должно содержать хотя бы одну букву.',
        'mixed' => 'Поле :attribute должно содержать хотя бы одну заглавную и одну строчную букву.',
        'numbers' => 'Поле :attribute должно содержать хотя бы одну цифру.',
        'symbols' => 'Поле :attribute должно содержать хотя бы один символ.',
        'uncompromised' => 'Указанное значение :attribute появилось в утечке данных. Пожалуйста, выберите другое значение :attribute.',
    ],
    'present' => 'Поле :attribute должно присутствовать.',
    'present_if' => 'Поле :attribute должно присутствовать, когда :other равно :value.',
    'present_unless' => 'Поле :attribute должно присутствовать, если :other не равно :value.',
    'present_with' => 'Поле :attribute должно присутствовать, когда присутствует :values.',
    'present_with_all' => 'Поле :attribute должно присутствовать, когда присутствуют :values.',
    'prohibited' => 'Поле :attribute запрещено.',
    'prohibited_if' => 'Поле :attribute запрещено, когда :other равно :value.',
    'prohibited_unless' => 'Поле :attribute запрещено, если :other не находится в :values.',
    'prohibits' => 'Поле :attribute запрещает присутствие :other.',
    'regex' => 'Формат поля :attribute недопустим.',
    'required' => 'Поле :attribute обязательно для заполнения.',
    'required_array_keys' => 'Поле :attribute должно содержать записи для: :values.',
    'required_if' => 'Поле :attribute обязательно для заполнения, когда :other равно :value.',
    'required_if_accepted' => 'Поле :attribute обязательно для заполнения, когда :other принято.',
    'required_if_declined' => 'Поле :attribute обязательно для заполнения, когда :other отклонено.',
    'required_unless' => 'Поле :attribute обязательно для заполнения, если :other не находится в :values.',
    'required_with' => 'Поле :attribute обязательно для заполнения, когда присутствует :values.',
    'required_with_all' => 'Поле :attribute обязательно для заполнения, когда присутствуют :values.',
    'required_without' => 'Поле :attribute обязательно для заполнения, когда отсутствует :values.',
    'required_without_all' => 'Поле :attribute обязательно для заполнения, когда отсутствуют все :values.',
    'same' => 'Поля :attribute и :other должны совпадать.',
    'size' => [
        'array' => 'Поле :attribute должно содержать :size элементов.',
        'file' => 'Поле :attribute должно быть :size килобайт.',
        'numeric' => 'Поле :attribute должно быть :size.',
        'string' => 'Поле :attribute должно содержать :size символов.',
    ],
    'starts_with' => 'Поле :attribute должно начинаться с одного из следующих: :values.',
    'string' => 'Поле :attribute должно быть строкой.',
    'timezone' => 'Поле :attribute должно быть действительным часовым поясом.',
    'unique' => 'Такое значение поля :attribute уже существует.',
    'uploaded' => 'Не удалось загрузить :attribute.',
    'uppercase' => 'Поле :attribute должно быть в верхнем регистре.',
    'url' => 'Поле :attribute должно быть действительным URL.',
    'ulid' => 'Поле :attribute должно быть действительным ULID.',
    'uuid' => 'Поле :attribute должно быть действительным UUID.',

    // Custom messages
    'password_reset_throttled' => 'Слишком много попыток. Повторите позже.',

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
        'label' => 'Проверьте правильность заполнения полей.',
        'required' => 'Проверьте правильность заполнения полей.',
        'in' => 'Проверьте правильность заполнения полей.',
    ],

    'profile' => [
        'current_password_required' => 'Проверьте правильность заполнения полей.',
        'new_password_required' => 'Проверьте правильность заполнения полей.',
        'passwords_do_not_match' => 'Проверьте правильность заполнения полей.',
        'new_password_same_as_current' => 'Проверьте правильность заполнения полей.',
        'telegram_format' => 'Проверьте правильность заполнения полей.',
        'phone_format' => 'Проверьте правильность заполнения полей.',
        'invalid_verification_code' => 'Проверьте правильность заполнения полей.',
    ],
    'personal_greeting' => [
        'required' => 'Проверьте правильность заполнения полей.',
        'string' => 'Проверьте правильность заполнения полей.',
        'min' => 'Проверьте правильность заполнения полей.',
        'max' => 'Проверьте правильность заполнения полей.',
        'label' => 'Проверьте правильность заполнения полей.',
        'label_confirmation_method' => 'Проверьте правильность заполнения полей.',
    ],
    'ip_restrictions' => [
        'invalid' => 'Проверьте правильность заполнения полей.',
    ],
    'registered_user' => [
        'required' => 'Проверьте правильность заполнения полей.',
        'string' => 'Проверьте правильность заполнения полей.',
        'max' => 'Проверьте правильность заполнения полей.',
        'regex' => 'Проверьте правильность заполнения полей.',
        'unique' => 'Проверьте правильность заполнения полей.',
        'in' => 'Проверьте правильность заполнения полей.',
        'lowercase' => 'Email должен быть в нижнем регистре',
        'email' => 'Введите корректный email адрес',
        'confirmed' => 'Пароли не совпадают',
        'recaptcha' => 'Пожалуйста, подтвердите, что вы не робот',
    ],
    'landing' => [
        'required' => 'Необходимо указать URL',
        'string' => 'Необходимо указать URL',
        'max' => 'URL не должен превышать :max символов',
        'regex' => 'Необходимо указать URL в формате https://example.com',
        'unique' => 'Необходимо указать URL',
    ],
    'auth' => [
        'failed' => 'Проверьте правильность заполнения полей.',
        'throttle' => 'Слишком много попыток. Повторите позже.',
        '2fa_failed' => 'Неверный код 2FA',
        '2fa_required' => 'Необходимо ввести код 2FA',
    ],
    'blog' => [
        'page.integer' => 'Проверьте правильность заполнения полей.',
        'page.min' => 'Проверьте правильность заполнения полей.',
        'page.max' => 'Проверьте правильность заполнения полей.',
        'category.regex' => 'Проверьте правильность заполнения полей.',
        'category.max' => 'Проверьте правильность заполнения полей.',
        'search.max' => 'Проверьте правильность заполнения полей.',
        'search.min' => 'Проверьте правильность заполнения полей.',
        'q.min' => 'Проверьте правильность заполнения полей.',
        'q.max' => 'Проверьте правильность заполнения полей.',
        'sort.in' => 'Проверьте правильность заполнения полей.',
        'order.in' => 'Проверьте правильность заполнения полей.',
        'limit.min' => 'Проверьте правильность заполнения полей.',
        'limit.max' => 'Проверьте правильность заполнения полей.',
        'validation_failed' => 'Проверьте правильность заполнения полей.',
        'search.combination' => 'Проверьте правильность заполнения полей.',
        'category.not_found' => 'Проверьте правильность заполнения полей.',
        'page.search' => 'Проверьте правильность заполнения полей.',
    ],
    'tariffs' => [
        'invalid_payment_page_url' => 'Неверный URL страницы оплаты',
        'payment_method_required' => 'Необходимо указать метод оплаты',
        'invalid_billing_type' => 'Проверьте правильность заполнения полей.',
        'tariff_not_found' => 'Проверьте правильность заполнения полей.',
        'payment_method_invalid' => 'Проверьте правильность заполнения полей.',
        'promo_code_min' => 'Проверьте правильность заполнения полей.',
        'promo_code_max' => 'Проверьте правильность заполнения полей.',
        'promo_code_regex' => 'Проверьте правильность заполнения полей.',
        'is_renewal_required' => 'Проверьте правильность заполнения полей.',
        'is_renewal_boolean' => 'Проверьте правильность заполнения полей.',
    ],

];

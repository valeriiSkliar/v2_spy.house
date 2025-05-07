<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Validation Language Lines
    |--------------------------------------------------------------------------
    |
    | The following language lines contain the default error messages used by
    | the validator class. Some of these rules have multiple versions such
    | as the size rules. Feel free to tweak each of these messages here.
    |
    */

    'accepted' => 'Поле :attribute має бути прийнято.',
    'accepted_if' => 'Поле :attribute має бути прийнято, коли :other дорівнює :value.',
    'active_url' => 'Поле :attribute має бути дійсною URL-адресою.',
    'after' => 'Поле :attribute має бути датою після :date.',
    'after_or_equal' => 'Поле :attribute має бути датою після або рівною :date.',
    'alpha' => 'Поле :attribute має містити лише літери.',
    'alpha_dash' => 'Поле :attribute має містити лише літери, цифри, дефіси та підкреслення.',
    'alpha_num' => 'Поле :attribute має містити лише літери та цифри.',
    'any_of' => 'Поле :attribute недійсне.',
    'array' => 'Поле :attribute має бути масивом.',
    'ascii' => 'Поле :attribute має містити лише однобайтові буквено-цифрові символи та символи.',
    'before' => 'Поле :attribute має бути датою до :date.',
    'before_or_equal' => 'Поле :attribute має бути датою до або рівною :date.',
    'between' => [
        'array' => 'Поле :attribute має містити від :min до :max елементів.',
        'file' => 'Поле :attribute має бути від :min до :max кілобайт.',
        'numeric' => 'Поле :attribute має бути між :min та :max.',
        'string' => 'Поле :attribute має бути від :min до :max символів.',
    ],
    'boolean' => 'Поле :attribute має бути істинним або хибним.',
    'can' => 'Поле :attribute містить неавторизоване значення.',
    'confirmed' => 'Підтвердження поля :attribute не співпадає.',
    'contains' => 'Поле :attribute не містить необхідного значення.',
    'current_password' => 'Невірний пароль.',
    'date' => 'Поле :attribute має бути дійсною датою.',
    'date_equals' => 'Поле :attribute має бути датою, рівною :date.',
    'date_format' => 'Поле :attribute має відповідати формату :format.',
    'decimal' => 'Поле :attribute має мати :decimal десяткових знаків.',
    'declined' => 'Поле :attribute має бути відхилено.',
    'declined_if' => 'Поле :attribute має бути відхилено, коли :other дорівнює :value.',
    'different' => 'Поля :attribute та :other мають відрізнятися.',
    'digits' => 'Поле :attribute має бути :digits цифр.',
    'digits_between' => 'Поле :attribute має бути між :min та :max цифр.',
    'dimensions' => 'Поле :attribute має недійсні розміри зображення.',
    'distinct' => 'Поле :attribute має повторюване значення.',
    'doesnt_end_with' => 'Поле :attribute не повинно закінчуватися одним із наступних: :values.',
    'doesnt_start_with' => 'Поле :attribute не повинно починатися з одного із наступних: :values.',
    'email' => 'Поле :attribute має бути дійсною адресою електронної пошти.',
    'ends_with' => 'Поле :attribute має закінчуватися одним із наступних: :values.',
    'enum' => 'Вибране значення :attribute недійсне.',
    'exists' => 'Вибране значення :attribute недійсне.',
    'extensions' => 'Поле :attribute має мати одне з наступних розширень: :values.',
    'file' => 'Поле :attribute має бути файлом.',
    'filled' => 'Поле :attribute має мати значення.',
    'gt' => [
        'array' => 'Поле :attribute має містити більше ніж :value елементів.',
        'file' => 'Поле :attribute має бути більше ніж :value кілобайт.',
        'numeric' => 'Поле :attribute має бути більше ніж :value.',
        'string' => 'Поле :attribute має бути більше ніж :value символів.',
    ],
    'gte' => [
        'array' => 'Поле :attribute має містити :value елементів або більше.',
        'file' => 'Поле :attribute має бути більше або дорівнювати :value кілобайт.',
        'numeric' => 'Поле :attribute має бути більше або дорівнювати :value.',
        'string' => 'Поле :attribute має бути більше або дорівнювати :value символів.',
    ],
    'hex_color' => 'Поле :attribute має бути дійсним шістнадцятковим кольором.',
    'image' => 'Поле :attribute має бути зображенням.',
    'in' => 'Вибране значення :attribute недійсне.',
    'in_array' => 'Поле :attribute має існувати в :other.',
    'integer' => 'Поле :attribute має бути цілим числом.',
    'ip' => 'Поле :attribute має бути дійсною IP-адресою або діапазоном IP.',
    'ipv4' => 'Поле :attribute має бути дійсною IPv4-адресою.',
    'ipv6' => 'Поле :attribute має бути дійсною IPv6-адресою.',
    'json' => 'Поле :attribute має бути дійсним JSON-рядком.',
    'list' => 'Поле :attribute має бути списком.',
    'lowercase' => 'Поле :attribute має бути в нижньому регістрі.',
    'lt' => [
        'array' => 'Поле :attribute має містити менше ніж :value елементів.',
        'file' => 'Поле :attribute має бути менше ніж :value кілобайт.',
        'numeric' => 'Поле :attribute має бути менше ніж :value.',
        'string' => 'Поле :attribute має бути менше ніж :value символів.',
    ],
    'lte' => [
        'array' => 'Поле :attribute не має містити більше ніж :value елементів.',
        'file' => 'Поле :attribute має бути менше або дорівнювати :value кілобайт.',
        'numeric' => 'Поле :attribute має бути менше або дорівнювати :value.',
        'string' => 'Поле :attribute має бути менше або дорівнювати :value символів.',
    ],
    'mac_address' => 'Поле :attribute має бути дійсною MAC-адресою.',
    'max' => [
        'array' => 'Поле :attribute не має містити більше ніж :max елементів.',
        'file' => 'Поле :attribute не має бути більше ніж :max кілобайт.',
        'numeric' => 'Поле :attribute не має бути більше ніж :max.',
        'string' => 'Поле :attribute не має бути більше ніж :max символів.',
    ],
    'max_digits' => 'Поле :attribute не має містити більше ніж :max цифр.',
    'mimes' => 'Поле :attribute має бути файлом одного з наступних типів: :values.',
    'mimetypes' => 'Поле :attribute має бути файлом одного з наступних типів: :values.',
    'min' => [
        'array' => 'Поле :attribute має містити не менше ніж :min елементів.',
        'file' => 'Поле :attribute має бути не менше ніж :min кілобайт.',
        'numeric' => 'Поле :attribute має бути не менше ніж :min.',
        'string' => 'Поле :attribute має бути не менше ніж :min символів.',
    ],
    'min_digits' => 'Поле :attribute має містити не менше ніж :min цифр.',
    'missing' => 'Поле :attribute має бути відсутнім.',
    'missing_if' => 'Поле :attribute має бути відсутнім, коли :other дорівнює :value.',
    'missing_unless' => 'Поле :attribute має бути відсутнім, якщо :other не дорівнює :value.',
    'missing_with' => 'Поле :attribute має бути відсутнім, коли :values присутній.',
    'missing_with_all' => 'Поле :attribute має бути відсутнім, коли :values присутні.',
    'multiple_of' => 'Поле :attribute має бути кратним :value.',
    'not_in' => 'Вибране значення :attribute недійсне.',
    'not_regex' => 'Формат поля :attribute недійсний.',
    'numeric' => 'Поле :attribute має бути числом.',
    'password' => [
        'letters' => 'Поле :attribute має містити хоча б одну літеру.',
        'mixed' => 'Поле :attribute має містити хоча б одну велику та одну малу літеру.',
        'numbers' => 'Поле :attribute має містити хоча б одну цифру.',
        'symbols' => 'Поле :attribute має містити хоча б один символ.',
        'uncompromised' => 'Вказане :attribute з\'явилося у витоку даних. Будь ласка, виберіть інше :attribute.',
    ],
    'present' => 'Поле :attribute має бути присутнім.',
    'present_if' => 'Поле :attribute має бути присутнім, коли :other дорівнює :value.',
    'present_unless' => 'Поле :attribute має бути присутнім, якщо :other не дорівнює :value.',
    'present_with' => 'Поле :attribute має бути присутнім, коли :values присутній.',
    'present_with_all' => 'Поле :attribute має бути присутнім, коли :values присутні.',
    'prohibited' => 'Поле :attribute заборонено.',
    'prohibited_if' => 'Поле :attribute заборонено, коли :other дорівнює :value.',
    'prohibited_if_accepted' => 'Поле :attribute заборонено, коли :other прийнято.',
    'prohibited_if_declined' => 'Поле :attribute заборонено, коли :other відхилено.',
    'prohibited_unless' => 'Поле :attribute заборонено, якщо :other не дорівнює :values.',
    'prohibits' => 'Поле :attribute забороняє присутність :other.',
    'regex' => 'Формат поля :attribute недійсний.',
    'required' => 'Поле :attribute обов\'язкове для заповнення.',
    'required_array_keys' => 'Поле :attribute має містити записи для: :values.',
    'required_if' => 'Поле :attribute обов\'язкове для заповнення, коли :other дорівнює :value.',
    'required_if_accepted' => 'Поле :attribute обов\'язкове для заповнення, коли :other прийнято.',
    'required_if_declined' => 'Поле :attribute обов\'язкове для заповнення, коли :other відхилено.',
    'required_unless' => 'Поле :attribute обов\'язкове для заповнення, якщо :other не дорівнює :values.',
    'required_with' => 'Поле :attribute обов\'язкове для заповнення, коли :values присутній.',
    'required_with_all' => 'Поле :attribute обов\'язкове для заповнення, коли :values присутні.',
    'required_without' => 'Поле :attribute обов\'язкове для заповнення, коли :values відсутній.',
    'required_without_all' => 'Поле :attribute обов\'язкове для заповнення, коли жоден з :values не присутній.',
    'same' => 'Поля :attribute та :other мають співпадати.',
    'size' => [
        'array' => 'Поле :attribute має містити :size елементів.',
        'file' => 'Поле :attribute має бути :size кілобайт.',
        'numeric' => 'Поле :attribute має бути :size.',
        'string' => 'Поле :attribute має бути :size символів.',
    ],
    'starts_with' => 'Поле :attribute має починатися з одного з наступних: :values.',
    'string' => 'Поле :attribute має бути рядком.',
    'timezone' => 'Поле :attribute має бути дійсним часовим поясом.',
    'unique' => 'Таке значення поля :attribute вже існує.',
    'uploaded' => 'Завантаження поля :attribute не вдалося.',
    'uppercase' => 'Поле :attribute має бути у верхньому регістрі.',
    'url' => 'Поле :attribute має бути дійсною URL-адресою.',
    'ulid' => 'Поле :attribute має бути дійсним ULID.',
    'uuid' => 'Поле :attribute має бути дійсним UUID.',

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
            'rule-name' => 'користувацьке-повідомлення',
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

];

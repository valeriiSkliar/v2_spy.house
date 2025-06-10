<?php

return [
    // Заголовки
    'title' => 'Финансы',
    'deposit_history_title' => 'История депозитов',

    // Методы оплаты
    'payment_methods' => [
        'title' => 'Выберите удобный способ оплаты:',
        'tether' => 'Tether',
        'capitalist' => 'Capitalist',
        'bitcoin' => 'Bitcoin',
        'ethereum' => 'Ethereum',
        'litecoin' => 'Litecoin',
        'pay2' => 'Pay2',
    ],

    // Форма депозита
    'deposit_form' => [
        'amount_label' => 'Сумма депозита',
        'submit_button' => 'Пополнить',
        'validation' => [
            'amount_required' => 'Сумма обязательна для заполнения',
            'amount_numeric' => 'Сумма должна быть числом',
            'amount_min' => 'Минимальная сумма депозита: :min',
            'payment_method_required' => 'Выберите способ оплаты',
        ],
    ],

    // Таблица истории
    'history_table' => [
        'date' => 'Дата',
        'transaction_number' => 'Номер транзакции',
        'payment_method' => 'Метод оплаты',
        'amount' => 'Сумма',
        'status' => 'Статус',
        'statuses' => [
            'pending' => 'Ожидается оплата',
            'successful' => 'Успешно',
            'rejected' => 'Отклонено',
        ],
    ],

    // Уведомления
    'messages' => [
        'deposit_success' => 'Запрос на депозит успешно отправлен',
        'deposit_error' => 'Ошибка при создании депозита',
    ],
];

<?php

return [
    // Headers
    'title' => 'Finances',
    'deposit_history_title' => 'Deposit History',
    'deposit_history_empty' => 'Deposit history is empty',

    // Payment methods
    'payment_methods' => [
        'title' => 'Choose a convenient payment method:',
        'tether' => 'Tether',
        'capitalist' => 'Capitalist',
        'bitcoin' => 'Bitcoin',
        'ethereum' => 'Ethereum',
        'litecoin' => 'Litecoin',
        'pay2' => 'Pay2',
    ],

    // Deposit form
    'deposit_form' => [
        'amount_label' => 'Deposit amount',
        'submit_button' => 'Top up',
        'validation' => [
            'amount_required' => 'Amount is required',
            'amount_numeric' => 'Amount must be a number',
            'amount_min' => 'Minimum deposit amount: :min',
            'payment_method_required' => 'Please select a payment method',
        ],
    ],

    // History table
    'history_table' => [
        'date' => 'Date',
        'transaction_number' => 'Transaction Number',
        'payment_method' => 'Payment Method',
        'amount' => 'Amount',
        'status' => 'Status',
        'statuses' => [
            'pending' => 'Payment pending',
            'successful' => 'Successful',
            'rejected' => 'Rejected',
        ],
    ],

    // Notifications
    'messages' => [
        'deposit_success' => 'Deposit request sent successfully',
        'deposit_error' => 'Error creating deposit',
        'amount' => [
            'required' => 'Deposit amount is required',
            'numeric' => 'Amount must be a number',
            'min' => 'Minimum deposit amount: :min',
            'max' => 'Maximum deposit amount: :max',
        ],
        'payment_method' => [
            'required' => 'Please select a payment method',
            'in' => 'Invalid payment method',
        ],
    ],
];

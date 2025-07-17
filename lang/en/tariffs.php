<?php

return [
    // --- Added for prices block localization ---
    'prices_title' => 'Tariffs',
    'unlimited' => 'Unlimited',
    'creative_downloads' => 'creative downloads',
    'api_requests' => 'API requests',
    'get_started' => 'Get Started',
    'prices_fallback' => [
        'downloads' => ':count creative downloads',
        'api' => ':count API requests',
    ],
    'start_price_month' => '30',
    'start_price_year' => '288',
    'premium_price_month' => '100',
    'premium_price_year' => '960',
    'start_search_request_count' => '1,000',
    'start_api_request_count' => '500',
    // --- /end prices block localization ---
    'trial' => 'Trial',
    'free' => 'Free',
    'start' => 'Starter',
    'basic' => 'Basic',
    'premium' => 'Premium',
    'enterprise' => 'Enterprise',
    'for_a_month' => 'For a month',
    'for_a_year' => 'For a year',
    'sale' => 'Discount',
    'header_title' => 'Tariffs',

    // Card component localization
    'per_month' => 'per month',
    'per_year' => 'per year',
    'discount_percent' => 'Discount :percent%',
    'search_requests' => 'Search requests',
    'priority_support' => 'Priority',
    'extend' => 'Extend',
    'select' => 'Select',

    // Current tariff component localization
    'current_tariff' => [
        'status' => [
            'active' => 'Active',
            'inactive' => 'Inactive',
            'expired' => 'Expired',
        ],
        'valid_until' => 'Valid until <span>:date</span>',
        'free_tariff' => 'Free plan',
    ],

    // Features component localization
    'features' => [
        'title' => 'What\'s included',
        'main' => [
            'unlimited_clicks' => 'Unlimited clicks',
            'bot_protection' => 'Protection from bots and all ad sources',
            'spy_protection' => 'Protection from spy services',
            'vpn_proxy_protection' => 'VPN/Proxy protection',
            'realtime_stats' => 'Real-time statistics',
            'php_integration' => 'PHP Integration',
            'premium_geo_db' => 'Premium GEO Database',
            'ipv4_support' => 'IPv4 Support',
        ],
        'additional' => [
            'unlimited_clicks_extra' => 'Unlimited clicks',
            'ipv6_support' => 'IPv6 Support',
            'isp_support' => 'ISP Support',
            'referrer_support' => 'Referrer Support',
            'device_filtering' => 'Device filtering',
            'os_filtering' => 'Operating system filtering',
            'browser_filtering' => 'Browser filtering',
            'blacklist_filtering' => 'Blacklist filtering',
            'all_traffic_sources' => 'All traffic sources support',
            'customer_support' => 'Customer support',
        ],
        'toggle' => [
            'show_all' => 'Show all',
            'hide' => 'Hide',
        ],
    ],

    // Payments table localization
    'payments_table' => [
        'title' => 'My payments',
        'columns' => [
            'date' => 'Date',
            'name' => 'Name',
            'type' => 'Type',
            'payment_method' => 'Payment method',
            'amount' => 'Amount',
            'status' => 'Status',
        ],
    ],

    'promo_code' => [
        'title' => 'Promo code',
        'description' => 'Enter promo code to get discount',
        'apply' => 'Apply',
        'cancel' => 'Cancel',
    ],

    'payment_info' => [
        'change_tariff_message' => 'Changing tariff to :tariff_name will start after the current subscription expires ',
    ],

    'previous' => 'Previous',
    'next' => 'Next',

    'payment_description' => 'Payment for :name plan',

    'errors' => [
        'payment_processing_failed' => 'Payment processing error',
        'payment_not_found' => 'Payment not found',
    ],

    'payment_methods' => [
        'title' => 'Payment methods',
    ],

    'payment_info' => [
        'renewal_on' => 'Renewal on',
        'subscription_period' => 'Subscription period',
        'price' => 'Price',
        'discount' => 'Discount',
    ],

    'payment_form' => [
        'account_activation_message' => 'Your account will be activated after payment confirmation. This usually takes',
        'payment_processing_time' => '5 minutes',
        'payment_processing_message' => 'Please be careful and transfer the exact amount specified in the instructions so that your payment is processed successfully',
        'proceed_to_payment' => 'Proceed to payment',
    ],

    'subscription_activated_modal' => [
        'title' => 'Subscription active',
        'message' => 'Your <strong>":tariff"</strong> subscription is active. <br> Valid until: <span class="icon-clock"></span> :expires',
        'ok' => 'OK',
        'change_tariff' => 'Change tariff',
    ],

    'trial_info' => [
        'expires_at' => 'Trial until: :date',
        'days_left' => 'Days left: :days',
    ],
];

<?php

return [
    'trial' => 'Trial',
    'free' => 'Free',
    'start' => 'Starter',
    'basic' => 'Basic',
    'premium' => 'Premium',
    'enterprise' => 'Enterprise',
    'for_a_month' => 'При оплате за месяц',
    'for_a_year' => 'При оплате за год',
    'sale' => 'Скидка',
    'header_title' => 'Тарифы',

    // Card component localization
    'per_month' => 'за месяц',
    'per_year' => 'за год',
    'discount_percent' => 'Скидка :percent%',
    'search_requests' => 'Поисковых запросов',
    'priority_support' => 'Приоритетная',
    'extend' => 'Продлить',
    'select' => 'Выбрать',

    // Current tariff component localization
    'current_tariff' => [
        'status' => [
            'active' => 'Активная',
            'inactive' => 'Неактивная',
            'expired' => 'Истекла',
        ],
        'valid_until' => 'Действительна до <span>:date</span>',
        'free_tariff' => 'Бесплатный тариф',
    ],

    // Features component localization
    'features' => [
        'title' => 'Что входит',
        'main' => [
            'unlimited_clicks' => 'Безлимит кликов',
            'bot_protection' => 'Защита от ботов и всех рекл. источников',
            'spy_protection' => 'Защита от спай сервисов',
            'vpn_proxy_protection' => 'Защита от VPN/Proxy',
            'realtime_stats' => 'Статистика в реальном времени',
            'php_integration' => 'PHP Интеграция',
            'premium_geo_db' => 'Премиум ГЕО Базы',
            'ipv4_support' => 'Поддержка IPv4',
        ],
        'additional' => [
            'unlimited_clicks_extra' => 'Безлимит кликов',
            'ipv6_support' => 'Поддержка IPv6',
            'isp_support' => 'Поддержка ISP',
            'referrer_support' => 'Поддержка Referrer',
            'device_filtering' => 'Фильтрация по устройствам',
            'os_filtering' => 'Фильтрация по операционным системам',
            'browser_filtering' => 'Фильтрация по браузерам',
            'blacklist_filtering' => 'Фильтрация по черным спискам',
            'all_traffic_sources' => 'Поддержка всех источников трафика',
            'customer_support' => 'Служба поддержки',
        ],
        'toggle' => [
            'show_all' => 'Показать все',
            'hide' => 'Скрыть',
        ],
    ],

    // Payments table localization
    'payments_table' => [
        'title' => 'Мои платежи',
        'columns' => [
            'date' => 'Дата',
            'name' => 'Название',
            'type' => 'Тип',
            'payment_method' => 'Метод оплаты',
            'amount' => 'Сумма',
            'status' => 'Статус',
        ],
    ],

    'promo_code' => [
        'title' => 'Промокод',
        'description' => 'Введите промокод, чтобы получить скидку',
        'apply' => 'Применить',
        'cancel' => 'Отменить',
    ],

    'payment_info' => [
        'change_tariff_message' => 'Смена тарифа на :tariff_name начнется после окончания текущей подписки ',
    ],

    'previous' => 'Предыдущая',
    'next' => 'Следующая',

    'payment_description' => 'Оплата тарифа :name',

    'errors' => [
        'payment_processing_failed' => 'Ошибка при обработке платежа',
        'payment_not_found' => 'Платеж не найден',
    ],

    'payment_methods' => [
        'title' => 'Способы оплаты',
    ],

    'payment_info' => [
        'renewal_on' => 'Продление на',
        'subscription_period' => 'Срок действия',
        'price' => 'Стоимость',
        'discount' => 'Скидка',
    ],

    'payment_form' => [
        'account_activation_message' => 'Ваш аккаунт будет активирован после подтверждения платежа. Это обычно занимает',
        'payment_processing_time' => '5 минут',
        'payment_processing_message' => 'Пожалуйста, будьте внимательны и переводите точную сумму, указанную в инструкциях, чтобы ваш платеж был успешно обработан',
        'proceed_to_payment' => 'Перейти к оплате',
    ],

    'subscription_activated_modal' => [
        'title' => 'Подписка активна',
        'message' => 'Ваша подписка <strong>":tariff"</strong> активна. <br> Срок действия: <span class="icon-clock"></span> :expires',
        'ok' => 'OK',
        'change_tariff' => 'Изменить тариф',
    ],

    'trial_info' => [
        'expires_at' => 'Триал до: :date',
        'days_left' => 'Осталось дней: :days',
    ],
];

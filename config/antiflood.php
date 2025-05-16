<?php

return [
    'landings' => [
        'store' => [
            'rate' => env('ANTIFLOOD_LANDINGS_STORE_RATE', 5),
            'time' => env('ANTIFLOOD_LANDINGS_STORE_TIME', 60),
        ],
    ],
];

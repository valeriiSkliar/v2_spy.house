<?php

return [
    'api_key' => env('RESEND_API_KEY'),
    'audience_id' => env('RESEND_AUDIENCE_ID'),
    'from' => env('RESEND_FROM', 'SPY.HOUSE <noreply@spy.house>'),
];

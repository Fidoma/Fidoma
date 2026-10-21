<?php

return [
    'gateway_url' => env('TERMINAL_GATEWAY_URL', 'http://host.docker.internal:5000'),
    'gateway_token' => env('TERMINAL_GATEWAY_TOKEN'),
    'timeout' => env('TERMINAL_GATEWAY_TIMEOUT', 130),
    'status_timeout' => env('TERMINAL_GATEWAY_STATUS_TIMEOUT', 10),
    'payment_method_id' => env('TERMINAL_PAYMENT_METHOD_ID'),
];

<?php

return [
    'mode' => env('TERMINAL_MODE', 'simulator'),
    'gateway_url' => env('TERMINAL_GATEWAY_URL'),
    'gateway_token' => env('TERMINAL_GATEWAY_TOKEN'),
    'timeout' => env('TERMINAL_GATEWAY_TIMEOUT', 10),
];

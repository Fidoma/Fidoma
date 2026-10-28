<?php

return [
    'engine' => env('FIDOMA_AI_ENGINE', 'none'),
    'api_key' => env('FIDOMA_AI_API_KEY'),
    'model' => env('FIDOMA_AI_MODEL'),
    'endpoint' => env('FIDOMA_AI_ENDPOINT'),
    'timeout' => env('FIDOMA_AI_TIMEOUT', 30),
];

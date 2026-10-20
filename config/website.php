<?php

return [
    'links' => [
        'repository' => env('FIDOMA_REPOSITORY_URL', 'https://github.com/Fidoma/Fidoma'),
        'documentation' => env('FIDOMA_DOCUMENTATION_URL', 'https://github.com/Fidoma/Fidoma#readme'),
        'contact' => env('FIDOMA_CONTACT_URL'),
        'support' => env('FIDOMA_SUPPORT_URL'),
        'cloud' => env('FIDOMA_CLOUD_URL'),
        'donation' => env('FIDOMA_DONATION_URL'),
        'imprint' => env('FIDOMA_IMPRINT_URL'),
        'privacy' => env('FIDOMA_PRIVACY_URL'),
    ],

    'plans' => [
        'community' => [
            'name' => 'Fidoma Community',
            'price' => '0 €',
            'suffix' => 'dauerhaft kostenlos',
        ],
        'cloud' => [
            'name' => 'Fidoma Cloud',
            'price' => '19 €',
            'suffix' => 'pro Monat',
        ],
        'support' => [
            'name' => 'Fidoma Support',
            'price' => 'ab 11 €',
            'suffix' => 'pro Monat',
        ],
    ],
];

<?php

return [
    'links' => [
        'repository' => env('FIDOMA_REPOSITORY_URL', 'https://github.com/Fidoma/Fidoma'),
        'documentation' => env('FIDOMA_DOCUMENTATION_URL', 'https://github.com/Fidoma/Fidoma#readme'),
        'donation' => env('FIDOMA_DONATION_URL', 'https://www.paypal.com/ncp/payment/4J8LA2Z8TNGNQ'),
        'imprint' => env('FIDOMA_IMPRINT_URL'),
        'privacy' => env('FIDOMA_PRIVACY_URL'),
    ],

    'contact_email' => env('FIDOMA_CONTACT_EMAIL', 'info@fidoma.de'),
    'contact_from_email' => env('FIDOMA_CONTACT_FROM_EMAIL', 'info@fidoma.de'),

    'paypal' => [
        'donation' => env('FIDOMA_PAYPAL_DONATION_URL', 'https://www.paypal.com/ncp/payment/4J8LA2Z8TNGNQ'),
    ],

    'plans' => [
        'community' => [
            'name' => 'Fidoma Community',
            'price' => '0 €',
            'suffix' => 'dauerhaft kostenlos',
        ],
        'cloud' => [
            'name' => 'Fidoma Cloud',
            'price' => 'Auf Anfrage',
            'suffix' => 'individuelles Angebot',
        ],
        'support' => [
            'name' => 'Fidoma Support',
            'price' => 'Auf Anfrage',
            'suffix' => 'individuelles Angebot',
        ],
    ],
];

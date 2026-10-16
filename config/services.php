<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Resend, Postmark, AWS, and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'paperless' => [
        'url' => env('PAPERLESS_URL'),
        'token' => env('PAPERLESS_TOKEN'),
        'storage_path_id' => env('PAPERLESS_STORAGE_PATH_ID'),
        'document_type_id' => env('PAPERLESS_DOCUMENT_TYPE_ID'),
        'amount_field_id' => env('PAPERLESS_AMOUNT_FIELD_ID'),
        'invoice_number_field_id' => env('PAPERLESS_INVOICE_NUMBER_FIELD_ID'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

];

<?php

return [
    'private_dir' => env('ROUNDCUBE_PRIVATE_DIR'),

    'imap' => [
        'host' => env('IMAP_HOST'),
        'port' => env('IMAP_PORT', 993),
        'scheme' => env('IMAP_SCHEME', 'ssl'),
        'username' => env('IMAP_USERNAME'),
        'password' => env('IMAP_PASSWORD'),
        'mailbox' => env('IMAP_MAILBOX', 'INBOX'),
        'verify_certificates' => env('IMAP_VERIFY_CERTIFICATES', true),
    ],

    'smtp_verify_certificates' => env('MAIL_VERIFY_CERTIFICATES', true),
];

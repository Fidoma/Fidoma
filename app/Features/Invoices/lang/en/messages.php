<?php

return [
    'feature' => 'Invoices',
    'singular' => 'Invoice',
    'billing_types' => [
        'standard' => 'Invoice',
        'progress' => 'Progress invoice',
        'final' => 'Final invoice',
    ],
    'assignment' => ['heading' => 'Billing and project'],
    'fields' => [
        'billing_type' => 'Billing type',
        'project' => 'Project / construction project / order',
    ],
];

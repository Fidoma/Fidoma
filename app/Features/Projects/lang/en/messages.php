<?php

return [
    'feature' => 'Projects',
    'singular' => 'Project',
    'statuses' => [
        'draft' => 'Draft',
        'active' => 'Active',
        'paused' => 'Paused',
        'completed' => 'Completed',
        'cancelled' => 'Cancelled',
    ],
    'fields' => [
        'number' => 'Project number',
        'name' => 'Name',
        'status' => 'Status',
        'client' => 'Client',
        'description' => 'Description',
        'start_date' => 'Start date',
        'end_date' => 'End date',
        'contract_amount' => 'Net contract amount',
        'quote' => 'Quote',
        'contract' => 'Contract',
        'invoices' => 'Invoices',
    ],
    'form' => [
        'project' => 'Project',
        'framework' => 'Framework',
        'number_placeholder' => 'Assigned automatically',
    ],
    'actions' => ['create' => 'Project'],
];

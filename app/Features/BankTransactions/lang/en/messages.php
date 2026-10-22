<?php

return [
    'feature' => 'Bank transactions',
    'singular' => 'Bank transaction',
    'navigation_group' => 'Finance',
    'actions' => [
        'view' => 'View details',
        'open_assignment' => 'Open assignments',
    ],
    'detail' => [
        'heading' => 'Bank transaction',
        'overview' => 'Overview',
        'payment_information' => 'Payment information',
        'linked_records' => 'Linked business records',
        'technical_data' => 'Technical bank data',
        'no_linked_records' => 'This transaction has not yet been assigned to a business record.',
        'document' => 'Document',
        'invoice' => 'Invoice',
        'payment' => 'Payment',
        'assignment_amount' => 'Assigned amount',
        'remaining_amount' => 'Remaining amount',
        'paperless_document' => 'Paperless document #:id',
        'payment_number' => 'Payment #:id',
        'invoice_number' => 'Invoice #:id',
        'invoice_open_amount' => ':amount :currency outstanding',
    ],
    'fields' => [
        'amount' => 'Amount', 'counterparty' => 'Counterparty', 'booking_date' => 'Booking date',
        'assignment_status' => 'Assignment status', 'purpose' => 'Purpose',
        'counterparty_iban' => 'Counterparty IBAN', 'bank_account' => 'Bank account',
        'value_date' => 'Value date', 'booking_text' => 'Booking text', 'type' => 'Type',
        'business_record' => 'Business record', 'details' => 'Details', 'bank_reference' => 'Bank reference',
        'end_to_end_id' => 'End-to-end ID', 'bank_transaction_id' => 'Bank transaction ID',
        'internal_reference' => 'Internal transaction reference', 'imported_at' => 'Imported/recorded at',
        'validation' => 'Validation',
    ],
    'status' => ['validated' => 'Validated', 'not_validated' => 'Not validated'],
];

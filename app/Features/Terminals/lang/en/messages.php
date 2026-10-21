<?php

return [
    'feature' => 'Terminals', 'subtitle' => 'Select open invoices and collect payment directly.', 'navigation_group' => 'Finance',
    'payment_status' => ['ready' => 'Ready', 'unavailable' => 'Not ready', 'sending' => 'Sending payment request', 'awaiting_card' => 'Waiting for card payment', 'processing' => 'Processing payment', 'successful' => 'Payment successful', 'demo_successful' => 'Demo successful – not posted', 'cancelled' => 'Payment cancelled', 'failed' => 'Payment failed', 'unknown' => 'Payment status unknown – review required'],
    'invoices' => ['heading' => 'Open invoices', 'description' => 'Select an invoice for card payment.', 'search' => 'Search invoice or customer', 'all' => 'Open & partially paid', 'open' => 'Open', 'partial' => 'Partially paid', 'newest' => 'Newest first', 'oldest' => 'Oldest first', 'amount_desc' => 'Highest amount', 'amount_asc' => 'Lowest amount', 'empty' => 'No open invoices', 'empty_description' => 'No payable invoices match the current selection.'],
    'payment' => ['invoice_information' => 'Selected invoice', 'invoice' => 'Invoice', 'customer' => 'Customer', 'total' => 'Invoice total', 'paid' => 'Already paid', 'balance' => 'Still to pay', 'terminal_and_amount' => 'Card payment', 'amount' => 'Payment amount', 'start' => 'Start payment', 'starting' => 'Starting payment …', 'status' => 'Payment status', 'check_status' => 'Check status after reconciliation', 'check_connection' => 'Check connection', 'connection_status' => 'ZVTAPI status: :status', 'simulator_ready' => 'ZVTAPI simulator reachable', 'select_invoice' => 'Select an invoice', 'select_invoice_description' => 'Select an open invoice on the left to prepare its payment.', 'terminal_ready' => 'Terminal ready for the next payment.'],
    'history' => [
        'heading' => 'Payment history',
        'singular' => 'Terminal payment attempt',
        'description' => 'The latest terminal payment attempts across all invoices.',
        'search' => 'Search invoice, customer or reference',
        'empty' => 'No terminal payment attempts yet.',
        'empty_description' => 'Started terminal payments automatically appear in this table.',
        'columns' => ['started_at' => 'Date and time', 'invoice' => 'Invoice', 'customer' => 'Customer', 'amount' => 'Amount', 'status' => 'Status', 'reference' => 'Transaction reference', 'message' => 'Status message'],
        'filters' => ['status' => 'Payment status', 'from' => 'From', 'until' => 'Until'],
    ],
    'demo' => ['not_booked' => 'Demo successful. No payment was posted.'],
    'errors' => ['gateway_not_configured' => 'The ZVTAPI connection is not fully configured.', 'gateway_unreachable' => 'ZVTAPI is currently unreachable.', 'terminal_not_ready' => 'ZVTAPI is not ready for payments (:status).', 'blocked_by_attempt' => 'A payment attempt from :time is still active or unresolved. Resolve its status first.', 'duplicate_gateway_reference' => 'The terminal transaction reference was already used. The payment attempt requires review.', 'gateway_rejected' => 'ZVTAPI rejected the payment request.', 'connection_lost' => 'The connection was interrupted. Do not start another payment; check the terminal journal first.', 'already_active' => 'A payment is already active or an attempt requires review.', 'invoice_not_payable' => 'The invoice is no longer payable.', 'invalid_amount' => 'The amount must be greater than zero and may not exceed the balance.', 'payment_method_missing' => 'The payment was confirmed but cannot be posted without a configured payment method.', 'balance_changed' => 'The invoice balance changed. The confirmed terminal payment must be reconciled manually.'],
    'notifications' => ['booked' => 'The card payment was confirmed and posted.'],
    'payment_note' => 'Terminal payment :id · Reference :reference',
];

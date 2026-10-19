<?php

return [
    'period' => ['year' => 'Year', 'all' => 'All', 'range' => 'Period', 'from' => 'From', 'until' => 'To', 'search' => 'Search', 'last_month' => 'Last month', 'this_quarter' => 'This quarter', 'this_year' => 'This year', 'this_month' => 'This month'],
    'validation' => ['end_after_start' => 'The end date must be after the start date.'],
    'kpis' => [
        'revenue' => 'Revenue', 'no_comparison' => 'No comparison value', 'more' => 'more', 'less' => 'less',
        'receivables' => 'Outstanding receivables', 'open_invoices' => ':count open invoice as of the reporting date|:count open invoices as of the reporting date',
        'payments' => 'Incoming payments', 'booked_income' => 'Booked bank receipts · :period',
        'overdue' => 'Overdue invoices', 'overdue_invoices' => ':count invoice overdue|:count invoices overdue',
    ],
    'charts' => [
        'revenue' => 'Revenue trend', 'revenue_description' => 'Invoice revenue compared with the previous year',
        'current' => 'Current period', 'previous' => 'Previous year', 'cashflow' => 'Income and expenses',
        'cashflow_description' => 'Booked bank transactions', 'income' => 'Income', 'expenses' => 'Expenses',
    ],
    'operations' => [
        'documents' => 'Documents and receipts', 'unassigned_transactions' => 'Unassigned bank transactions',
        'unprocessed_documents' => 'Unprocessed documents', 'assignment_rate' => 'Document assignment rate',
        'quotes' => 'Quotes', 'open_quote_value' => 'Open quote value', 'open_quotes' => 'Open quotes',
        'accepted' => 'Accepted', 'acceptance_rate' => 'Acceptance rate',
    ],
    'tasks' => [
        'heading' => 'Tasks and notices', 'description' => 'Open items that require your attention',
        'overdue_invoices' => 'Overdue customer invoices', 'amount_open' => '€:amount open',
        'unassigned_transactions' => 'Bank transactions without assignment', 'unassigned_description' => 'Not yet assigned to a document or incoming payment',
        'documents' => 'Documents to process', 'paperless_unavailable' => 'Paperless is currently unavailable',
        'documents_unassigned' => 'Not fully assigned yet', 'open_quotes' => 'Open quotes',
        'quote_value' => '€:amount quote value',
    ],
];

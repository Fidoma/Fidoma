<?php

return [
    'feature' => 'Banktransaktionen',
    'singular' => 'Banktransaktion',
    'navigation_group' => 'Finanzen',
    'actions' => [
        'view' => 'Details anzeigen',
        'open_assignment' => 'In Zuordnung öffnen',
    ],
    'detail' => [
        'heading' => 'Banktransaktion',
        'overview' => 'Übersicht',
        'payment_information' => 'Zahlungsinformationen',
        'linked_records' => 'Verknüpfte Geschäftsvorgänge',
        'technical_data' => 'Technische Bankdaten',
        'no_linked_records' => 'Diese Transaktion ist noch keinem Geschäftsvorgang zugeordnet.',
        'document' => 'Dokument',
        'invoice' => 'Rechnung',
        'payment' => 'Zahlung',
        'assignment_amount' => 'Zuordnungsbetrag',
        'remaining_amount' => 'Offener Restbetrag',
        'paperless_document' => 'Paperless-Dokument #:id',
        'payment_number' => 'Zahlung #:id',
        'invoice_number' => 'Rechnung #:id',
        'invoice_open_amount' => ':amount :currency offen',
    ],
    'fields' => [
        'amount' => 'Betrag', 'counterparty' => 'Gegenpartei', 'booking_date' => 'Buchungsdatum',
        'assignment_status' => 'Zuordnungsstatus', 'purpose' => 'Verwendungszweck',
        'counterparty_iban' => 'IBAN der Gegenpartei', 'bank_account' => 'Bankkonto',
        'value_date' => 'Wertstellung', 'booking_text' => 'Buchungstext', 'type' => 'Typ',
        'business_record' => 'Vorgang', 'details' => 'Details', 'bank_reference' => 'Bankreferenz',
        'end_to_end_id' => 'End-to-End-ID', 'bank_transaction_id' => 'Bank-Transaktions-ID',
        'internal_reference' => 'Interne Transaktionsreferenz', 'imported_at' => 'Importiert/erfasst am',
        'validation' => 'Validierung',
    ],
    'status' => ['validated' => 'Validiert', 'not_validated' => 'Nicht validiert'],
];

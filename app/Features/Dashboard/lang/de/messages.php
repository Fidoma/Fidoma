<?php

return [
    'period' => ['year' => 'Jahr', 'all' => 'Gesamt', 'range' => 'Zeitraum', 'from' => 'Von', 'until' => 'Bis', 'search' => 'Suchen', 'last_month' => 'Letzter Monat', 'this_quarter' => 'Dieses Quartal', 'this_year' => 'Dieses Jahr', 'this_month' => 'Dieser Monat'],
    'validation' => ['end_after_start' => 'Das Enddatum muss nach dem Startdatum liegen.'],
    'kpis' => [
        'revenue' => 'Umsatz', 'no_comparison' => 'Kein Vergleichswert', 'more' => 'mehr', 'less' => 'weniger',
        'receivables' => 'Offene Forderungen', 'open_invoices' => ':count offene Rechnung zum Stichtag|:count offene Rechnungen zum Stichtag',
        'payments' => 'Zahlungseingänge', 'booked_income' => 'Gebuchte Bankeingänge · :period',
        'overdue' => 'Überfällige Rechnungen', 'overdue_invoices' => ':count Rechnung überfällig|:count Rechnungen überfällig',
    ],
    'charts' => [
        'revenue' => 'Umsatzentwicklung', 'revenue_description' => 'Rechnungsumsatz im Vergleich zum Vorjahr',
        'current' => 'Aktueller Zeitraum', 'previous' => 'Vorjahr', 'cashflow' => 'Einnahmen und Ausgaben',
        'cashflow_description' => 'Gebuchte Banktransaktionen', 'income' => 'Einnahmen', 'expenses' => 'Ausgaben',
    ],
    'operations' => [
        'documents' => 'Dokumente und Belege', 'unassigned_transactions' => 'Nicht zugeordnete Banktransaktionen',
        'unprocessed_documents' => 'Unverarbeitete Dokumente', 'assignment_rate' => 'Belegzuordnungsquote',
        'quotes' => 'Angebote', 'open_quote_value' => 'Offener Angebotswert', 'open_quotes' => 'Offene Angebote',
        'accepted' => 'Angenommen', 'acceptance_rate' => 'Annahmequote',
    ],
    'tasks' => [
        'heading' => 'Aufgaben und Hinweise', 'description' => 'Offene Vorgänge, die Ihre Aufmerksamkeit benötigen',
        'overdue_invoices' => 'Überfällige Kundenrechnungen', 'amount_open' => ':amount € offen',
        'unassigned_transactions' => 'Banktransaktionen ohne Zuordnung', 'unassigned_description' => 'Noch keinem Beleg oder Zahlungseingang zugeordnet',
        'documents' => 'Dokumente zu verarbeiten', 'paperless_unavailable' => 'Paperless derzeit nicht erreichbar',
        'documents_unassigned' => 'Noch nicht vollständig zugeordnet', 'open_quotes' => 'Offene Angebote',
        'quote_value' => ':amount € Angebotswert',
    ],
];

<?php

return [
    'feature' => 'Projekte',
    'singular' => 'Projekt',
    'statuses' => [
        'draft' => 'Entwurf',
        'active' => 'Aktiv',
        'paused' => 'Pausiert',
        'completed' => 'Abgeschlossen',
        'cancelled' => 'Storniert',
    ],
    'fields' => [
        'number' => 'Projektnummer',
        'name' => 'Bezeichnung',
        'status' => 'Status',
        'client' => 'Kunde',
        'description' => 'Beschreibung',
        'start_date' => 'Beginn',
        'end_date' => 'Ende',
        'contract_amount' => 'Projektwert netto',
        'quote' => 'Angebot',
        'contract' => 'Vertrag',
        'invoices' => 'Rechnungen',
    ],
    'form' => [
        'project' => 'Projekt',
        'framework' => 'Rahmendaten',
        'number_placeholder' => 'Wird automatisch vergeben',
    ],
    'actions' => ['create' => 'Projekt'],
];

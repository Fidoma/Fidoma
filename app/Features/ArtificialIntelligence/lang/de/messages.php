<?php

return [
    'navigation_group' => 'Externe Dienste',
    'navigation_label' => 'KI-Vorlagen',
    'template' => 'Vorlage',
    'templates' => 'Vorlagen',
    'create_template' => 'Vorlage',
    'form' => [
        'section' => 'KI-Vorlage',
    ],
    'fields' => [
        'title' => 'Titel',
        'area' => 'Bereich',
        'action' => 'Aktion',
        'prompt' => 'Prompt',
        'active' => 'Aktiv',
        'sort_order' => 'Sortierreihenfolge',
        'arrival_date' => 'Anreise',
        'departure_date' => 'Abreise',
        'product' => 'Produkt',
    ],
    'actions' => [
        'create_client' => 'Kunde erstellen',
        'create_reservation' => 'Reservierung erstellen',
    ],
    'simulation' => [
        'badge' => 'Simulation – es wird keine echte KI verwendet.',
        'create_client_heading' => 'Kunde aus E-Mail erstellen',
        'create_reservation_heading' => 'Reservierung aus E-Mail erstellen',
        'client_created' => 'Kunde wurde erstellt.',
        'reservation_created' => 'Reservierung wurde erstellt.',
        'duplicate_client' => 'Ein Kunde mit dieser E-Mail-Adresse ist bereits vorhanden.',
    ],
    'areas' => [
        'email_message' => 'E-Mail-Nachricht',
    ],
    'roundcube' => [
        'actions' => 'KI-Aktionen',
        'selection_prepared' => 'KI-Aktion ausgewählt',
        'selection_prepared_body' => '„:template“ ist für die ausgewählte Nachricht vorbereitet.',
    ],
];

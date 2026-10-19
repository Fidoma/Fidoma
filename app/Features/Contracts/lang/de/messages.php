<?php

return [
    'feature' => 'Verträge',
    'singular' => 'Vertrag',
    'template' => [
        'feature' => 'Vertragsvorlagen',
        'singular' => 'Vertragsvorlage',
        'fields' => [
            'title' => 'Vorlagentitel',
            'status' => 'Status',
            'version' => 'Version',
            'modified' => 'Geändert',
            'content' => 'Vertragstext',
        ],
        'statuses' => [
            'draft' => 'Entwurf',
            'active' => 'Aktiv',
        ],
        'delete' => [
            'in_use' => 'Verwendete Vorlagen können nicht gelöscht werden.',
        ],
        'placeholders' => [
            'heading' => 'Platzhalter',
            'add' => 'Platzhalter hinzufügen',
            'modal_heading' => 'Eigenen Platzhalter hinzufügen',
            'save' => 'Speichern',
            'label' => 'Bezeichnung',
            'type' => 'Typ',
            'types' => [
                'text' => 'Text',
                'date' => 'Datum',
            ],
            'helper' => 'Aus der Bezeichnung wird automatisch ein Platzhalter erzeugt.',
            'duplicate' => 'Dieser Platzhalter ist bereits vorhanden.',
            'copy' => 'Platzhalter kopieren',
            'delete' => 'Platzhalter löschen',
            'cannot_delete' => 'Der Platzhalter wird bereits in einem Vertrag verwendet und kann nicht gelöscht werden.',
        ],
    ],
];

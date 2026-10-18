<?php

return [
    'fields' => [
        'account_iban' => 'Konto-IBAN',
        'booking_date' => 'Buchungsdatum',
        'value_date' => 'Wertstellungsdatum',
        'amount' => 'Betrag',
        'currency' => 'Währung',
        'purpose' => 'Verwendungszweck',
        'counterparty_name' => 'Gegenpartei',
        'counterparty_iban' => 'IBAN der Gegenpartei',
        'booking_text' => 'Buchungstext',
        'transaction_reference' => 'Transaktionsreferenz',
        'bank_transaction_id' => 'Banktransaktions-ID',
        'bank_reference' => 'Bankreferenz',
        'end_to_end_id' => 'End-to-End-ID',
        'pending' => 'Vorgemerkt',
        'validated' => 'Validiert',
    ],
    'errors' => [
        'empty' => 'Die CSV-Datei ist leer.',
        'unreadable' => 'Die CSV-Datei konnte nicht gelesen werden.',
        'headers' => 'Die Kopfzeile enthält leere oder doppelte Spaltennamen.',
        'columns' => 'Zeile :line besitzt nicht dieselbe Spaltenanzahl wie die Kopfzeile.',
        'mapping' => 'Die Spaltenzuordnung ist ungültig. Bitte erstellen Sie die Vorschau erneut.',
        'duplicate' => 'Zeile :line wurde bereits importiert. Der gesamte Import wurde zurückgerollt.',
        'no_rows' => 'Die CSV-Datei enthält keine importierbaren Banktransaktionen.',
        'invalid' => 'Zeile :line enthält einen ungültigen Wert für „:field“. Der gesamte Import wurde zurückgerollt.',
        'sign_configuration' => 'Die Werte für Einnahme und Ausgabe müssen ausgefüllt und unterschiedlich sein.',
        'sign_value' => 'Der Soll/Haben-Wert „:value“ in Zeile :line ist unbekannt.',
    ],
];

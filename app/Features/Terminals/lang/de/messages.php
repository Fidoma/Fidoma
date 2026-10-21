<?php

return [
    'feature' => 'Terminals', 'subtitle' => 'Offene Rechnungen auswählen und direkt kassieren.', 'navigation_group' => 'Finanzen',
    'payment_status' => ['ready' => 'Bereit', 'unavailable' => 'Nicht bereit', 'sending' => 'Zahlungsanforderung wird gesendet', 'awaiting_card' => 'Warte auf Kartenzahlung', 'processing' => 'Zahlung wird verarbeitet', 'successful' => 'Zahlung erfolgreich', 'demo_successful' => 'Demo erfolgreich – nicht verbucht', 'cancelled' => 'Zahlung abgebrochen', 'failed' => 'Zahlung fehlgeschlagen', 'unknown' => 'Zahlungsstatus unbekannt – Prüfung erforderlich'],
    'invoices' => ['heading' => 'Offene Rechnungen', 'description' => 'Rechnung für die Kartenzahlung auswählen.', 'search' => 'Rechnung oder Kunde suchen', 'all' => 'Offen & teilbezahlt', 'open' => 'Offen', 'partial' => 'Teilbezahlt', 'newest' => 'Neueste zuerst', 'oldest' => 'Älteste zuerst', 'amount_desc' => 'Höchster Betrag', 'amount_asc' => 'Niedrigster Betrag', 'empty' => 'Keine offenen Rechnungen', 'empty_description' => 'Für diese Auswahl wurden keine zahlbaren Rechnungen gefunden.'],
    'payment' => ['invoice_information' => 'Ausgewählte Rechnung', 'invoice' => 'Rechnung', 'customer' => 'Kunde', 'total' => 'Rechnungsbetrag', 'paid' => 'Bereits bezahlt', 'balance' => 'Noch zu bezahlen', 'terminal_and_amount' => 'Kartenzahlung', 'amount' => 'Zahlungsbetrag', 'start' => 'Zahlung starten', 'starting' => 'Zahlung wird gestartet …', 'status' => 'Zahlungsstatus', 'check_status' => 'Status nach Klärung prüfen', 'check_connection' => 'Verbindung prüfen', 'connection_status' => 'ZVTAPI-Status: :status', 'simulator_ready' => 'ZVTAPI-Simulator erreichbar', 'select_invoice' => 'Rechnung auswählen', 'select_invoice_description' => 'Wähle links eine offene Rechnung, um die Zahlung vorzubereiten.', 'terminal_ready' => 'Terminal bereit für die nächste Zahlung.'],
    'history' => [
        'heading' => 'Zahlungshistorie',
        'singular' => 'Terminal-Zahlungsversuch',
        'description' => 'Die letzten Terminal-Zahlungsversuche für alle Rechnungen.',
        'search' => 'Rechnung, Kunde oder Referenz durchsuchen',
        'empty' => 'Noch keine Terminal-Zahlungsversuche vorhanden.',
        'empty_description' => 'Gestartete Terminalzahlungen erscheinen automatisch in dieser Tabelle.',
        'columns' => ['started_at' => 'Datum und Uhrzeit', 'invoice' => 'Rechnung', 'customer' => 'Kunde', 'amount' => 'Betrag', 'status' => 'Status', 'reference' => 'Transaktionsreferenz', 'message' => 'Statusmeldung'],
        'filters' => ['status' => 'Zahlungsstatus', 'from' => 'Von', 'until' => 'Bis'],
    ],
    'demo' => ['not_booked' => 'Demo erfolgreich. Es wurde keine Zahlung verbucht.'],
    'errors' => ['gateway_not_configured' => 'Die ZVTAPI-Verbindung ist nicht vollständig konfiguriert.', 'gateway_unreachable' => 'Die ZVTAPI ist derzeit nicht erreichbar.', 'terminal_not_ready' => 'Die ZVTAPI ist nicht zahlungsbereit (:status).', 'blocked_by_attempt' => 'Ein Zahlungsversuch vom :time ist noch aktiv oder ungeklärt. Bitte zuerst dessen Status klären.', 'duplicate_gateway_reference' => 'Die Terminal-Transaktionsreferenz wurde bereits verwendet. Der Zahlungsversuch muss geprüft werden.', 'gateway_rejected' => 'ZVTAPI hat die Zahlungsanforderung abgelehnt.', 'connection_lost' => 'Die Verbindung wurde unterbrochen. Keine neue Zahlung starten; zuerst das Terminaljournal prüfen.', 'already_active' => 'Es läuft bereits eine Zahlung oder ein Zahlungsversuch erfordert eine Prüfung.', 'invoice_not_payable' => 'Die Rechnung ist nicht mehr zahlbar.', 'invalid_amount' => 'Der Betrag muss größer als null sein und darf den offenen Betrag nicht überschreiten.', 'payment_method_missing' => 'Die Zahlung wurde bestätigt, kann aber ohne konfigurierte Zahlungsart noch nicht verbucht werden.', 'balance_changed' => 'Der offene Rechnungsbetrag hat sich geändert. Die bestätigte Terminalzahlung muss manuell abgeglichen werden.'],
    'notifications' => ['booked' => 'Die Kartenzahlung wurde bestätigt und verbucht.'],
    'payment_note' => 'Terminal-Zahlung :id · Referenz :reference',
];

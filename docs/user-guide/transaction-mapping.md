# Transaktionszuordnung

Die Transaktionszuordnung verbindet importierte Bankbewegungen mit Rechnungen, vorhandenen Zahlungen oder Paperless-Dokumenten. Jede Banktransaktion kann nur einmal zugeordnet werden.

## Import vorbereiten

Unter **Datenimport** eine CSV-Datei bis 10 MB hochladen. Fidoma erkennt Trennzeichen und schlägt Spalten für IBAN, Buchungsdatum, Betrag und weitere Felder vor. Pflichtfelder sind IBAN, Buchungsdatum und Betrag. Eine optionale Soll/Haben-Spalte kann das Vorzeichen bestimmen.

Vor dem Import lassen sich Zeilen in der Vorschau ausschließen. Eine Duplikaterkennung verhindert den erneuten Import identischer Bewegungen.

## Manuelle Verarbeitung

1. Unzugeordnete positive Banktransaktion öffnen.
2. Passende Rechnung oder bestehende Zahlung auswählen.
3. Betrag, Verwendungszweck, Gegenkonto und Buchungsdatum prüfen.
4. Zuordnung bestätigen; die Transaktion wird validiert.

Bei einer Rechnungszuordnung erzeugt Fidoma eine Zahlung in Höhe der Transaktion und berechnet die Rechnung neu. Bei der Zuordnung zu einer bestehenden Zahlung bleibt diese Zahlung eigenständig erhalten.

## Schutz vor Fehlern

- Entwürfe, archivierte Rechnungen und nicht positive Transaktionen werden abgewiesen.
- Bereits zugeordnete oder parallel geänderte Transaktionen können nicht erneut gespeichert werden.
- Für Rechnungszuordnungen muss eine Zahlungsart vorhanden sein.
- Zuordnungen können wieder gelöst werden.

::: warning Keine automatische Regel-Engine
Der aktuelle Quellcode enthält Kandidatensuchen und manuelle Mehrfachauswahl, aber keine frei konfigurierbaren Regeln für eine vollautomatische Rechnungszuordnung.
:::

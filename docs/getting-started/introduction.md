# Einführung

Fidoma bündelt kaufmännische Abläufe in einer selbst gehosteten Webanwendung. Die Oberfläche basiert auf Filament; interaktive Dialoge und Zuordnungen werden mit Livewire umgesetzt.

## Für wen ist Fidoma gedacht?

- Anwender verwalten Stammdaten, Angebote, Rechnungen und Zahlungseingänge.
- Administratoren aktivieren Module und konfigurieren externe Dienste über Umgebungsvariablen.
- Entwickler erweitern klar abgegrenzte Features unter `app/Features`.

## Aktueller Umfang

Implementiert sind unter anderem Kunden, Projekte, Angebote, Rechnungen inklusive PDF und XRechnung, Zahlungen, CSV-Bankimport, Transaktionszuordnungen, ZVT-Gateway-Zahlungen, Verträge, Reservierungen, Berichte, Paperless-ngx-Anbindung und Webmail.

Einzelne Bereiche sind optional und erscheinen nur bei aktivem Modul beziehungsweise gültiger Konfiguration. Eine öffentliche REST-API und eine konfigurierbare automatische Zuordnungsregel-Engine sind derzeit **nicht** vorhanden.

## Nächste Schritte

1. [Systemvoraussetzungen und Installation](./installation)
2. [Umgebung konfigurieren](./configuration)
3. [Module im Überblick](../user-guide/overview)

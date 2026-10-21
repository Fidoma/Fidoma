# Architektur

Fidoma basiert auf Laravel 13, Filament 5 und Livewire. MySQL 8.4 speichert die Daten; Tabellen mit Präfix `ip_` lehnen sich an das InvoicePlane-Datenmodell an.

## Feature-First-Struktur

Fachlicher Code liegt unter `app/Features/<Feature>` und enthält nur die benötigten Schichten:

```text
app/Features/Invoices/
├── Actions/       # schreibende Anwendungsfälle
├── Enums/         # Typen und Status
├── Filament/      # Ressourcen, Seiten und Aktionen
├── Http/          # klassische Responses
├── Livewire/      # komplexe interaktive Oberflächen
├── Models/        # Eloquent und Relationen
├── Policies/      # Autorisierung
├── Queries/       # wiederverwendbare Leseabfragen
├── Services/      # Rendering und komplexere Logik
└── lang/          # de/en-Übersetzungen
```

`AdminPanelProvider` entdeckt Ressourcen unter `app/Features`; eigenständige Seiten werden explizit registriert. `FeatureTranslationServiceProvider` lädt Feature-Übersetzungen mit kebab-case Namespace.

## Geschäftslogik

Filament-Seiten delegieren schreibende Vorgänge an Actions. Rechnungsbeträge werden zentral neu berechnet, und PDF-Vorschau sowie PDF-Erzeugung verwenden dieselbe Template-Verarbeitung. Externe Clients kapseln Paperless, Terminal und Webmail.

## Infrastruktur

Die Produktion besteht aus identischen Images für Webanwendung, Queue-Worker und Scheduler sowie MySQL und persistentem Storage. Die lokale Entwicklung verwendet Laravel Sail.

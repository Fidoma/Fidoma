# Fidoma – Hinweise für Coding Agents

## Projektüberblick

Fidoma ist eine feature-basierte ERP-Anwendung für Rechnungen, Angebote, Reservierungen,
Zahlungen, Banktransaktionen, Belegzuordnungen und Auswertungen. Die Administrationsoberfläche
läuft unter `/admin` und wird überwiegend mit Filament und Livewire umgesetzt.

Bevor du etwas änderst, prüfe die betroffenen Models, Relationen, Status-Enums, Actions,
Services und vorhandenen Tests. Bestehende Geschäftsprozesse und Datenstrukturen sollen
wiederverwendet werden.

## Technischer Stack

- PHP 8.5 im Entwicklungscontainer
- Laravel 13
- Filament 5 als Admin-UI
- Livewire für interaktive Komponenten und Slide-overs
- MySQL 8.4
- Laravel Sail / Docker Compose für die lokale Umgebung
- Vite 8 und Tailwind CSS 4 für Frontend-Assets
- PHPUnit für Unit- und Feature-Tests; das Projekt verwendet nicht Pest
- Laravel Pint für PHP-Formatierung
- DomPDF (`barryvdh/laravel-dompdf`) für PDF-Erzeugung
- `horstoeko/invoicesuite` für elektronische Rechnungen/XRechnung
- CodeMirror 6 für den HTML-/CSS-Editor der PDF-Layouts
- Paperless-ngx wird über einen API-Client angebunden; Dokumente liegen nicht lokal in Fidoma

Füge keine neue Dependency hinzu, wenn die vorhandenen Pakete oder Laravel-/Filament-Mittel
ausreichen. Änderungen an Abhängigkeiten brauchen eine ausdrückliche Anforderung.

## Entwicklungsumgebung und Befehle

Das Projekt läuft mit Laravel Sail. Führe PHP-, Composer-, Artisan- und Node-Befehle im
Container aus. Wenn Sail bereits läuft, sind auch äquivalente `docker compose exec`-Befehle
möglich; dokumentiere bevorzugt Sail-Befehle.

```sh
vendor/bin/sail up -d
vendor/bin/sail artisan migrate
vendor/bin/sail artisan test
vendor/bin/sail artisan test tests/Feature/Filament/DocumentsPageTest.php
vendor/bin/sail bin pint --dirty
vendor/bin/sail npm run dev
vendor/bin/sail npm run build
vendor/bin/sail composer show --direct
vendor/bin/sail stop
```

Nutze `--no-interaction` bei generierenden Artisan-Befehlen. Prüfe vor versionsabhängigen
Änderungen die tatsächlich installierte Paketversion. Laravel Boost ist als Dev-Dependency
installiert; wenn die Boost-MCP-Werkzeuge verfügbar sind, nutze insbesondere die
Dokumentations-, Schema-, Query- und Browser-Log-Werkzeuge.

## Architektur

Die Fachlogik ist nach Features unter `app/Features/<Feature>` organisiert. Derzeit gibt es:

- `BankTransactions`, `Clients`, `Documents`, `EmailTemplates`
- `Families`, `InvoiceGroups`, `Invoices`, `PaymentMethods`, `Payments`
- `Products`, `Quotes`, `Reports`, `Reservations`
- `TaxRates`, `Units`, `Users`

Ein Feature besitzt nur die Verzeichnisse, die es benötigt, beispielsweise:

```text
app/Features/Invoices/
├── Actions/
├── Enums/
├── Filament/
├── Http/
├── Livewire/
├── Models/
├── Policies/
├── Queries/
├── Services/
└── lang/
```

### Zuständigkeiten

- `Filament/`: Resources, Pages, Tabellen, Formulare und Actions des Features
- `Livewire/`: eigenständige interaktive Oberflächen, insbesondere komplexe Slide-overs
- `Actions/`: einzelne schreibende Anwendungsfälle und Geschäftsoperationen
- `Queries/`: wiederverwendbare, lesende Abfragen und Tabellenabfragen
- `Services/`: komplexere Berechnungen, Rendering und externe Integrationen
- `Models/`: Eloquent-Modelle und Relationen
- `Enums/`: Status- und Typdefinitionen; keine Statuswerte als Magic Numbers duplizieren
- `Policies/`: Berechtigungen
- `Http/`: Controller für klassische Responses wie PDF, Vorschau und XRechnung
- `lang/`: feature-lokale Übersetzungen

Neue fachliche Klassen gehören in das passende Feature. `app/Filament` ist für
feature-übergreifende Seiten, Widgets, Concerns und UI-Bausteine vorgesehen. Allgemeine
Laravel-Provider bleiben in `app/Providers`. Erzeuge keine neuen globalen Sammelordner,
wenn der Code eindeutig einem Feature gehört.

## Filament und Livewire

Filament entdeckt Feature-Resources automatisch über `app/Features`; globale Seiten und
Widgets werden über `app/Filament/Pages` und `app/Filament/Widgets` entdeckt.

- Bevorzuge Filament-Komponenten, Layouts, Actions, Tabellen und Formulare.
- Ergänze eigenes Blade, JavaScript oder CSS nur, wenn Filament den Anwendungsfall nicht
  angemessen abbilden kann.
- Tabellen in verschachtelten Livewire-Komponenten oder Modals müssen einen eindeutigen
  Filament-Tabellen-Identifier erhalten, damit Pagination und Query-String-Zustand nicht mit
  der Haupttabelle kollidieren.
- Bewahre bestehende Slide-over-, Tabellen- und Action-Konventionen in Geschwisterklassen.
- UI-Navigation soll möglichst über benannte Routen beziehungsweise Filament-URLs erfolgen.

## Übersetzungen (i18n)

Jedes Feature verwaltet seine Texte selbst:

```text
app/Features/Documents/lang/de/messages.php
app/Features/Documents/lang/en/messages.php
```

`FeatureTranslationServiceProvider` lädt alle `app/Features/*/lang`-Verzeichnisse automatisch.
Der Namespace ist der Feature-Name in kebab-case:

```php
__('documents::messages.assignment.unassigned');
__('bank-transactions::messages.feature');
```

- Neue UI-Texte direkt im jeweiligen Feature anlegen.
- Deutsch ist die Standardsprache; englische Schlüssel parallel pflegen.
- Doppelte Begriffe zwischen Features sind erlaubt und zugunsten klarer Feature-Grenzen
  ausdrücklich akzeptiert.
- Fachliche Benutzerdaten wie Kunden-, Produkt- und Dokumenttexte werden nicht übersetzt.

## Daten und Geschäftslogik

- Tabellen mit Präfix `ip_` stammen konzeptionell aus dem InvoicePlane-Datenmodell. Benenne
  Tabellen oder Schlüssel nicht ohne zwingenden Grund um.
- Geldbeträge niemals mit unpräziser Float-Logik neu implementieren. Verwende vorhandene
  Calculator-, Amount- und Währungslogik.
- Berücksichtige bei Rechnungen Status, Typ, Teilzahlungen, Gutschriften, Stornierungen und
  Archivierung.
- Rechnungsumsatz und tatsächliche Zahlungseingänge sind fachlich unterschiedliche Größen.
- PDF-Vorschau und echtes PDF sollen dieselbe Template-Verarbeitung verwenden.
- Benutzerdefinierte PDF-Templates dürfen nicht als PHP oder Blade ausgeführt werden.
- Paperless-Zuordnungen und Rechnungszahlungen besitzen unterschiedliche fachliche Abläufe;
  vermische sie nicht ohne ausdrückliche Anforderung.

## Code-Konventionen

- Folge zuerst dem Stil benachbarter Dateien.
- Nutze strikte Parameter- und Rückgabetypen sowie aussagekräftige Namen.
- Verwende bei PHP-Kontrollstrukturen immer geschweifte Klammern.
- Nutze Enums, Relations, Policies und vorhandene Services statt duplizierter Logik.
- Halte Controller und Livewire-Komponenten schlank; verschiebe wiederverwendbare Fachlogik
  in Actions, Queries oder Services.
- Verändere keine unbeteiligten Dateien und überschreibe keine fremden uncommitted Änderungen.

## Tests und Abschlussprüfung

Das Projekt verwendet PHPUnit mit `RefreshDatabase` in datenbankabhängigen Feature-Tests.

- Ergänze Regressionstests für geändertes Verhalten und wichtige Fehlerfälle.
- Führe zunächst den kleinsten relevanten Test aus.
- Formatiere geänderte PHP-Dateien mit Pint.
- Führe bei bereichsübergreifenden Änderungen anschließend die vollständige Testsuite aus.
- Reine Text- oder Layoutänderungen benötigen nur dann einen Test, wenn bereits eine passende
  UI-Assertion existiert oder das Verhalten regressionsanfällig ist.

Abschlussreihenfolge:

```sh
vendor/bin/sail bin pint --dirty
vendor/bin/sail artisan test tests/Feature/BetroffenerTest.php
vendor/bin/sail artisan test
```


<div align="center">

# Fidoma

### Open-Source ERP für moderne Unternehmen

**Weniger Bürokratie. Mehr Automatisierung.**

![Laravel](https://img.shields.io/badge/Laravel-Framework-red)
![FilamentPHP](https://img.shields.io/badge/FilamentPHP-Admin-orange)
![Open Source](https://img.shields.io/badge/Open%20Source-ERP-blue)
![Modular](https://img.shields.io/badge/Architecture-Feature--First-green)

[Features](#features) · [Dokumentation](https://fidoma.github.io/Fidoma/) · [Installation](#installation) · [Technologie](#technologie--architektur) · [Mitwirken](#mitwirken)

</div>

---

## Über Fidoma

Fidoma ist ein modulares Open-Source-ERP zur Verwaltung und Automatisierung von Geschäftsprozessen – von der Rechnungsstellung über Zahlungsabwicklung bis hin zu branchenspezifischen Workflows.

## Features

| Modul | Beschreibung |
|---|---|
| 👥 **Kunden & Projekte** | Zentrale Verwaltung von Kunden und Projekten |
| 📄 **Rechnungen & Angebote** | Rechnungsstellung, Angebote und Zahlungsverwaltung |
| 🏨 **Branchenmodule** | Flexible Funktionen für Hotellerie und Handwerk |
| 💳 **ZVT Terminal** | Kartenzahlungen direkt über kompatible Zahlungsterminals |
| 🔄 **Transaction Mapping** | Automatische Zuordnung von Transaktionen zu Rechnungen |
| ⚡ **Automatisierung** | Wiederkehrende Geschäftsprozesse effizient automatisieren |

## Installation

Die vollständige Anleitung zu Installation, Konfiguration, Aktualisierung und Modulen steht in der [Fidoma-Dokumentation](https://fidoma.github.io/Fidoma/).

### Docker-Produktionsinstallation

Fidoma mit einem einzigen Befehl installieren:

```bash
curl -fsSL https://raw.githubusercontent.com/Fidoma/Fidoma/main/install.sh | bash
```

Der Schnellinstaller legt die Produktionsdateien gesammelt unter `./fidoma/` ab. Dort ist
`.env.production` die einzige aktive Produktionskonfiguration. Beim Aufruf von `./install.sh`
innerhalb eines geklonten Fidoma-Repositories wird stattdessen direkt das Repository-Verzeichnis
verwendet und kein zusätzlicher `fidoma/`-Unterordner angelegt.

> **Hinweis:** Den Skriptpfad vor Verwendung prüfen. Der Befehl setzt voraus, dass `install.sh` im Branch `main` existiert.

### Native Installation

Das vorkompilierte [Release-Archiv](https://github.com/Fidoma/Fidoma/releases/latest) herunterladen und entpacken. Composer-Abhängigkeiten und Frontend-Assets sind bereits enthalten.

1. `.env.example` in `.env` umbenennen.
2. `APP_ENV=production`, `APP_DEBUG=false`, `APP_URL`, `DB_*`, `SESSION_ENCRYPT=true` und `LOG_LEVEL=warning` konfigurieren.
3. Optional `MAIL_*` und `PAPERLESS_*` konfigurieren.
4. Folgende Befehle ausführen:

```bash
php artisan key:generate
php artisan migrate --force
php artisan storage:link
php artisan make:filament-user
php artisan optimize
```

Der Webserver muss auf `public/` zeigen. `storage/` und `bootstrap/cache/` müssen für den Webserver beschreibbar sein.

## Technologie & Architektur

**Laravel** · **FilamentPHP** · **PHP** · **Feature-First Architecture**

Fidoma basiert auf einer modularen Feature-First-Architektur. Jedes Feature ist logisch gekapselt und kann unabhängig erweitert und weiterentwickelt werden.

## Integrationen

- **ZVT** – Kommunikation mit kompatiblen Zahlungsterminals
- **Transaction Mapping** – Zuordnung und Verarbeitung von Zahlungstransaktionen
- **Paperless-ngx** – Dokumentenmanagement und automatisierte Verarbeitung, soweit integriert
- **Erweiterbare Schnittstellen** – Grundlage für zusätzliche Integrationen

## Mitwirken

Fidoma ist ein Open-Source-Projekt. Beiträge, Verbesserungsvorschläge und Fehlerberichte sind willkommen.

[Issues](https://github.com/Fidoma/Fidoma/issues) · [Pull Requests](https://github.com/Fidoma/Fidoma/pulls) · [Repository](https://github.com/Fidoma/Fidoma)

---

<div align="center">

**Fidoma – Open Source. Modular. Automatisiert.**

</div>

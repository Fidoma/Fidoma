
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

### Schnellinstallation

Fidoma mit einem einzigen Befehl installieren:

```bash
curl -fsSL https://raw.githubusercontent.com/Fidoma/Fidoma/main/install.sh | bash
```

> **Hinweis:** Den Skriptpfad vor Verwendung prüfen. Der Befehl setzt voraus, dass `install.sh` im Branch `main` existiert.

### Lokale Installation

Repository klonen:

```bash
git clone https://github.com/Fidoma/Fidoma.git
cd Fidoma
```

Anschließend die erforderlichen Abhängigkeiten installieren und die Umgebung konfigurieren.

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

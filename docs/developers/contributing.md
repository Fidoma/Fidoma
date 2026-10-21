# Mitwirken

## Entwicklungsablauf

```bash
vendor/bin/sail up -d
vendor/bin/sail artisan migrate
vendor/bin/sail npm run dev
```

Arbeite in einem fokussierten Branch und respektiere die Feature-Grenzen. Neue Abhängigkeiten sind nur sinnvoll, wenn Laravel, Filament oder die vorhandenen Pakete den Anwendungsfall nicht abdecken.

## Qualitätssicherung

Das Projekt verwendet PHPUnit, nicht Pest. Starte mit dem kleinsten relevanten Test:

```bash
vendor/bin/sail artisan test tests/Feature/Features/Invoices/IssueInvoiceTest.php
vendor/bin/sail bin pint --dirty
vendor/bin/sail artisan test
```

Für Änderungen an der Dokumentation:

```bash
vendor/bin/sail npm run docs:dev
vendor/bin/sail npm run docs:build
vendor/bin/sail npm run docs:preview
```

Die lokale Dokumentation verwendet standardmäßig den GitHub-Pages-Basispfad `/Fidoma/`. Öffne deshalb die von VitePress ausgegebene URL einschließlich dieses Pfads.

## GitHub Pages veröffentlichen

Der Workflow `.github/workflows/docs.yml` baut Pull Requests zur Kontrolle und veröffentlicht `main` über GitHub Pages. Im Repository unter **Settings → Pages** als Quelle **GitHub Actions** wählen. Nach dem nächsten Lauf ist die Website unter `https://fidoma.github.io/Fidoma/` erreichbar.

Für eine eigene Domain wird in GitHub Pages die Domain hinterlegt, ein `CNAME` im öffentlichen Dokumentationsordner ergänzt und die Repository-Variable `DOCS_BASE` auf `/` gesetzt. Ohne diese Variable bleibt der sichere Projektpfad `/Fidoma/` aktiv.

## Pull Request

Beschreibe fachliches Verhalten, technische Umsetzung, Tests und bekannte Grenzen. Verändere keine unbeteiligten Dateien und committe keine `.env`-Dateien oder Zugangsdaten.

# Installation

## Voraussetzungen

Für Entwicklung und Self-Hosting werden Git, Docker Engine und Docker Compose v2 benötigt. Die lokale Compose-Datei verwendet PHP 8.5 über Laravel Sail und MySQL 8.4. PHP, Composer, Node.js und MySQL müssen auf dem Host nicht separat installiert sein.

## Produktionsinstallation aus einem Release

Ein Release-Archiv enthält `setup.sh`, `compose.production.yaml` und `.env.production.example`. Im entpackten Verzeichnis:

```bash
chmod +x setup.sh
./setup.sh
```

Das Skript erzeugt `.env.production`, sichere Datenbankwerte und einen Anwendungsschlüssel, startet App, Queue, Scheduler und MySQL, führt Migrationen aus und legt beim ersten Lauf ein Administratorkonto an. Die einmaligen Zugangsdaten stehen in `.fidoma-admin`.

::: danger Zugangsdaten schützen
Melde dich sofort unter `http://localhost:5555/admin` an, ändere das Kennwort und lösche anschließend `.fidoma-admin`. Sichere `.env.production` niemals im Repository.
:::

Wichtige Werte lassen sich beim ersten Aufruf vorgeben:

```bash
FIDOMA_PORT=8080 \
FIDOMA_APP_URL=https://erp.example.org \
FIDOMA_ADMIN_EMAIL=admin@example.org \
FIDOMA_IMAGE=ghcr.io/fidoma/fidoma:latest \
./setup.sh
```

::: info Schnellinstaller
Die öffentliche Startseite stellt `/install.sh` dynamisch bereit. Vor dem Einsatz eines per `curl` geladenen Skripts sollte dessen Inhalt geprüft werden. Das Repository selbst enthält als belastbaren Einstiegspunkt `setup.sh`.
:::

## Lokale Entwicklungsinstallation

```bash
git clone https://github.com/Fidoma/Fidoma.git
cd Fidoma
cp .env.example .env
docker run --rm \
  -u "$(id -u):$(id -g)" \
  -v "$PWD:/app" -w /app \
  laravelsail/php85-composer:latest composer install
vendor/bin/sail up -d --build
vendor/bin/sail artisan key:generate
vendor/bin/sail artisan migrate
vendor/bin/sail npm install
vendor/bin/sail npm run build
vendor/bin/sail artisan make:filament-user \
  --name="Administrator" \
  --email="admin@example.org" \
  --password="ein-langes-einmalpasswort" \
  --panel=admin \
  --no-interaction
```

Danach sind die Anwendung unter `http://localhost:8000`, die Administration unter `http://localhost:8000/admin` und phpMyAdmin unter `http://localhost:8081` erreichbar.

## Häufige Probleme

### Port bereits belegt

Passe `APP_PORT`, `FORWARD_DB_PORT` oder `FORWARD_PHPMYADMIN_PORT` in `.env` an und starte die Container neu.

### Sail ist noch nicht vorhanden

`vendor/bin/sail` wird erst durch `composer install` installiert. Verwende dafür einmalig den oben gezeigten Composer-Container.

### Datenbank noch nicht bereit

Prüfe `vendor/bin/sail ps` und warte auf den Zustand `healthy` des MySQL-Containers. Danach Migration erneut ausführen.

### Schreibrechte im Storage

Der Webprozess benötigt Schreibrechte auf `storage` und `bootstrap/cache`. Im Produktionsimage übernimmt dies der mitgelieferte Entrypoint.

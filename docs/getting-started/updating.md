# Aktualisierung

## Produktionsinstallation

Vor jeder Aktualisierung Datenbank und das Volume `fidoma-storage` sichern. Anschließend das gewünschte Image in `.env.production` setzen und `setup.sh` erneut ausführen:

```bash
FIDOMA_IMAGE=ghcr.io/fidoma/fidoma:<version> ./setup.sh
```

Der erneute Lauf lädt das Image, startet die Dienste neu, führt offene Migrationen aus und optimiert Laravel. Vorhandene Schlüssel, Passwörter und das Administratorkonto bleiben erhalten.

## Entwicklungsinstallation

```bash
git pull --ff-only
vendor/bin/sail composer install
vendor/bin/sail npm ci
vendor/bin/sail artisan migrate
vendor/bin/sail npm run build
vendor/bin/sail artisan optimize:clear
```

## Rückkehr zu einer älteren Version

Ein Image-Downgrade macht Datenbankmigrationen nicht automatisch rückgängig. Stelle für ein belastbares Rollback das zur alten Version gehörende Datenbank- und Storage-Backup wieder her.

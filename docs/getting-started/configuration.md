# Konfiguration

Fidoma liest seine Konfiguration aus `.env` (Entwicklung) beziehungsweise `.env.production` (Produktions-Compose). Nach Änderungen an Produktionswerten:

```bash
docker compose --env-file .env.production -f compose.production.yaml exec app \
  php artisan optimize:clear
```

## Anwendung und Datenbank

| Variable | Zweck |
|---|---|
| `APP_URL` | Öffentliche Basis-URL, einschließlich `https://` |
| `APP_KEY` | Laravel-Verschlüsselungsschlüssel; nicht nach Inbetriebnahme austauschen |
| `APP_DEBUG` | In Produktion immer `false` |
| `DB_*` | MySQL-Verbindung der Anwendung |
| `SESSION_ENCRYPT` | Verschlüsselt Sitzungsdaten; Produktion verwendet `true` |
| `QUEUE_CONNECTION` | Produktion verwendet die Datenbank-Queue |

Die Compose-Werte `FIDOMA_DB_*` konfigurieren den MySQL-Container. `DB_*` müssen dazu passen.

## E-Mail

Rechnungen und Angebote werden über Laravels Mail-Konfiguration versendet. Relevant sind `MAIL_MAILER`, `MAIL_HOST`, `MAIL_PORT`, `MAIL_USERNAME`, `MAIL_PASSWORD`, `MAIL_FROM_ADDRESS` und `MAIL_VERIFY_CERTIFICATES`.

## Externe Dienste

| Integration | Variablen |
|---|---|
| Paperless-ngx | `PAPERLESS_URL`, `PAPERLESS_TOKEN` sowie optionale IDs für Ablagepfad, Dokumenttyp und benutzerdefinierte Felder |
| ZVT-Gateway | `TERMINAL_GATEWAY_URL`, `TERMINAL_GATEWAY_TOKEN`, Timeouts und `TERMINAL_PAYMENT_METHOD_ID` |
| Webmail | `IMAP_*`, SMTP-Werte und optional `ROUNDCUBE_PRIVATE_DIR` |

Details stehen unter [Integrationen](../integrations/overview).

## Module und Benutzer

Module können in der Administration unter **Einstellungen → Module** ein- und ausgeblendet werden. Standardmäßig sind alle registrierten Module aktiv. Das System unterstützt Anmeldung, Benutzerverwaltung, Sprachwahl sowie optionale App-basierte Zwei-Faktor-Authentifizierung.

::: warning Berechtigungen
Die vorhandenen Policies erlauben angemeldeten Benutzern derzeit weitreichenden Zugriff; eine Rollen- und Rechteverwaltung ist noch nicht implementiert. Begrenze Benutzerkonten und den Netzwerkzugriff entsprechend.
:::

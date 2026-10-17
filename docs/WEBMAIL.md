# Roundcube Webmail

Fidoma bindet Roundcube 1.7.4 unter **Kommunikation → Webmail** in einem iFrame ein.
Angemeldete Fidoma-Benutzer werden automatisch mit dem zentral konfigurierten IMAP-Konto
angemeldet. Nicht authentifizierte Zugriffe auf den Roundcube-Gateway werden abgewiesen.

## Konfiguration

Die SMTP-Werte werden aus der vorhandenen Laravel-Mailkonfiguration übernommen. Ergänzend
sind in `.env` die IMAP-Werte zu setzen:

```dotenv
MAIL_MAILER=smtp
MAIL_SCHEME=tls
MAIL_HOST=smtp.example.com
MAIL_PORT=587
MAIL_USERNAME="rechnung@example.com"
MAIL_PASSWORD="..."
MAIL_FROM_ADDRESS="rechnung@example.com"
MAIL_VERIFY_CERTIFICATES=true

IMAP_HOST=imap.example.com
IMAP_PORT=993
IMAP_SCHEME=ssl
IMAP_USERNAME="rechnung@example.com"
IMAP_PASSWORD="..."
IMAP_MAILBOX=INBOX
IMAP_VERIFY_CERTIFICATES=true
```

Danach in Fidoma die Seite **Webmail** öffnen und einmalig **Roundcube installieren** wählen.
Der Installer lädt ausschließlich das im Code festgelegte Complete-Archiv und prüft dessen
SHA-256-Prüfsumme vor dem Entpacken.

Optional kann `ROUNDCUBE_PRIVATE_DIR` auf ein absolutes, beschreibbares Verzeichnis außerhalb
des Webroots zeigen. Standardmäßig liegen Laufzeit, SQLite-Datenbank, Schlüssel, Logs und
temporäre Anhänge unter `storage/app/private/roundcube`. Unter `public/roundcube` werden nur
statische Dateien der geprüften Distribution veröffentlicht.

## Sicherheitsmodell

- Dynamische Roundcube-Aufrufe laufen über `/admin/webmail/client` und benötigen eine gültige
  Fidoma-Anmeldung.
- Roundcube verwendet eine eigene Session und eine eigene SQLite-Datenbank.
- Das IMAP-Passwort wird nur zur Laufzeit übergeben und weder in JavaScript noch in der
  generierten Roundcube-Konfiguration gespeichert.
- Die Roundcube-Session wird an die Fidoma-Sitzung und die aktuelle Mailkonfiguration gebunden.
- Der Gateway ist von Laravels CSRF-Prüfung ausgenommen, weil Roundcube dort seine eigene
  CSRF-Prüfung ausführt. Alle anderen Fidoma-Routen bleiben unverändert geschützt.

Das private Verzeichnis muss in Backups berücksichtigt werden. Für ein Roundcube-Update sind
Version, Prüfsumme und gegebenenfalls die Roundcube-Datenbankmigration bewusst anzupassen;
der Installationsbutton überschreibt keine vorhandene Installation.

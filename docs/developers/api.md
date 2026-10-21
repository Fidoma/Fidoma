# Schnittstellen

## Öffentliche API

Fidoma stellt derzeit **keine öffentliche REST- oder GraphQL-API** bereit. Es gibt auch keine `routes/api.php`. Die vorhandenen HTTP-Routen sind Webrouten und – abgesehen von der öffentlichen Startseite – authentifiziert.

## Interne Web-Endpunkte

Authentifizierte Endpunkte liefern unter anderem Rechnungsvorschau, PDF, XRechnung, PDF-Layout-Vorschau, Messedaten und den eingebetteten Webmail-Client. Sie sind für die Fidoma-Oberfläche vorgesehen und keine stabile Integrations-API.

## Ausgehende Integrationen

### Paperless-ngx

`PaperlessClient` authentifiziert sich per Token gegen die konfigurierte Basis-URL und arbeitet mit Dokumenten, Vorschauen und Metadaten.

### ZVTAPI-Gateway

Der Terminal-Client verwendet JSON über HTTP und sendet den API-Key als `X-API-Key`. Verwendete Gatewaypfade sind unter anderem `/health/ready`, `/payments` und Statusabfragen zu einem Vorgang.

::: warning Kompatibilität
Die Gateway-Aufrufe sind eine interne Vertragsschnittstelle des aktuellen Codes. Vor einem eigenen Gateway müssen Requests, Antworten und Statuswerte direkt am Interface und Client geprüft werden.
:::

## Neue API hinzufügen

Eine öffentliche API benötigt ein bewusstes Authentifizierungs-, Autorisierungs-, Versionierungs- und Fehlerformatkonzept. Sie sollte nicht durch Freigabe bestehender Admin-Routen simuliert werden.

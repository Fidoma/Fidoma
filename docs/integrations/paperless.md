# Paperless-ngx

Fidoma speichert Paperless-Dokumente nicht lokal. Der API-Client liest Dokumente, lädt Vorschauen und aktualisiert konfigurierte Metadaten. Lokale Zuordnungen verbinden eine Paperless-Dokument-ID mit einer Banktransaktion.

## Einrichtung

```dotenv
PAPERLESS_URL=https://paperless.example.org
PAPERLESS_TOKEN=api-token
PAPERLESS_STORAGE_PATH_ID=
PAPERLESS_DOCUMENT_TYPE_ID=
PAPERLESS_AMOUNT_FIELD_ID=
PAPERLESS_INVOICE_NUMBER_FIELD_ID=
```

URL und Token sind für den Zugriff erforderlich. Die IDs sind optional und müssen den IDs der Zielinstanz entsprechen. Aktiviere anschließend **Paperless** und **Zuordnungen** unter **Einstellungen → Module**.

## Arbeitsablauf

1. Dokumentübersicht öffnen.
2. Paperless-Dokument und passende Banktransaktion prüfen.
3. Zuordnung speichern; eine bereits verwendete Transaktion steht nicht erneut zur Verfügung.
4. Bei Bedarf Zuordnung wieder lösen.

::: warning Getrennte Prozesse
Eine Paperless-Zuordnung ist keine Rechnungszahlung. Sie dokumentiert den Zusammenhang zwischen externem Beleg und Banktransaktion; die Verbuchung an einer Rechnung erfolgt separat.
:::

Bei API-Fehlern URL, Token, Zertifikatskette und die konfigurierten IDs direkt in Paperless prüfen.

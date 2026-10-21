# Integrationen

| Integration | Richtung | Voraussetzung |
|---|---|---|
| SMTP | ausgehend | gültige Laravel-Mail-Konfiguration |
| Paperless-ngx | bidirektionale Metadatenabfragen/-updates | URL und API-Token |
| ZVTAPI-Gateway | ausgehend | Gateway-URL, API-Key und Zahlungsart |
| Roundcube/IMAP | eingebettete Webmail-Oberfläche | Roundcube- und Mailserver-Konfiguration |
| XRechnung | Dateierzeugung | Rechnungs- und Kundendaten |

Nicht konfigurierte Dienste sollten in der Modulverwaltung deaktiviert werden. Geheimnisse gehören ausschließlich in die jeweilige `.env`-Datei.

::: info FinTS
Im frei ausgelieferten Repository ist kein FinTS-Client zum automatischen Abruf von Banktransaktionen implementiert. Bankbewegungen werden im Self-Hosting-Kern per CSV importiert.
:::

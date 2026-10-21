# ZVT-Terminal

Fidoma spricht ein Zahlungsterminal nicht direkt an. Es sendet Zahlungsaufträge an einen separat betriebenen **ZVTAPI-Gateway**. Terminal-IP, Port, Händlerpasswort und ein möglicher Simulationsmodus werden dort konfiguriert.

## Fidoma konfigurieren

```dotenv
TERMINAL_GATEWAY_URL=http://host.docker.internal:5000
TERMINAL_GATEWAY_TOKEN=geheimer-api-schluessel
TERMINAL_GATEWAY_TIMEOUT=130
TERMINAL_GATEWAY_STATUS_TIMEOUT=10
TERMINAL_PAYMENT_METHOD_ID=3
```

`TERMINAL_PAYMENT_METHOD_ID` muss auf eine existierende Zahlungsart zeigen. Danach Konfigurationscache leeren und das Modul **Terminals** aktivieren.

## Verbindung und Status

Die Terminalseite fragt `/health/ready` mit dem Header `X-API-Key` ab. Bereit sind die Gateway-Zustände `OK` und `ErreichbarNichtRegistriert`. Ein erkannter Simulator wird sichtbar gekennzeichnet.

## Zahlung durchführen

1. Offene, nicht archivierte Rechnung auswählen.
2. Betrag bis maximal zum offenen Saldo eingeben.
3. Zahlungsanforderung starten und Karte am Terminal verarbeiten.
4. Bei laufendem oder unbekanntem Status den Zustand erneut abrufen.
5. Erst ein bestätigter Erfolg erzeugt genau eine Zahlung.

Zustände umfassen Senden, Warten auf Karte, Verarbeitung, erfolgreich, Demo-Erfolg, abgebrochen, fehlgeschlagen und unbekannt. Während eines aktiven oder unbekannten Vorgangs blockiert Fidoma weitere Terminalzahlungen.

::: danger Unklarer Status
Verbindungsfehler werden nicht als Erfolg gewertet. Starte nicht blind eine zweite Zahlung, sondern gleiche den vorhandenen Vorgang erneut ab und prüfe Terminalbeleg sowie Gateway-Protokoll.
:::

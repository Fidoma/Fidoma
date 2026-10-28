# KI-Vorlagen

Die verfügbaren Vorlagenbereiche sind zentral in `Enums/AiTemplateArea.php` definiert. Ein
neuer Bereich wird als weiterer Enum-Fall ergänzt und erhält in `lang/de/messages.php` sowie
`lang/en/messages.php` einen Eintrag unter `areas`.

Verbraucher laden aktive und für den aktuellen Benutzer erlaubte Vorlagen über
`Queries/AvailableAiTemplates`. Die Roundcube-Brücke überträgt ausschließlich Vorlagen-ID und
Titel. `Actions/PrepareAiTemplateExecution` validiert aktuell nur Vorlage und Nachrichtenkontext;
sie führt bewusst noch keine KI-Anfrage und keine schreibende Fachaktion aus.

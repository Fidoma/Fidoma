# Features & Module erweitern

## Bestehendes Feature erweitern

1. Benachbarte Models, Relationen, Enums, Actions und Tests lesen.
2. UI in der bestehenden Filament-Resource oder Feature-Seite ergänzen.
3. Schreiblogik in einer Action, Leseabfragen in einer Query kapseln.
4. Deutsche und englische Texte im Feature ergänzen.
5. Regressionstest hinzufügen.

Ein typischer Aufruf bleibt dadurch schlank:

```php
public function save(CreateProject $createProject): void
{
    $project = $createProject->handle($this->form->getState());

    $this->redirect(ProjectResource::getUrl('edit', ['record' => $project]));
}
```

## Neues Feature registrieren

Filament-Resources werden automatisch unter `app/Features` entdeckt. Eigenständige Pages müssen wie `Reports` oder `Terminal` in `AdminPanelProvider::pages()` eingetragen werden. Soll die Sichtbarkeit konfigurierbar sein, wird ein klarer Schlüssel in `ModuleSettings` ergänzt und die bestehende Navigation-Concern verwendet.

## Datenbankänderungen

Migrationen liegen zentral unter `database/migrations`. Bestehende `ip_`-Tabellen und Schlüssel nur aus zwingendem Grund umbenennen. Geldbeträge nicht mit neuer Float-Logik berechnen, sondern die vorhandenen Calculator-/Amount-Strukturen wiederverwenden.

## Übersetzungen

```php
__('bank-transactions::messages.feature');
```

Neue sichtbare Texte werden parallel unter `lang/de/messages.php` und `lang/en/messages.php` gepflegt.

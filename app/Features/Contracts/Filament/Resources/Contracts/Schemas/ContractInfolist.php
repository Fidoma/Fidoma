<?php

namespace App\Features\Contracts\Filament\Resources\Contracts\Schemas;

use App\Features\Contracts\Models\Contract;
use App\Features\Contracts\Models\ContractTemplateField;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ContractInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(['default' => 1, 'lg' => 2])
            ->components([
                Section::make('Vertragsdaten')
                    ->columns(['default' => 1, 'md' => 2])
                    ->schema([
                        TextEntry::make('number')->label('Vertragsnummer')->copyable(),
                        TextEntry::make('status')
                            ->label('Status')
                            ->badge()
                            ->formatStateUsing(fn (string $state): string => $state === 'draft' ? 'Entwurf' : $state),
                        TextEntry::make('client_name')
                            ->label('Kunde')
                            ->state(fn (Contract $record): string => $record->client->client_company ?: $record->client->client_name),
                        TextEntry::make('template.name')->label('Vertragsvorlage'),
                        TextEntry::make('issuer.name')->label('Aussteller'),
                        TextEntry::make('contract_location')->label('Vertragsort'),
                        TextEntry::make('contract_create_date')->label('Erstellungsdatum')->date('d.m.Y'),
                        TextEntry::make('contract_start_date')->label('Vertragsbeginn')->date('d.m.Y'),
                        TextEntry::make('contract_end_date')->label('Vertragsende')->date('d.m.Y')->placeholder('Unbefristet'),
                    ]),
                Section::make('Weitere Vertragsdaten')
                    ->columns(['default' => 1, 'md' => 2])
                    ->schema(fn (Contract $record): array => self::customFieldEntries($record))
                    ->visible(fn (Contract $record): bool => $record->template->fields->isNotEmpty()),
            ]);
    }

    /** @return array<int, Component> */
    private static function customFieldEntries(Contract $contract): array
    {
        $contract->loadMissing(['template.fields', 'fieldValues']);
        $values = $contract->fieldValues->pluck('value', 'template_field_id');

        return $contract->template->fields
            ->map(fn (ContractTemplateField $field): Component => self::customFieldEntry(
                $field,
                $values->get($field->getKey()),
            ))
            ->all();
    }

    private static function customFieldEntry(ContractTemplateField $field, mixed $value): Component
    {
        if ($field->field_type === 'boolean') {
            return IconEntry::make("custom_field_{$field->getKey()}")
                ->label($field->label)
                ->state(filter_var($value, FILTER_VALIDATE_BOOLEAN))
                ->boolean();
        }

        $entry = TextEntry::make("custom_field_{$field->getKey()}")
            ->label($field->label)
            ->state($value)
            ->placeholder('–');

        return match ($field->field_type) {
            'date' => $entry->date('d.m.Y'),
            'currency' => $entry->money('EUR', locale: 'de'),
            default => $entry,
        };
    }
}

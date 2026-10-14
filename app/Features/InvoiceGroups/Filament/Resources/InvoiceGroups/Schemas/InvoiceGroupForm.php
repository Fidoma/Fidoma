<?php

namespace App\Features\InvoiceGroups\Filament\Resources\InvoiceGroups\Schemas;

use App\Features\InvoiceGroups\Services\InvoiceGroupNumberFormatter;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;

class InvoiceGroupForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Nummernkreis')
                ->description('Das gleiche Prinzip wird für Rechnungs- und Angebotskreise verwendet.')
                ->columnSpanFull()
                ->columns([
                    'default' => 1,
                    'md' => 2,
                ])
                ->schema([
                    TextInput::make('invoice_group_name')
                        ->label('Name')
                        ->placeholder('z. B. Angebote')
                        ->required()
                        ->columnSpanFull(),
                    TextInput::make('invoice_group_identifier_format')
                        ->label('Nummernformat')
                        ->placeholder('ANG-{{{year}}}-{{{id}}}')
                        ->helperText('Verfügbare Platzhalter: {{{id}}}, {{{year}}}, {{{yy}}}, {{{month}}}, {{{day}}}')
                        ->required()
                        ->maxLength(255)
                        ->live(debounce: 500)
                        ->columnSpanFull(),
                    TextInput::make('invoice_group_next_id')
                        ->label('Nächste laufende Nummer')
                        ->helperText('Diese Nummer wird beim Erstellen des nächsten Belegs verwendet und danach erhöht.')
                        ->integer()
                        ->minValue(1)
                        ->default(1)
                        ->required()
                        ->live(debounce: 500),
                    TextInput::make('invoice_group_left_pad')
                        ->label('Mindeststellen der Nummer')
                        ->helperText('Beispiel: 5 Stellen erzeugen aus 23 die Nummer 00023.')
                        ->integer()
                        ->minValue(0)
                        ->maxValue(20)
                        ->default(0)
                        ->required()
                        ->live(debounce: 500),
                    Placeholder::make('number_preview')
                        ->label('Vorschau der nächsten Belegnummer')
                        ->content(fn (Get $get): string => self::preview($get))
                        ->columnSpanFull(),
                ]),
        ]);
    }

    private static function preview(Get $get): string
    {
        $format = (string) ($get('invoice_group_identifier_format') ?: '{{{id}}}');

        return app(InvoiceGroupNumberFormatter::class)->format(
            $format,
            max(1, (int) ($get('invoice_group_next_id') ?: 1)),
            max(0, (int) ($get('invoice_group_left_pad') ?: 0)),
        );
    }
}

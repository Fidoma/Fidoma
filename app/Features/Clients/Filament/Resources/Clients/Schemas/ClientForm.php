<?php

namespace App\Features\Clients\Filament\Resources\Clients\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;

class ClientForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(['default' => 1, 'lg' => 2])
            ->components([
                Section::make('Anschrift')
                    ->columns(2)
                    ->schema([
                        TextInput::make('client_address_1')
                            ->label('Straße und Hausnummer')
                            ->maxLength(100)
                            ->columnSpanFull(),
                        TextInput::make('client_address_2')
                            ->label('Adresszusatz')
                            ->columnSpanFull(),
                        TextInput::make('client_zip')
                            ->label('PLZ')
                            ->maxLength(15),
                        TextInput::make('client_city')
                            ->label('Ort')
                            ->maxLength(45),
                        TextInput::make('client_state')
                            ->label('Bundesland / Region')
                            ->maxLength(35),
                        TextInput::make('client_country')
                            ->label('Land')
                            ->maxLength(35),
                    ]),
                Section::make('Stammdaten')
                    ->columns(2)
                    ->schema([
                        TextInput::make('client_title')
                            ->label('Anrede / Titel')
                            ->maxLength(50),
                        TextInput::make('client_company')
                            ->label('Firma')
                            ->maxLength(150),
                        TextInput::make('client_name')
                            ->label('Vorname / Name')
                            ->required()
                            ->maxLength(100),
                        TextInput::make('client_surname')
                            ->label('Nachname')
                            ->maxLength(255),
                        Toggle::make('client_active')
                            ->label('Aktiv')
                            ->default(true),
                    ]),
                Section::make('Kontakt')
                    ->columns(2)
                    ->schema([
                        TextInput::make('client_invoicing_contact')
                            ->label('Rechnungskontakt')
                            ->maxLength(50),
                        TextInput::make('client_email')
                            ->label('E-Mail')
                            ->email()
                            ->maxLength(100),
                        TextInput::make('client_phone')
                            ->label('Telefon')
                            ->tel()
                            ->maxLength(20),
                        TextInput::make('client_mobile')
                            ->label('Mobil')
                            ->tel()
                            ->maxLength(20),
                        TextInput::make('client_fax')
                            ->label('Fax')
                            ->maxLength(255),
                        TextInput::make('client_web')
                            ->label('Webseite')
                            ->url()
                            ->maxLength(100),
                    ]),
                Section::make('Rechnungs- und Steuerdaten')
                    ->columns(2)
                    ->schema([
                        TextInput::make('client_vat_id')
                            ->label('USt-IdNr.'),
                        TextInput::make('client_tax_code')
                            ->label('Steuernummer'),
                        Toggle::make('client_einvoicing_active')
                            ->label('E-Rechnung aktiv')
                            ->live(),
                        Select::make('client_einvoicing_version')
                            ->label('E-Rechnungsformat')
                            ->default('XRechnung 3.0 (CII)')
                            ->options([
                                'XRechnung 3.0 (CII)' => 'XRechnung 3.0 (CII)',
                                'XRechnung 3.0 (UBL)' => 'XRechnung 3.0 (UBL) – noch nicht verfügbar',
                                'ZUGFeRD 2.x / Factur-X' => 'ZUGFeRD 2.x / Factur-X – noch nicht verfügbar',
                                'Peppol BIS Billing 3.0' => 'Peppol BIS Billing 3.0 – noch nicht verfügbar',
                            ])
                            ->disableOptionWhen(fn (string $value): bool => $value !== 'XRechnung 3.0 (CII)')
                            ->required(fn (Get $get): bool => (bool) $get('client_einvoicing_active'))
                            ->native(false)
                            ->visible(fn (Get $get): bool => (bool) $get('client_einvoicing_active')),
                        TextInput::make('client_einvoicing_reference')
                            ->label('Käuferreferenz / Leitweg-ID')
                            ->helperText('Für XRechnungen verpflichtend. Bei Behörden ist dies üblicherweise die Leitweg-ID.')
                            ->maxLength(100)
                            ->required(fn (Get $get): bool => (bool) $get('client_einvoicing_active'))
                            ->visible(fn (Get $get): bool => (bool) $get('client_einvoicing_active')),
                    ]),
            ]);
    }
}

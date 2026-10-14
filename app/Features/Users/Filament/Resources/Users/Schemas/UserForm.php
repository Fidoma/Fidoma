<?php

namespace App\Features\Users\Filament\Resources\Users\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class UserForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(['default' => 1, 'lg' => 2])
            ->components([
                Section::make('Benutzerkonto')
                    ->columns(2)
                    ->schema([
                        TextInput::make('name')
                            ->label('Name')
                            ->required()
                            ->maxLength(255),
                        TextInput::make('email')
                            ->label('E-Mail-Adresse')
                            ->email()
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(255),
                        TextInput::make('password')
                            ->label('Passwort')
                            ->password()
                            ->revealable()
                            ->confirmed()
                            ->required(fn (string $operation): bool => $operation === 'create')
                            ->dehydrated(fn (?string $state): bool => filled($state))
                            ->maxLength(255),
                        TextInput::make('password_confirmation')
                            ->label('Passwort bestätigen')
                            ->password()
                            ->revealable()
                            ->required(fn (string $operation): bool => $operation === 'create')
                            ->dehydrated(false)
                            ->maxLength(255),
                    ]),
                Section::make('Firma und Anschrift')
                    ->columns(2)
                    ->schema([
                        TextInput::make('company_name')
                            ->label('Firmenname')
                            ->maxLength(255),
                        TextInput::make('invoicing_contact')
                            ->label('Ansprechpartner Rechnungen')
                            ->maxLength(50),
                        TextInput::make('address_line_1')
                            ->label('Straße und Hausnummer')
                            ->maxLength(255),
                        TextInput::make('address_line_2')
                            ->label('Adresszusatz')
                            ->maxLength(255),
                        TextInput::make('postal_code')
                            ->label('Postleitzahl')
                            ->maxLength(20),
                        TextInput::make('city')
                            ->label('Ort')
                            ->maxLength(255),
                        TextInput::make('state')
                            ->label('Bundesland / Region')
                            ->maxLength(255),
                        TextInput::make('country')
                            ->label('Land')
                            ->maxLength(255),
                    ]),
                Section::make('Kontakt')
                    ->columns(2)
                    ->schema([
                        TextInput::make('phone')->label('Telefon')->tel()->maxLength(50),
                        TextInput::make('mobile')->label('Mobil')->tel()->maxLength(50),
                        TextInput::make('fax')->label('Fax')->maxLength(50),
                        TextInput::make('website')->label('Website')->url()->maxLength(255),
                    ]),
                Section::make('Steuerdaten')
                    ->columns(2)
                    ->schema([
                        TextInput::make('vat_id')->label('USt-IdNr.')->maxLength(50),
                        TextInput::make('tax_number')->label('Steuernummer')->maxLength(50),
                        TextInput::make('gln')->label('GLN')->maxLength(13),
                    ]),
                Section::make('Bankverbindung')
                    ->columns(2)
                    ->schema([
                        TextInput::make('bank_name')->label('Bank')->maxLength(100),
                        TextInput::make('iban')->label('IBAN')->maxLength(34),
                        TextInput::make('bic')->label('BIC')->maxLength(11),
                        TextInput::make('remittance_text')
                            ->label('Verwendungszweck')
                            ->maxLength(105),
                    ]),
            ]);
    }
}

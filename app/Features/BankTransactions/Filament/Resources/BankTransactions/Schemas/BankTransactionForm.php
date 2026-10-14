<?php

namespace App\Features\BankTransactions\Filament\Resources\BankTransactions\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class BankTransactionForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(['default' => 1, 'lg' => 2])
            ->components([
                Section::make('Transaktion')
                    ->columns(2)
                    ->schema([
                        TextInput::make('account_iban')
                            ->label('Konto-IBAN')
                            ->required()
                            ->maxLength(34)
                            ->columnSpanFull(),
                        DatePicker::make('booking_date')
                            ->label('Buchungsdatum')
                            ->default(now()->toDateString())
                            ->required(),
                        DatePicker::make('value_date')
                            ->label('Wertstellungsdatum'),
                        TextInput::make('amount')
                            ->label('Betrag')
                            ->numeric()
                            ->step(0.0001)
                            ->required(),
                        Select::make('currency')
                            ->label('Währung')
                            ->options([
                                'EUR' => 'EUR',
                                'CHF' => 'CHF',
                                'USD' => 'USD',
                                'GBP' => 'GBP',
                            ])
                            ->default('EUR')
                            ->required()
                            ->native(false),
                        Toggle::make('pending')
                            ->label('Vorgemerkt')
                            ->default(false),
                    ]),
                Section::make('Gegenpartei')
                    ->columns(2)
                    ->schema([
                        TextInput::make('counterparty_name')
                            ->label('Name')
                            ->maxLength(255)
                            ->columnSpanFull(),
                        TextInput::make('counterparty_iban')
                            ->label('IBAN')
                            ->maxLength(34)
                            ->columnSpanFull(),
                        Textarea::make('purpose')
                            ->label('Verwendungszweck')
                            ->rows(4)
                            ->columnSpanFull(),
                        TextInput::make('booking_text')
                            ->label('Buchungstext')
                            ->maxLength(255)
                            ->columnSpanFull(),
                    ]),
                Section::make('Bankreferenzen')
                    ->columns(2)
                    ->collapsed()
                    ->columnSpanFull()
                    ->schema([
                        TextInput::make('bank_transaction_id')
                            ->label('Bank-Transaktions-ID')
                            ->maxLength(255),
                        TextInput::make('bank_reference')
                            ->label('Bankreferenz')
                            ->maxLength(255),
                        TextInput::make('end_to_end_id')
                            ->label('End-to-End-ID')
                            ->maxLength(255)
                            ->columnSpanFull(),
                        TextInput::make('transaction_reference')
                            ->label('Interne Transaktionsreferenz')
                            ->disabled()
                            ->dehydrated(false)
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}

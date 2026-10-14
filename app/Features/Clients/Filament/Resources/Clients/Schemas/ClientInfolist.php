<?php

namespace App\Features\Clients\Filament\Resources\Clients\Schemas;

use App\Features\Clients\Models\Client;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ClientInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(['default' => 1, 'lg' => 2])
            ->components([
                Section::make('Kontaktinformationen')
                    ->columns(2)
                    ->schema([
                        TextEntry::make('client_company')->label('Firma')->placeholder('–'),
                        TextEntry::make('display_name')
                            ->label('Name')
                            ->state(fn (Client $record): string => trim(implode(' ', array_filter([
                                $record->client_title,
                                $record->client_name,
                                $record->client_surname,
                            ])))),
                        TextEntry::make('client_address_1')->label('Adresse')->placeholder('–'),
                        TextEntry::make('client_address_2')->label('Adresszusatz')->placeholder('–'),
                        TextEntry::make('location')
                            ->label('PLZ / Ort')
                            ->state(fn (Client $record): string => trim($record->client_zip.' '.$record->client_city))
                            ->placeholder('–'),
                        TextEntry::make('client_country')->label('Land')->placeholder('–'),
                        TextEntry::make('client_email')->label('E-Mail')->icon('heroicon-m-envelope')->copyable()->placeholder('–'),
                        TextEntry::make('client_phone')->label('Telefon')->icon('heroicon-m-phone')->placeholder('–'),
                        TextEntry::make('client_mobile')->label('Mobil')->icon('heroicon-m-device-phone-mobile')->placeholder('–'),
                        TextEntry::make('client_web')->label('Webseite')->url(fn (?string $state): ?string => $state)->openUrlInNewTab()->placeholder('–'),
                    ]),
                Section::make('Rechnungsinformationen')
                    ->columns(2)
                    ->schema([
                        TextEntry::make('billed_total')
                            ->label('Gesamt berechnet')
                            ->state(fn (Client $record): float => (float) $record->invoices()->with('amount')->get()->sum(
                                fn ($invoice): float => (float) ($invoice->amount?->invoice_total ?? 0),
                            ))
                            ->money('EUR', locale: 'de'),
                        TextEntry::make('paid_total')
                            ->label('Gesamt bezahlt')
                            ->state(fn (Client $record): float => (float) $record->payments()->sum('payment_amount'))
                            ->money('EUR', locale: 'de'),
                        TextEntry::make('open_total')
                            ->label('Offener Betrag')
                            ->state(fn (Client $record): float => (float) $record->invoices()->with('amount')->get()->sum(
                                fn ($invoice): float => (float) ($invoice->amount?->invoice_balance ?? 0),
                            ))
                            ->money('EUR', locale: 'de'),
                        TextEntry::make('client_invoicing_contact')->label('Rechnungskontakt')->placeholder('–'),
                        TextEntry::make('client_vat_id')->label('USt-IdNr.')->placeholder('–'),
                        TextEntry::make('client_tax_code')->label('Steuernummer')->placeholder('–'),
                        IconEntry::make('client_einvoicing_active')->label('E-Rechnung')->boolean(),
                        TextEntry::make('client_einvoicing_reference')->label('Käuferreferenz / Leitweg-ID')->placeholder('–'),
                        IconEntry::make('client_active')->label('Aktiv')->boolean(),
                    ]),
            ]);
    }
}

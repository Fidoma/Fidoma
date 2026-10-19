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
                Section::make(__('clients::messages.sections.contact_information'))
                    ->columns(2)
                    ->schema([
                        TextEntry::make('client_company')->label(__('clients::messages.fields.company'))->placeholder('–'),
                        TextEntry::make('display_name')
                            ->label(__('clients::messages.fields.name'))
                            ->state(fn (Client $record): string => trim(implode(' ', array_filter([
                                $record->client_title,
                                $record->client_name,
                                $record->client_surname,
                            ])))),
                        TextEntry::make('client_address_1')->label(__('clients::messages.fields.address'))->placeholder('–'),
                        TextEntry::make('client_address_2')->label(__('clients::messages.fields.address_2'))->placeholder('–'),
                        TextEntry::make('location')
                            ->label(__('clients::messages.fields.zip_city'))
                            ->state(fn (Client $record): string => trim($record->client_zip.' '.$record->client_city))
                            ->placeholder('–'),
                        TextEntry::make('client_country')->label(__('clients::messages.fields.country'))->placeholder('–'),
                        TextEntry::make('client_email')->label(__('clients::messages.fields.email'))->icon('heroicon-m-envelope')->copyable()->placeholder('–'),
                        TextEntry::make('client_phone')->label(__('clients::messages.fields.phone'))->icon('heroicon-m-phone')->placeholder('–'),
                        TextEntry::make('client_mobile')->label(__('clients::messages.fields.mobile'))->icon('heroicon-m-device-phone-mobile')->placeholder('–'),
                        TextEntry::make('client_web')->label(__('clients::messages.fields.website'))->url(fn (?string $state): ?string => $state)->openUrlInNewTab()->placeholder('–'),
                    ]),
                Section::make(__('clients::messages.sections.billing_information'))
                    ->columns(2)
                    ->schema([
                        TextEntry::make('billed_total')
                            ->label(__('clients::messages.fields.total_billed'))
                            ->state(fn (Client $record): float => (float) $record->invoices()->with('amount')->get()->sum(
                                fn ($invoice): float => (float) ($invoice->amount?->invoice_total ?? 0),
                            ))
                            ->money('EUR', locale: app()->getLocale()),
                        TextEntry::make('paid_total')
                            ->label(__('clients::messages.fields.total_paid'))
                            ->state(fn (Client $record): float => (float) $record->payments()->sum('payment_amount'))
                            ->money('EUR', locale: app()->getLocale()),
                        TextEntry::make('open_total')
                            ->label(__('clients::messages.fields.outstanding_amount'))
                            ->state(fn (Client $record): float => (float) $record->invoices()->with('amount')->get()->sum(
                                fn ($invoice): float => (float) ($invoice->amount?->invoice_balance ?? 0),
                            ))
                            ->money('EUR', locale: app()->getLocale()),
                        TextEntry::make('client_invoicing_contact')->label(__('clients::messages.fields.billing_contact'))->placeholder('–'),
                        TextEntry::make('client_vat_id')->label(__('clients::messages.fields.vat_id'))->placeholder('–'),
                        TextEntry::make('client_tax_code')->label(__('clients::messages.fields.tax_code'))->placeholder('–'),
                        IconEntry::make('client_einvoicing_active')->label(__('clients::messages.fields.einvoicing'))->boolean(),
                        TextEntry::make('client_einvoicing_reference')->label(__('clients::messages.fields.buyer_reference'))->placeholder('–'),
                        IconEntry::make('client_active')->label(__('clients::messages.fields.active'))->boolean(),
                    ]),
            ]);
    }
}

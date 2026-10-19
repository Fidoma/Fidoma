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
                Section::make(__('clients::messages.sections.address'))
                    ->columns(2)
                    ->schema([
                        TextInput::make('client_address_1')
                            ->label(__('clients::messages.fields.address_1'))
                            ->maxLength(100)
                            ->columnSpanFull(),
                        TextInput::make('client_address_2')
                            ->label(__('clients::messages.fields.address_2'))
                            ->columnSpanFull(),
                        TextInput::make('client_zip')
                            ->label(__('clients::messages.fields.zip'))
                            ->maxLength(15),
                        TextInput::make('client_city')
                            ->label(__('clients::messages.fields.city'))
                            ->maxLength(45),
                        TextInput::make('client_state')
                            ->label(__('clients::messages.fields.state'))
                            ->maxLength(35),
                        TextInput::make('client_country')
                            ->label(__('clients::messages.fields.country'))
                            ->maxLength(35),
                    ]),
                Section::make(__('clients::messages.sections.master_data'))
                    ->columns(2)
                    ->schema([
                        TextInput::make('client_title')
                            ->label(__('clients::messages.fields.title'))
                            ->maxLength(50),
                        TextInput::make('client_company')
                            ->label(__('clients::messages.fields.company'))
                            ->maxLength(150),
                        TextInput::make('client_name')
                            ->label(__('clients::messages.fields.first_name'))
                            ->required()
                            ->maxLength(100),
                        TextInput::make('client_surname')
                            ->label(__('clients::messages.fields.last_name'))
                            ->maxLength(255),
                        Toggle::make('client_active')
                            ->label(__('clients::messages.fields.active'))
                            ->default(true),
                    ]),
                Section::make(__('clients::messages.sections.contact'))
                    ->columns(2)
                    ->schema([
                        TextInput::make('client_invoicing_contact')
                            ->label(__('clients::messages.fields.billing_contact'))
                            ->maxLength(50),
                        TextInput::make('client_email')
                            ->label(__('clients::messages.fields.email'))
                            ->email()
                            ->maxLength(100),
                        TextInput::make('client_phone')
                            ->label(__('clients::messages.fields.phone'))
                            ->tel()
                            ->maxLength(20),
                        TextInput::make('client_mobile')
                            ->label(__('clients::messages.fields.mobile'))
                            ->tel()
                            ->maxLength(20),
                        TextInput::make('client_fax')
                            ->label(__('clients::messages.fields.fax'))
                            ->maxLength(255),
                        TextInput::make('client_web')
                            ->label(__('clients::messages.fields.website'))
                            ->url()
                            ->maxLength(100),
                    ]),
                Section::make(__('clients::messages.sections.billing_tax'))
                    ->columns(2)
                    ->schema([
                        TextInput::make('client_vat_id')
                            ->label(__('clients::messages.fields.vat_id')),
                        TextInput::make('client_tax_code')
                            ->label(__('clients::messages.fields.tax_code')),
                        Toggle::make('client_einvoicing_active')
                            ->label(__('clients::messages.fields.einvoicing_active'))
                            ->live(),
                        Select::make('client_einvoicing_version')
                            ->label(__('clients::messages.fields.einvoicing_format'))
                            ->default('XRechnung 3.0 (CII)')
                            ->options([
                                'XRechnung 3.0 (CII)' => 'XRechnung 3.0 (CII)',
                                'XRechnung 3.0 (UBL)' => __('clients::messages.einvoicing.unavailable', ['format' => 'XRechnung 3.0 (UBL)']),
                                'ZUGFeRD 2.x / Factur-X' => __('clients::messages.einvoicing.unavailable', ['format' => 'ZUGFeRD 2.x / Factur-X']),
                                'Peppol BIS Billing 3.0' => __('clients::messages.einvoicing.unavailable', ['format' => 'Peppol BIS Billing 3.0']),
                            ])
                            ->disableOptionWhen(fn (string $value): bool => $value !== 'XRechnung 3.0 (CII)')
                            ->required(fn (Get $get): bool => (bool) $get('client_einvoicing_active'))
                            ->native(false)
                            ->visible(fn (Get $get): bool => (bool) $get('client_einvoicing_active')),
                        TextInput::make('client_einvoicing_reference')
                            ->label(__('clients::messages.fields.buyer_reference'))
                            ->helperText(__('clients::messages.einvoicing.reference_help'))
                            ->maxLength(100)
                            ->required(fn (Get $get): bool => (bool) $get('client_einvoicing_active'))
                            ->visible(fn (Get $get): bool => (bool) $get('client_einvoicing_active')),
                    ]),
            ]);
    }
}

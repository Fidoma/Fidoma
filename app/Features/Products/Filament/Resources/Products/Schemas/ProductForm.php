<?php

namespace App\Features\Products\Filament\Resources\Products\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class ProductForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(2)
            ->components([
                TextInput::make('product_sku')
                    ->label('Artikelnummer'),
                TextInput::make('product_name')
                    ->label('Produktname')
                    ->required()
                    ->maxLength(50),
                Textarea::make('product_description')
                    ->label('Beschreibung')
                    ->required()
                    ->rows(5)
                    ->columnSpanFull(),
                TextInput::make('product_price')
                    ->label('Verkaufspreis')
                    ->required()
                    ->numeric()
                    ->step(0.01),
                TextInput::make('purchase_price')
                    ->label('Einkaufspreis')
                    ->required()
                    ->numeric()
                    ->step(0.01),
                TextInput::make('provider_name')
                    ->label('Lieferant'),
                Select::make('family_id')
                    ->label('Produktfamilie')
                    ->relationship('family', 'family_name')
                    ->required()
                    ->searchable()
                    ->preload(),
                Select::make('unit_id')
                    ->label('Einheit')
                    ->relationship('unit', 'unit_name')
                    ->searchable()
                    ->preload(),
                TextInput::make('product_tariff')
                    ->label('Tarifnummer')
                    ->integer(),
                Select::make('tax_rate_id')
                    ->label('Steuersatz')
                    ->relationship('taxRate', 'tax_rate_name')
                    ->required()
                    ->searchable()
                    ->preload(),
            ]);
    }
}

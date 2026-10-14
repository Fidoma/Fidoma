<?php

namespace App\Features\Products\Filament\Resources\Products\Tables;

use App\Features\Products\Filament\Resources\Products\ProductResource;
use Filament\Actions\CreateAction;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ProductsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->recordUrl(fn ($record): string => ProductResource::getUrl('edit', ['record' => $record]))
            ->columns([
                TextColumn::make('product_sku')
                    ->label('Artikelnummer')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('product_name')
                    ->label('Produktname')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('product_price')
                    ->label('Verkaufspreis')
                    ->numeric(decimalPlaces: 2)
                    ->sortable(),
                TextColumn::make('purchase_price')
                    ->label('Einkaufspreis')
                    ->numeric(decimalPlaces: 2)
                    ->sortable(),
                TextColumn::make('family.family_name')
                    ->label('Familie')
                    ->placeholder('Ohne Namen')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('taxRate.tax_rate_name')
                    ->label('Steuersatz')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('unit.unit_name')
                    ->label('Einheit'),
            ])
            ->toolbarActions([
                CreateAction::make()
                    ->label('Produkt')
                    ->icon(Heroicon::Plus)
                    ->url(ProductResource::getUrl('create')),
            ]);
    }
}

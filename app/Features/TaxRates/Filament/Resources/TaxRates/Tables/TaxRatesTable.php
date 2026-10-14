<?php

namespace App\Features\TaxRates\Filament\Resources\TaxRates\Tables;

use App\Features\TaxRates\Filament\Resources\TaxRates\TaxRateResource;
use Filament\Actions\CreateAction;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class TaxRatesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->recordUrl(fn ($record): string => TaxRateResource::getUrl('edit', ['record' => $record]))
            ->columns([
                TextColumn::make('tax_rate_name')
                    ->label('Name')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('tax_rate_percent')
                    ->label('Steuersatz')
                    ->numeric(decimalPlaces: 2)
                    ->suffix(' %')
                    ->sortable(),
                TextColumn::make('products_count')
                    ->label('Produkte')
                    ->counts('products'),
            ])
            ->toolbarActions([
                CreateAction::make()
                    ->label('Steuersatz')
                    ->icon(Heroicon::Plus)
                    ->url(TaxRateResource::getUrl('create')),
            ]);
    }
}

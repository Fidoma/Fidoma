<?php

namespace App\Features\TaxRates\Filament\Resources\TaxRates\Tables;

use App\Features\TaxRates\Filament\Resources\TaxRates\TaxRateResource;
use Filament\Actions\CreateAction;
use Filament\Forms\Components\TextInput;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

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
            ->filters([
                Filter::make('tax_rate_name')
                    ->label('Name')
                    ->schema([TextInput::make('value')->label('Name')])
                    ->query(fn (Builder $query, array $data): Builder => $query->when(
                        filled($data['value'] ?? null),
                        fn (Builder $query): Builder => $query->where('tax_rate_name', 'like', '%'.trim((string) $data['value']).'%'),
                    )),
            ])
            ->toolbarActions([
                CreateAction::make()
                    ->label('Steuersatz')
                    ->icon(Heroicon::Plus)
                    ->url(TaxRateResource::getUrl('create')),
            ]);
    }
}

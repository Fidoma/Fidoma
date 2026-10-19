<?php

namespace App\Features\Units\Filament\Resources\Units\Tables;

use App\Features\Units\Filament\Resources\Units\UnitResource;
use Filament\Actions\CreateAction;
use Filament\Forms\Components\TextInput;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class UnitsTable
{
    public static function configure(Table $table): Table
    {
        return $table->recordUrl(fn ($record): string => UnitResource::getUrl('edit', ['record' => $record]))->columns([
            TextColumn::make('unit_name')->label('Einzahl')->searchable()->sortable(),
            TextColumn::make('unit_name_plrl')->label('Mehrzahl')->searchable(),
            TextColumn::make('products_count')->label('Produkte')->counts('products'),
        ])->filters([
            Filter::make('unit_name')
                ->label('Einheit')
                ->schema([TextInput::make('value')->label('Einheit')])
                ->query(fn (Builder $query, array $data): Builder => $query->when(
                    filled($data['value'] ?? null),
                    fn (Builder $query): Builder => $query->where('unit_name', 'like', '%'.trim((string) $data['value']).'%'),
                )),
        ])->toolbarActions([
            CreateAction::make()
                ->label('Einheit')
                ->icon(Heroicon::Plus)
                ->url(UnitResource::getUrl('create')),
        ]);
    }
}

<?php

namespace App\Features\Families\Filament\Resources\Families\Tables;

use App\Features\Families\Filament\Resources\Families\FamilyResource;
use Filament\Actions\CreateAction;
use Filament\Forms\Components\TextInput;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class FamiliesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->recordUrl(fn ($record): string => FamilyResource::getUrl('edit', ['record' => $record]))
            ->columns([
                TextColumn::make('family_name')
                    ->label('Name')
                    ->placeholder('Ohne Namen')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('products_count')
                    ->label('Produkte')
                    ->counts('products'),
            ])
            ->filters([
                Filter::make('family_name')
                    ->label('Familie')
                    ->schema([TextInput::make('value')->label('Familie')])
                    ->query(fn (Builder $query, array $data): Builder => $query->when(
                        filled($data['value'] ?? null),
                        fn (Builder $query): Builder => $query->where('family_name', 'like', '%'.trim((string) $data['value']).'%'),
                    )),
            ])
            ->toolbarActions([
                CreateAction::make()
                    ->label('Produktfamilie')
                    ->icon(Heroicon::Plus)
                    ->url(FamilyResource::getUrl('create')),
            ]);
    }
}

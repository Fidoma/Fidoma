<?php

namespace App\Features\Families\Filament\Resources\Families\Tables;

use App\Features\Families\Filament\Resources\Families\FamilyResource;
use Filament\Actions\CreateAction;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

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
            ->toolbarActions([
                CreateAction::make()
                    ->label('Produktfamilie')
                    ->icon(Heroicon::Plus)
                    ->url(FamilyResource::getUrl('create')),
            ]);
    }
}

<?php

namespace App\Features\Users\Filament\Resources\Users\Tables;

use App\Features\Users\Filament\Resources\Users\UserResource;
use Filament\Actions\CreateAction;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class UsersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('name')
            ->recordUrl(fn ($record): string => UserResource::getUrl('edit', ['record' => $record]))
            ->columns([
                TextColumn::make('name')
                    ->label('Name')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('company_name')
                    ->label('Firma')
                    ->placeholder('–')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('email')
                    ->label('E-Mail-Adresse')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('city')
                    ->label('Ort')
                    ->placeholder('–')
                    ->searchable()
                    ->sortable(),
            ])
            ->toolbarActions([
                CreateAction::make()
                    ->label('Benutzer')
                    ->icon(Heroicon::Plus)
                    ->url(UserResource::getUrl('create')),
            ]);
    }
}

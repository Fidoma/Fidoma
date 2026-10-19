<?php

namespace App\Features\Users\Filament\Resources\Users\Tables;

use App\Features\Users\Filament\Resources\Users\UserResource;
use Filament\Actions\CreateAction;
use Filament\Forms\Components\TextInput;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

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
            ->filters([
                Filter::make('name')
                    ->label('Name')
                    ->schema([TextInput::make('value')->label('Name')])
                    ->query(fn (Builder $query, array $data): Builder => $query->when(
                        filled($data['value'] ?? null),
                        fn (Builder $query): Builder => $query->where('name', 'like', '%'.trim((string) $data['value']).'%'),
                    )),
            ])
            ->toolbarActions([
                CreateAction::make()
                    ->label('Benutzer')
                    ->icon(Heroicon::Plus)
                    ->url(UserResource::getUrl('create')),
            ]);
    }
}

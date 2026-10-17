<?php

namespace App\Features\Contracts\Filament\Resources\ContractTemplates\Tables;

use App\Features\Contracts\Filament\Resources\ContractTemplates\ContractTemplateResource;
use App\Features\Contracts\Models\ContractTemplate;
use Filament\Actions\ActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ContractTemplatesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('updated_at', 'desc')
            ->recordUrl(fn ($record): string => ContractTemplateResource::getUrl('edit', ['record' => $record]))
            ->columns([
                TextColumn::make('name')->label('Vorlagentitel')->searchable()->sortable(),
                TextColumn::make('status')->label('Status')->badge()
                    ->formatStateUsing(fn (string $state): string => $state === 'active' ? 'Aktiv' : 'Entwurf')
                    ->color(fn (string $state): string => $state === 'active' ? 'success' : 'gray'),
                TextColumn::make('version')->label('Version')->sortable(),
                TextColumn::make('updated_at')->label('Geändert')->dateTime('d.m.Y H:i')->sortable(),
            ])
            ->toolbarActions([
                CreateAction::make()->label('Vertragsvorlage')->icon(Heroicon::Plus),
            ])
            ->recordActions([
                ActionGroup::make([
                    EditAction::make(),
                    DeleteAction::make()
                        ->disabled(fn (ContractTemplate $record): bool => $record->contracts()->exists())
                        ->tooltip(fn (ContractTemplate $record): ?string => $record->contracts()->exists()
                            ? 'Verwendete Vorlagen können nicht gelöscht werden.'
                            : null),
                ])->iconButton()->icon(Heroicon::OutlinedEllipsisVertical)->color('gray'),
            ]);
    }
}

<?php

namespace App\Features\Contracts\Filament\Resources\Contracts\Tables;

use App\Features\Contracts\Filament\Resources\Contracts\ContractResource;
use Filament\Actions\ActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ContractsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('contract_create_date', 'desc')
            ->recordUrl(fn ($record): string => ContractResource::getUrl('view', ['record' => $record]))
            ->columns([
                TextColumn::make('number')->label('Vertragsnummer')->searchable()->sortable(),
                TextColumn::make('client.client_name')->label('Kunde')->searchable()->sortable(),
                TextColumn::make('template.name')->label('Vorlage')->searchable()->sortable(),
                TextColumn::make('contract_start_date')->label('Beginn')->date('d.m.Y')->sortable(),
                TextColumn::make('contract_end_date')->label('Ende')->date('d.m.Y')->placeholder('Unbefristet')->sortable(),
                TextColumn::make('status')->label('Status')->badge()
                    ->formatStateUsing(fn (string $state): string => $state === 'draft' ? 'Entwurf' : $state),
            ])
            ->toolbarActions([
                CreateAction::make()->label('Vertrag')->icon(Heroicon::Plus),
            ])
            ->recordActions([
                ActionGroup::make([
                    ViewAction::make(),
                    EditAction::make(),
                    DeleteAction::make(),
                ])->iconButton()->icon(Heroicon::OutlinedEllipsisVertical)->color('gray'),
            ]);
    }
}

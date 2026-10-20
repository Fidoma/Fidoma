<?php

namespace App\Features\Terminals\Filament\Resources\PaymentTerminals\Tables;

use App\Features\Terminals\Filament\Resources\PaymentTerminals\PaymentTerminalResource;
use Filament\Actions\CreateAction;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

final class PaymentTerminalsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->recordUrl(fn ($record): string => PaymentTerminalResource::getUrl('edit', ['record' => $record]))
            ->columns([
                TextColumn::make('name')->label(__('terminals::messages.fields.name'))->searchable()->sortable(),
                TextColumn::make('manufacturer')->label(__('terminals::messages.fields.device'))->formatStateUsing(fn ($record): string => trim(($record->manufacturer ?: '–').' '.($record->model ?: ''))),
                TextColumn::make('host')->label(__('terminals::messages.fields.host'))->formatStateUsing(fn ($record): string => $record->host.':'.$record->port),
                TextColumn::make('connection_status')->label(__('terminals::messages.fields.connection_status'))->badge()->formatStateUsing(fn ($state): string => $state->label())->color(fn ($state): string => $state->color()),
                TextColumn::make('operating_status')->label(__('terminals::messages.fields.operating_status'))->badge()->formatStateUsing(fn ($state): string => $state->label()),
                IconColumn::make('is_enabled')->label(__('terminals::messages.fields.enabled'))->boolean(),
            ])
            ->toolbarActions([
                CreateAction::make()->label(__('terminals::messages.management.add'))->icon(Heroicon::Plus)->url(PaymentTerminalResource::getUrl('create')),
            ])
            ->emptyStateHeading(__('terminals::messages.management.empty'))
            ->emptyStateDescription(__('terminals::messages.management.empty_description'))
            ->emptyStateIcon(Heroicon::OutlinedCreditCard);
    }
}

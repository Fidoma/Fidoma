<?php

namespace App\Features\Quotes\Filament\Resources\Quotes\Tables;

use App\Features\Quotes\Actions\ArchiveQuote;
use App\Features\Quotes\Enums\QuoteStatus;
use App\Features\Quotes\Filament\Resources\Quotes\QuoteResource;
use App\Features\Quotes\Models\Quote;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\EditAction;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class QuotesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('quote_date_created', 'desc')
            ->columns([
                TextColumn::make('quote_number')
                    ->label('Angebotsnummer')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('client.client_name')
                    ->label('Kunde')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('quote_date_created')
                    ->label('Datum')
                    ->date('d.m.Y')
                    ->sortable(),
                TextColumn::make('quote_date_expires')
                    ->label('Gültig bis')
                    ->date('d.m.Y')
                    ->sortable(),
                TextColumn::make('quote_status_id')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn (QuoteStatus $state): string => $state->label())
                    ->color(fn (QuoteStatus $state): string => $state->color()),
                TextColumn::make('amount.quote_item_subtotal')
                    ->label('Netto')
                    ->money('EUR', locale: 'de')
                    ->sortable(),
                TextColumn::make('amount.quote_total')
                    ->label('Gesamt')
                    ->money('EUR', locale: 'de')
                    ->sortable(),
            ])
            ->toolbarActions([
                CreateAction::make()
                    ->label('Angebot')
                    ->icon(Heroicon::Plus)
                    ->url(QuoteResource::getUrl('create')),
            ])
            ->recordActions([
                ActionGroup::make([
                    EditAction::make()
                        ->label('Bearbeiten')
                        ->icon(Heroicon::OutlinedPencilSquare),
                    Action::make('archive')
                        ->label('Archivieren')
                        ->icon(Heroicon::OutlinedArchiveBox)
                        ->color('danger')
                        ->requiresConfirmation()
                        ->modalHeading('Angebot archivieren')
                        ->modalDescription('Das Angebot und alle zugehörigen Positionen und Steuern bleiben vollständig erhalten.')
                        ->modalSubmitActionLabel('Archivieren')
                        ->visible(fn (Quote $record): bool => ! $record->is_archived)
                        ->action(function (Quote $record): void {
                            app(ArchiveQuote::class)->handle($record);

                            Notification::make()
                                ->success()
                                ->title('Angebot wurde archiviert.')
                                ->send();
                        }),
                ])
                    ->label('Optionen')
                    ->icon(Heroicon::OutlinedEllipsisVertical)
                    ->color('gray')
                    ->tooltip('Optionen')
                    ->iconButton(),
            ]);
    }
}

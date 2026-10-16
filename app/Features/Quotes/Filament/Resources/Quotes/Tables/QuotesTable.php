<?php

namespace App\Features\Quotes\Filament\Resources\Quotes\Tables;

use App\Features\Clients\Filament\Resources\Clients\ClientResource;
use App\Features\Invoices\Filament\Resources\Invoices\InvoiceResource;
use App\Features\Quotes\Actions\ArchiveQuote;
use App\Features\Quotes\Actions\ConvertQuoteToInvoice;
use App\Features\Quotes\Enums\QuoteStatus;
use App\Features\Quotes\Filament\Actions\QuoteEmailAction;
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
                    ->url(fn (Quote $record): string => QuoteResource::getUrl('edit', ['record' => $record]))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('client.client_name')
                    ->label('Kunde')
                    ->url(fn (Quote $record): string => ClientResource::getUrl('view', ['record' => $record->client_id]))
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
            ->recordUrl(null)
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
                    Action::make('invoice')
                        ->label(fn (Quote $record): string => $record->invoice_id ? 'Rechnung öffnen' : 'In Rechnung umwandeln')
                        ->icon(Heroicon::OutlinedDocumentText)
                        ->visible(fn (Quote $record): bool => ! $record->is_archived)
                        ->requiresConfirmation(fn (Quote $record): bool => ! (bool) $record->invoice_id)
                        ->modalHeading('Angebot in Rechnung umwandeln')
                        ->modalDescription('Alle Positionen, Steuern und Rabatte werden in einen neuen Rechnungsentwurf übernommen.')
                        ->action(function (Action $action, Quote $record): void {
                            $invoice = app(ConvertQuoteToInvoice::class)->handle($record);

                            Notification::make()
                                ->success()
                                ->title('Rechnungsentwurf ist verfügbar.')
                                ->send();

                            $action->redirect(InvoiceResource::getUrl('edit', ['record' => $invoice]));
                        }),
                    self::statusAction('markApproved', QuoteStatus::Approved, Heroicon::OutlinedCheckCircle),
                    self::statusAction('markRejected', QuoteStatus::Rejected, Heroicon::OutlinedXCircle),
                    self::statusAction('markCanceled', QuoteStatus::Canceled, Heroicon::OutlinedNoSymbol),
                    QuoteEmailAction::make(),
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

    private static function statusAction(string $name, QuoteStatus $status, Heroicon $icon): Action
    {
        return Action::make($name)
            ->label($status->label())
            ->icon($icon)
            ->color($status->color())
            ->visible(fn (Quote $record): bool => ! $record->is_archived
                && $record->quote_status_id !== $status)
            ->requiresConfirmation()
            ->modalHeading('Angebot als „'.$status->label().'“ markieren?')
            ->modalSubmitActionLabel('Status ändern')
            ->action(function (Quote $record) use ($status): void {
                $record->update(['quote_status_id' => $status]);

                Notification::make()
                    ->success()
                    ->title('Angebot wurde als „'.$status->label().'“ markiert.')
                    ->send();
            });
    }
}

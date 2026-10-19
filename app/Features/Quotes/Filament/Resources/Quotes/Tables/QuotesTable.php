<?php

namespace App\Features\Quotes\Filament\Resources\Quotes\Tables;

use App\Features\Clients\Filament\Resources\Clients\ClientResource;
use App\Features\Clients\Models\Client;
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
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

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
            ->filters([
                Filter::make('quote_number')
                    ->label('Angebotsnummer')
                    ->schema([
                        TextInput::make('value')
                            ->label('Angebotsnummer')
                            ->autocomplete('one-time-code'),
                    ])
                    ->query(fn (Builder $query, array $data): Builder => filled($data['value'] ?? null)
                        ? $query->where('quote_number', 'like', '%'.trim((string) $data['value']).'%')
                        : $query),
                SelectFilter::make('client_id')
                    ->label('Kunde')
                    ->options(fn (): array => Client::query()
                        ->orderBy('client_name')
                        ->pluck('client_name', 'client_id')
                        ->all())
                    ->searchable()
                    ->preload(),
                self::dateRangeFilter('quote_date_created', 'Angebotsdatum'),
                self::dateRangeFilter('quote_date_expires', 'Gültig bis'),
                self::amountRangeFilter('quote_item_subtotal', 'Netto', 'quote_item_subtotal'),
                self::amountRangeFilter('quote_total', 'Gesamt', 'quote_total'),
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

    private static function dateRangeFilter(string $name, string $label): Filter
    {
        return Filter::make($name)
            ->label($label)
            ->schema([
                DatePicker::make('from')->label('Von')->native(false),
                DatePicker::make('until')->label('Bis')->native(false),
            ])
            ->columns(2)
            ->query(fn (Builder $query, array $data): Builder => $query
                ->when($data['from'] ?? null, fn (Builder $query, string $date): Builder => $query->whereDate($name, '>=', $date))
                ->when($data['until'] ?? null, fn (Builder $query, string $date): Builder => $query->whereDate($name, '<=', $date)));
    }

    private static function amountRangeFilter(string $name, string $label, string $column): Filter
    {
        return Filter::make($name)
            ->label($label)
            ->schema([
                TextInput::make('from')->label('Von')->numeric()->prefix('€')->autocomplete('one-time-code'),
                TextInput::make('until')->label('Bis')->numeric()->prefix('€')->autocomplete('one-time-code'),
            ])
            ->columns(2)
            ->query(fn (Builder $query, array $data): Builder => $query
                ->when(
                    filled($data['from'] ?? null),
                    fn (Builder $query): Builder => $query->whereHas(
                        'amount',
                        fn (Builder $query): Builder => $query->where($column, '>=', (float) $data['from']),
                    ),
                )
                ->when(
                    filled($data['until'] ?? null),
                    fn (Builder $query): Builder => $query->whereHas(
                        'amount',
                        fn (Builder $query): Builder => $query->where($column, '<=', (float) $data['until']),
                    ),
                ));
    }
}

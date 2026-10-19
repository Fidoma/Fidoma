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
                    ->label(__('quotes::messages.fields.quote_number'))
                    ->url(fn (Quote $record): string => QuoteResource::getUrl('edit', ['record' => $record]))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('client.client_name')
                    ->label(__('quotes::messages.fields.client'))
                    ->url(fn (Quote $record): string => ClientResource::getUrl('view', ['record' => $record->client_id]))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('quote_date_created')
                    ->label(__('quotes::messages.fields.date'))
                    ->date('d.m.Y')
                    ->sortable(),
                TextColumn::make('quote_date_expires')
                    ->label(__('quotes::messages.fields.valid_until'))
                    ->date('d.m.Y')
                    ->sortable(),
                TextColumn::make('quote_status_id')
                    ->label(__('quotes::messages.fields.status'))
                    ->badge()
                    ->formatStateUsing(fn (QuoteStatus $state): string => $state->label())
                    ->color(fn (QuoteStatus $state): string => $state->color()),
                TextColumn::make('amount.quote_item_subtotal')
                    ->label(__('quotes::messages.fields.net'))
                    ->money('EUR', locale: app()->getLocale())
                    ->sortable(),
                TextColumn::make('amount.quote_total')
                    ->label(__('quotes::messages.fields.total'))
                    ->money('EUR', locale: app()->getLocale())
                    ->sortable(),
            ])
            ->filters([
                Filter::make('quote_number')
                    ->label(__('quotes::messages.fields.quote_number'))
                    ->schema([
                        TextInput::make('value')
                            ->label(__('quotes::messages.fields.quote_number'))
                            ->autocomplete('one-time-code'),
                    ])
                    ->query(fn (Builder $query, array $data): Builder => filled($data['value'] ?? null)
                        ? $query->where('quote_number', 'like', '%'.trim((string) $data['value']).'%')
                        : $query),
                SelectFilter::make('client_id')
                    ->label(__('quotes::messages.fields.client'))
                    ->options(fn (): array => Client::query()
                        ->orderBy('client_name')
                        ->pluck('client_name', 'client_id')
                        ->all())
                    ->searchable()
                    ->preload(),
                self::dateRangeFilter('quote_date_created', __('quotes::messages.fields.quote_date')),
                self::dateRangeFilter('quote_date_expires', __('quotes::messages.fields.valid_until')),
                self::amountRangeFilter('quote_item_subtotal', __('quotes::messages.fields.net'), 'quote_item_subtotal'),
                self::amountRangeFilter('quote_total', __('quotes::messages.fields.total'), 'quote_total'),
            ])
            ->recordUrl(null)
            ->toolbarActions([
                CreateAction::make()
                    ->label(__('quotes::messages.singular'))
                    ->icon(Heroicon::Plus)
                    ->url(QuoteResource::getUrl('create')),
            ])
            ->recordActions([
                ActionGroup::make([
                    EditAction::make()
                        ->label(__('quotes::messages.actions.edit'))
                        ->icon(Heroicon::OutlinedPencilSquare),
                    Action::make('invoice')
                        ->label(fn (Quote $record): string => $record->invoice_id
                            ? __('quotes::messages.actions.open_invoice')
                            : __('quotes::messages.actions.convert_invoice'))
                        ->icon(Heroicon::OutlinedDocumentText)
                        ->visible(fn (Quote $record): bool => ! $record->is_archived)
                        ->requiresConfirmation(fn (Quote $record): bool => ! (bool) $record->invoice_id)
                        ->modalHeading(__('quotes::messages.conversion.heading'))
                        ->modalDescription(__('quotes::messages.conversion.description'))
                        ->action(function (Action $action, Quote $record): void {
                            $invoice = app(ConvertQuoteToInvoice::class)->handle($record);

                            Notification::make()
                                ->success()
                                ->title(__('quotes::messages.conversion.success'))
                                ->send();

                            $action->redirect(InvoiceResource::getUrl('edit', ['record' => $invoice]));
                        }),
                    self::statusAction('markApproved', QuoteStatus::Approved, Heroicon::OutlinedCheckCircle),
                    self::statusAction('markRejected', QuoteStatus::Rejected, Heroicon::OutlinedXCircle),
                    self::statusAction('markCanceled', QuoteStatus::Canceled, Heroicon::OutlinedNoSymbol),
                    QuoteEmailAction::make(),
                    Action::make('archive')
                        ->label(__('quotes::messages.actions.archive'))
                        ->icon(Heroicon::OutlinedArchiveBox)
                        ->color('danger')
                        ->requiresConfirmation()
                        ->modalHeading(__('quotes::messages.archive.heading'))
                        ->modalDescription(__('quotes::messages.archive.description'))
                        ->modalSubmitActionLabel(__('quotes::messages.actions.archive'))
                        ->visible(fn (Quote $record): bool => ! $record->is_archived)
                        ->action(function (Quote $record): void {
                            app(ArchiveQuote::class)->handle($record);

                            Notification::make()
                                ->success()
                                ->title(__('quotes::messages.archive.success'))
                                ->send();
                        }),
                ])
                    ->label(__('quotes::messages.actions.options'))
                    ->icon(Heroicon::OutlinedEllipsisVertical)
                    ->color('gray')
                    ->tooltip(__('quotes::messages.actions.options'))
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
            ->modalHeading(__('quotes::messages.status.heading', ['status' => $status->label()]))
            ->modalSubmitActionLabel(__('quotes::messages.actions.change_status'))
            ->action(function (Quote $record) use ($status): void {
                $record->update(['quote_status_id' => $status]);

                Notification::make()
                    ->success()
                    ->title(__('quotes::messages.status.success', ['status' => $status->label()]))
                    ->send();
            });
    }

    private static function dateRangeFilter(string $name, string $label): Filter
    {
        return Filter::make($name)
            ->label($label)
            ->schema([
                DatePicker::make('from')->label(__('quotes::messages.fields.from'))->native(false),
                DatePicker::make('until')->label(__('quotes::messages.fields.until'))->native(false),
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
                TextInput::make('from')->label(__('quotes::messages.fields.from'))->numeric()->prefix('€')->autocomplete('one-time-code'),
                TextInput::make('until')->label(__('quotes::messages.fields.until'))->numeric()->prefix('€')->autocomplete('one-time-code'),
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

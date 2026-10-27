<?php

namespace App\Features\Reservations\Filament\Resources\Reservations\Tables;

use App\Features\Clients\Filament\Resources\Clients\ClientResource;
use App\Features\Invoices\Enums\InvoiceStatus;
use App\Features\Invoices\Filament\Resources\Invoices\InvoiceResource;
use App\Features\Reservations\Actions\ConvertReservationToInvoice;
use App\Features\Reservations\Enums\ReservationStatus;
use App\Features\Reservations\Models\Reservation;
use App\Features\Reservations\Models\Room;
use Carbon\CarbonImmutable;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Support\Enums\Width;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ReservationsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('reservation_date_created', 'desc')
            ->columns([
                TextColumn::make('reservation_number')
                    ->label('Reservierungsnummer')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('invoice.invoice_number')
                    ->label('Rechnung')
                    ->placeholder('Nicht berechnet')
                    ->url(fn (Reservation $record): ?string => $record->invoice_id
                        ? InvoiceResource::getUrl('edit', ['record' => $record->invoice_id])
                        : null),
                TextColumn::make('client.client_name')
                    ->label('Kunde')
                    ->url(fn (Reservation $record): string => ClientResource::getUrl('view', ['record' => $record->client_id]))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('arrival_date')
                    ->label('Anreise')
                    ->state(fn (Reservation $record) => $record->items->first()?->arrival_date)
                    ->date('d.m.Y')
                    ->placeholder('–'),
                TextColumn::make('departure_date')
                    ->label('Abreise')
                    ->state(fn (Reservation $record) => $record->items->first()?->departure_date)
                    ->date('d.m.Y')
                    ->placeholder('–'),
                TextColumn::make('arrival_in_days')
                    ->label(__('reservations::messages.table.arrival_in'))
                    ->state(function (Reservation $record): ?int {
                        $arrivalDate = $record->items->first()?->arrival_date;

                        if ($arrivalDate === null) {
                            return null;
                        }

                        return (int) CarbonImmutable::today()->diffInDays($arrivalDate, false);
                    })
                    ->formatStateUsing(fn (?int $state): ?string => $state === null ? null : match (true) {
                        $state === 0 => __('reservations::messages.table.arrival_today'),
                        $state === 1 => __('reservations::messages.table.arrival_future_one'),
                        $state > 1 => __('reservations::messages.table.arrival_future_many', ['count' => $state]),
                        $state === -1 => __('reservations::messages.table.arrival_past_one'),
                        default => __('reservations::messages.table.arrival_past_many', ['count' => abs($state)]),
                    })
                    ->badge()
                    ->color(fn (?int $state): string => match (true) {
                        $state > 0 => 'success',
                        $state < 0 => 'danger',
                        default => 'gray',
                    })
                    ->placeholder('–'),
                TextColumn::make('reservation_status_id')->label('Status')->badge()
                    ->formatStateUsing(fn (ReservationStatus $state): string => $state->label())
                    ->color(fn (ReservationStatus $state): string => $state->color()),
                TextColumn::make('amount.reservation_total')->label('Gesamt')->money('EUR', locale: 'de')->sortable(),
            ])
            ->filters([
                SelectFilter::make('reservation_status_id')
                    ->label('Status')
                    ->options(ReservationStatus::options()),
                Filter::make('stay_period')
                    ->label(__('reservations::messages.statistics.filters.stay_period'))
                    ->schema([
                        DatePicker::make('from')->label(__('reservations::messages.statistics.filters.from')),
                        DatePicker::make('until')->label(__('reservations::messages.statistics.filters.until')),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        if (blank($data['from'] ?? null) && blank($data['until'] ?? null)) {
                            return $query;
                        }

                        return $query->whereHas('items', fn (Builder $items): Builder => $items
                            ->when($data['from'] ?? null, fn (Builder $items, mixed $date): Builder => $items
                                ->whereDate('departure_date', '>', $date))
                            ->when($data['until'] ?? null, fn (Builder $items, mixed $date): Builder => $items
                                ->whereDate('arrival_date', '<=', $date)));
                    }),
                Filter::make('room_id')
                    ->label(__('reservations::messages.statistics.filters.room'))
                    ->schema([
                        Select::make('value')
                            ->label(__('reservations::messages.statistics.filters.room'))
                            ->options(fn (): array => Room::query()
                                ->orderByRaw('CAST(room_number AS UNSIGNED)')
                                ->orderBy('room_number')
                                ->get()
                                ->mapWithKeys(fn (Room $room): array => [$room->getKey() => $room->displayName()])
                                ->all())
                            ->searchable()
                            ->preload(),
                    ])
                    ->query(fn (Builder $query, array $data): Builder => $query->when(
                        $data['value'] ?? null,
                        fn (Builder $query, mixed $roomId): Builder => $query
                            ->whereHas('items', fn (Builder $items): Builder => $items->where('room_id', $roomId)),
                    )),
                SelectFilter::make('schedule')
                    ->label(__('reservations::messages.statistics.filters.schedule'))
                    ->options([
                        'today_arrivals' => __('reservations::messages.statistics.filters.today_arrivals'),
                        'today_departures' => __('reservations::messages.statistics.filters.today_departures'),
                        'today_movements' => __('reservations::messages.statistics.filters.today_movements'),
                        'future_arrivals' => __('reservations::messages.statistics.filters.future_arrivals'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        $value = $data['value'] ?? null;
                        $today = CarbonImmutable::today()->toDateString();

                        return match ($value) {
                            'today_arrivals' => $query->whereHas('items', fn (Builder $items): Builder => $items->whereDate('arrival_date', $today)),
                            'today_departures' => $query->whereHas('items', fn (Builder $items): Builder => $items->whereDate('departure_date', $today)),
                            'today_movements' => $query->whereHas('items', fn (Builder $items): Builder => $items
                                ->where(fn (Builder $dates): Builder => $dates
                                    ->whereDate('arrival_date', $today)
                                    ->orWhereDate('departure_date', $today))),
                            'future_arrivals' => $query->whereHas('items', fn (Builder $items): Builder => $items->whereDate('arrival_date', '>=', $today))
                                ->where('reservation_status_id', '!=', ReservationStatus::Canceled->value),
                            default => $query,
                        };
                    }),
                SelectFilter::make('billing')
                    ->label(__('reservations::messages.statistics.filters.billing'))
                    ->options([
                        'unbilled' => __('reservations::messages.statistics.filters.unbilled'),
                        'draft' => __('reservations::messages.statistics.filters.invoice_draft'),
                        'open' => __('reservations::messages.statistics.filters.invoice_open'),
                        'partial' => __('reservations::messages.statistics.filters.invoice_partial'),
                        'paid' => __('reservations::messages.statistics.filters.invoice_paid'),
                    ])
                    ->query(fn (Builder $query, array $data): Builder => match ($data['value'] ?? null) {
                        'unbilled' => $query
                            ->where('reservation_status_id', '!=', ReservationStatus::Canceled->value)
                            ->where(fn (Builder $query): Builder => $query
                                ->whereNull('invoice_id')
                                ->orWhere('invoice_id', 0)
                                ->orWhereHas('invoice', fn (Builder $invoice): Builder => $invoice
                                    ->where('invoice_status_id', InvoiceStatus::Draft->value))),
                        'draft' => $query->whereHas('invoice', fn (Builder $invoice): Builder => $invoice->where('invoice_status_id', InvoiceStatus::Draft->value)),
                        'open' => $query->whereHas('invoice', fn (Builder $invoice): Builder => $invoice->where('invoice_status_id', InvoiceStatus::Open->value)),
                        'partial' => $query->whereHas('invoice.amount', fn (Builder $amount): Builder => $amount->where('invoice_paid', '>', 0)->where('invoice_balance', '>', 0)),
                        'paid' => $query->whereHas('invoice', fn (Builder $invoice): Builder => $invoice->where('invoice_status_id', InvoiceStatus::Paid->value)),
                        default => $query,
                    }),
            ])
            ->recordUrl(null)
            ->recordAction('viewDetails')
            ->toolbarActions([
                CreateAction::make()->label('Reservierung')->icon(Heroicon::Plus),
            ])
            ->recordActions([
                Action::make('viewDetails')
                    ->label(__('reservations::messages.details.open'))
                    ->icon(Heroicon::OutlinedEye)
                    ->view('filament.actions.record-slide-over-trigger')
                    ->slideOver()
                    ->modalWidth(Width::FiveExtraLarge)
                    ->modalHeading(fn (Reservation $record): string => $record->reservation_number)
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel(__('reservations::messages.details.close'))
                    ->modalContent(fn (Reservation $record) => view('reservations::reservation-details-modal', [
                        'reservationId' => $record->getKey(),
                    ])),
                ActionGroup::make([
                    EditAction::make(),
                    Action::make('invoice')
                        ->label(fn (Reservation $record): string => $record->invoice_id ? 'Rechnung öffnen' : 'In Rechnung umwandeln')
                        ->icon(Heroicon::OutlinedDocumentText)
                        ->requiresConfirmation(fn (Reservation $record): bool => ! (bool) $record->invoice_id)
                        ->modalHeading('Reservierung in Rechnung umwandeln')
                        ->modalDescription('Alle Positionen, Steuern und Rabatte werden in einen neuen Rechnungsentwurf übernommen.')
                        ->action(function (Action $action, Reservation $record): void {
                            $invoice = app(ConvertReservationToInvoice::class)->handle($record);
                            Notification::make()->success()->title('Rechnungsentwurf ist verfügbar.')->send();
                            $action->redirect(InvoiceResource::getUrl('edit', ['record' => $invoice]));
                        }),
                    DeleteAction::make(),
                ])->iconButton()->icon(Heroicon::OutlinedEllipsisVertical)->color('gray'),
            ]);
    }
}

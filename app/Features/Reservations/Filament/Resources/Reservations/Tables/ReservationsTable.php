<?php

namespace App\Features\Reservations\Filament\Resources\Reservations\Tables;

use App\Features\Clients\Filament\Resources\Clients\ClientResource;
use App\Features\Invoices\Filament\Resources\Invoices\InvoiceResource;
use App\Features\Reservations\Actions\ConvertReservationToInvoice;
use App\Features\Reservations\Enums\ReservationStatus;
use App\Features\Reservations\Filament\Resources\Reservations\ReservationResource;
use App\Features\Reservations\Models\Reservation;
use Carbon\CarbonImmutable;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class ReservationsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('reservation_date_created', 'desc')
            ->columns([
                TextColumn::make('reservation_number')
                    ->label('Reservierungsnummer')
                    ->url(fn (Reservation $record): string => ReservationResource::getUrl('edit', ['record' => $record]))
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
            ])
            ->recordUrl(null)
            ->toolbarActions([
                CreateAction::make()->label('Reservierung')->icon(Heroicon::Plus),
            ])
            ->recordActions([
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

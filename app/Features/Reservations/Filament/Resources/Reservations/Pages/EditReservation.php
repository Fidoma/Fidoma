<?php

namespace App\Features\Reservations\Filament\Resources\Reservations\Pages;

use App\Features\Invoices\Filament\Resources\Invoices\InvoiceResource;
use App\Features\Reservations\Actions\ConvertReservationToInvoice;
use App\Features\Reservations\Actions\RecalculateReservation;
use App\Features\Reservations\Filament\Resources\Reservations\ReservationResource;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Filament\Support\Enums\Width;
use Filament\Support\Icons\Heroicon;

class EditReservation extends EditRecord
{
    protected static string $resource = ReservationResource::class;

    protected Width|string|null $maxContentWidth = Width::Full;

    protected function afterSave(): void
    {
        app(RecalculateReservation::class)->handle($this->record);
    }

    /** @return array<Action> */
    protected function getHeaderActions(): array
    {
        return [
            Action::make('invoice')
                ->label(fn (): string => $this->record->invoice_id ? 'Rechnung öffnen' : 'In Rechnung umwandeln')
                ->icon(Heroicon::OutlinedDocumentText)
                ->requiresConfirmation(fn (): bool => ! (bool) $this->record->invoice_id)
                ->action(function (Action $action): void {
                    $invoice = app(ConvertReservationToInvoice::class)->handle($this->record);
                    Notification::make()->success()->title('Rechnungsentwurf ist verfügbar.')->send();
                    $action->redirect(InvoiceResource::getUrl('edit', ['record' => $invoice]));
                }),
        ];
    }
}

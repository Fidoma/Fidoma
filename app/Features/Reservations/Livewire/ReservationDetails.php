<?php

namespace App\Features\Reservations\Livewire;

use App\Features\Invoices\Filament\Resources\Invoices\InvoiceResource;
use App\Features\Reservations\Actions\ConvertReservationToInvoice;
use App\Features\Reservations\Actions\UpdateReservationStatus;
use App\Features\Reservations\Enums\ReservationStatus;
use App\Features\Reservations\Filament\Resources\Reservations\ReservationResource;
use App\Features\Reservations\Models\Reservation;
use Carbon\CarbonInterface;
use Filament\Actions\Action;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Notifications\Notification;
use Filament\Schemas\Concerns\InteractsWithSchemas;
use Filament\Schemas\Contracts\HasSchemas;
use Filament\Support\Icons\Heroicon;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class ReservationDetails extends Component implements HasActions, HasSchemas
{
    use InteractsWithActions;
    use InteractsWithSchemas;

    public Reservation $reservation;

    public int $reservationStatus;

    public function mount(int $reservationId): void
    {
        $reservation = Reservation::query()->findOrFail($reservationId);
        abort_unless(ReservationResource::canView($reservation), 403);

        $this->reservation = $reservation;
        $this->refreshReservation();
    }

    public function createInvoiceAction(): Action
    {
        return Action::make('createInvoice')
            ->label(__('reservations::messages.details.create_invoice'))
            ->icon(Heroicon::OutlinedDocumentPlus)
            ->color('primary')
            ->visible(fn (): bool => ! (bool) $this->reservation->invoice_id
                && ReservationResource::canEdit($this->reservation))
            ->requiresConfirmation()
            ->modalHeading(__('reservations::messages.details.create_invoice'))
            ->modalDescription(__('reservations::messages.details.create_invoice_description'))
            ->modalSubmitActionLabel(__('reservations::messages.details.create_invoice'))
            ->action(function (Action $action): void {
                $invoice = app(ConvertReservationToInvoice::class)->handle($this->reservation);
                $this->refreshReservation();

                Notification::make()
                    ->success()
                    ->title(__('reservations::messages.details.invoice_created'))
                    ->send();

                $action->redirect(InvoiceResource::getUrl('edit', ['record' => $invoice]));
            });
    }

    public function deleteReservationAction(): Action
    {
        return Action::make('deleteReservation')
            ->label(__('reservations::messages.details.delete'))
            ->icon(Heroicon::OutlinedTrash)
            ->color('danger')
            ->visible(fn (): bool => ReservationResource::canDelete($this->reservation))
            ->requiresConfirmation()
            ->modalHeading(__('reservations::messages.details.delete_heading'))
            ->modalDescription(__('reservations::messages.details.delete_description'))
            ->modalSubmitActionLabel(__('reservations::messages.details.delete'))
            ->action(function (): void {
                $this->reservation->delete();

                Notification::make()
                    ->success()
                    ->title(__('reservations::messages.details.deleted'))
                    ->send();

                $this->redirect(ReservationResource::getUrl('index'));
            });
    }

    public function refreshReservation(): void
    {
        $this->reservation->refresh()->load([
            'amount',
            'client',
            'invoice.amount',
            'items.amount',
            'items.room',
        ]);
        $this->reservationStatus = $this->reservation->reservation_status_id->value;
    }

    public function updatedReservationStatus(mixed $status): void
    {
        abort_unless(ReservationResource::canEdit($this->reservation), 403);

        $reservationStatus = ReservationStatus::tryFrom((int) $status);

        if ($reservationStatus === null) {
            $this->reservationStatus = $this->reservation->reservation_status_id->value;

            return;
        }

        app(UpdateReservationStatus::class)->handle($this->reservation, $reservationStatus);
        $this->refreshReservation();

        Notification::make()
            ->success()
            ->title(__('reservations::messages.details.status_updated', ['status' => $reservationStatus->label()]))
            ->send();
    }

    public function arrivalDate(): ?CarbonInterface
    {
        return $this->reservation->items
            ->pluck('arrival_date')
            ->filter()
            ->sort()
            ->first();
    }

    public function departureDate(): ?CarbonInterface
    {
        return $this->reservation->items
            ->pluck('departure_date')
            ->filter()
            ->sortDesc()
            ->first();
    }

    public function nights(): ?int
    {
        $arrival = $this->arrivalDate();
        $departure = $this->departureDate();

        if ($arrival === null || $departure === null) {
            return null;
        }

        return (int) $arrival->diffInDays($departure);
    }

    public function render(): View
    {
        return view('reservations::reservation-details');
    }
}

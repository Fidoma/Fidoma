<?php

namespace App\Features\Reservations\Filament\Resources\Reservations\Pages;

use App\Features\Reservations\Enums\ReservationStatus;
use App\Features\Reservations\Filament\Resources\Reservations\ReservationResource;
use App\Features\Reservations\Models\ReservationItem;
use App\Features\Reservations\Models\Room;
use Carbon\CarbonImmutable;
use Filament\Actions\Action;
use Filament\Resources\Pages\Page;
use Filament\Support\Enums\Width;
use Filament\Support\Icons\Heroicon;

class ReservationSchedule extends Page
{
    protected static string $resource = ReservationResource::class;

    protected string $view = 'filament.resources.reservations.pages.reservation-schedule';

    protected static ?string $title = 'Belegungsplan';

    protected Width|string|null $maxContentWidth = Width::Full;

    public function createAction(): Action
    {
        return Action::make('create')
            ->label('Reservierung')
            ->icon(Heroicon::Plus)
            ->url(ReservationResource::getUrl('create'));
    }

    public function listAction(): Action
    {
        return Action::make('list')
            ->label('Liste')
            ->icon(Heroicon::OutlinedListBullet)
            ->color('gray')
            ->url(ReservationResource::getUrl());
    }

    /** @return array{startDate: string, days: int, resources: array<int, array<string, mixed>>, events: array<int, array<string, mixed>>, createUrl: string} */
    public function schedule(): array
    {
        $defaultStart = CarbonImmutable::today()->subDays(32);
        $start = $this->dateFromQuery('start') ?? $defaultStart;
        $end = $this->dateFromQuery('end') ?? $start->addDays(90);

        if (! $end->isAfter($start) || $start->diffInDays($end) > 366) {
            $start = $defaultStart;
            $end = $start->addDays(90);
        }

        $resources = Room::query()->orderBy('room_number')->get()
            ->map(fn (Room $room): array => ['id' => 'room-'.$room->getKey(), 'name' => $room->displayName()])
            ->values()->all();

        $events = ReservationItem::query()
            ->with(['room', 'reservation.client', 'reservation.invoice'])
            ->whereDate('arrival_date', '<', $end->toDateString())
            ->whereDate('departure_date', '>', $start->toDateString())
            ->whereHas('room')
            ->get()
            ->map(function (ReservationItem $item): array {
                $reservation = $item->reservation;
                $status = $reservation->reservation_status_id;
                $invoiceStatus = $reservation->invoice?->invoice_status_id?->value ?? 0;
                $billing = match ($invoiceStatus) {
                    1 => ['label' => 'Entwurf', 'class' => 'open'],
                    2 => ['label' => 'Versendet', 'class' => 'open'],
                    4 => ['label' => 'Bezahlt', 'class' => 'paid'],
                    default => ['label' => 'Nicht berechnet', 'class' => 'open'],
                };
                $colors = match ($status) {
                    ReservationStatus::Confirmed => ['background' => '#f1f3f5', 'border' => '#22c55e', 'font' => '#14532d'],
                    ReservationStatus::Canceled => ['background' => '#f1f3f5', 'border' => '#ef4444', 'font' => '#7f1d1d'],
                    default => ['background' => '#f1f3f5', 'border' => '#cbd5e1', 'font' => '#334155'],
                };

                return [
                    'id' => 'reservation-item-'.$item->getKey(),
                    'reservationId' => $reservation->getKey(),
                    'resource' => 'room-'.$item->room_id,
                    'start' => $item->arrival_date->format('Y-m-d').'T14:00:00',
                    'end' => $item->departure_date->format('Y-m-d').'T11:00:00',
                    'text' => $reservation->client?->client_name ?: $reservation->client?->client_company ?: 'Unbekannter Gast',
                    'status' => $status->label(),
                    'billingStatus' => $billing['label'],
                    'billingClass' => $billing['class'],
                    'invoiceNumber' => $reservation->invoice?->invoice_number,
                    'backColor' => $colors['background'],
                    'borderColor' => $colors['border'],
                    'fontColor' => $colors['font'],
                    'editUrl' => ReservationResource::getUrl('edit', ['record' => $reservation]),
                ];
            })->values()->all();

        return [
            'startDate' => $start->toDateString(),
            'days' => (int) $start->diffInDays($end),
            'resources' => $resources,
            'events' => $events,
            'createUrl' => ReservationResource::getUrl('create'),
        ];
    }

    private function dateFromQuery(string $key): ?CarbonImmutable
    {
        $value = request()->query($key);

        if (! is_string($value) || ! preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
            return null;
        }

        try {
            $date = CarbonImmutable::createFromFormat('!Y-m-d', $value);
        } catch (\Throwable) {
            return null;
        }

        return $date?->format('Y-m-d') === $value ? $date : null;
    }
}

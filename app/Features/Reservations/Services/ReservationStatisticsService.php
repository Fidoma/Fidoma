<?php

namespace App\Features\Reservations\Services;

use App\Features\Invoices\Enums\InvoiceStatus;
use App\Features\Invoices\Models\Invoice;
use App\Features\Invoices\Models\InvoiceAmount;
use App\Features\Payments\Models\Payment;
use App\Features\Reservations\Data\ReservationStatisticsFilter;
use App\Features\Reservations\Enums\ReservationStatus;
use App\Features\Reservations\Models\Reservation;
use App\Features\Reservations\Models\ReservationAmount;
use App\Features\Reservations\Models\ReservationItem;
use App\Features\Reservations\Models\Room;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

final class ReservationStatisticsService
{
    /**
     * @return array{
     *   reservations: int, status_counts: array<int, int>, invoice_status_counts: array<int, int>, future_arrivals: int,
     *   future_confirmed_arrivals: int, future_open_arrivals: int,
     *   arrivals_today: int, departures_today: int, unbilled: int,
     *   occupied_room_nights: int, actual_room_nights: int, forecast_room_nights: int,
     *   confirmed_forecast_room_nights: int, open_forecast_room_nights: int,
     *   available_room_nights: int, unused_room_nights: int, occupancy_rate: float,
     *   reservation_value: float, invoiced_value: float, payments_received: float,
     *   partially_paid: int, conflicts: int
     * }
     */
    public function summary(ReservationStatisticsFilter $filter): array
    {
        $reservationQuery = $this->reservationsOverlapping($filter);
        $reservationIds = (clone $reservationQuery)->select('ip_reservations.reservation_id');
        $statusCounts = (clone $reservationQuery)
            ->selectRaw('reservation_status_id, COUNT(*) AS aggregate')
            ->groupBy('reservation_status_id')
            ->pluck('aggregate', 'reservation_status_id')
            ->map(fn (mixed $count): int => (int) $count)
            ->all();
        $items = $this->occupancyItems($filter);
        $today = CarbonImmutable::today();
        $todayIsInPeriod = $today->greaterThanOrEqualTo($filter->start) && $today->lessThan($filter->end);
        $futureStart = $today->greaterThan($filter->start) ? $today : $filter->start;
        $futureItems = $futureStart->lessThan($filter->end)
            ? $items->filter(fn (ReservationItem $item): bool => $item->departure_date->greaterThan($futureStart))
            : collect();
        $occupied = $this->occupiedRoomNights($items, $filter->start, $filter->end);
        $actualEnd = $today->lessThan($filter->end) ? $today : $filter->end;
        $actual = $actualEnd->greaterThan($filter->start)
            ? $this->occupiedRoomNights($items, $filter->start, $actualEnd)
            : 0;
        $forecast = $futureStart->lessThan($filter->end)
            ? $this->occupiedRoomNights($futureItems, $futureStart, $filter->end)
            : 0;
        $confirmedForecast = $futureStart->lessThan($filter->end)
            ? $this->occupiedRoomNights(
                $futureItems->filter(fn (ReservationItem $item): bool => $item->reservation->reservation_status_id === ReservationStatus::Confirmed),
                $futureStart,
                $filter->end,
            )
            : 0;
        $openForecast = $futureStart->lessThan($filter->end)
            ? $this->occupiedRoomNights(
                $futureItems->filter(fn (ReservationItem $item): bool => $item->reservation->reservation_status_id === ReservationStatus::Open),
                $futureStart,
                $filter->end,
            )
            : 0;
        $available = $this->availableRoomNights($filter);
        $invoiceIds = Reservation::query()
            ->whereIn('reservation_id', $reservationIds)
            ->where('invoice_id', '>', 0)
            ->select('invoice_id');
        $unbilledQuery = clone $reservationQuery;

        if ($filter->status === null) {
            $unbilledQuery->where('reservation_status_id', '!=', ReservationStatus::Canceled->value);
        }

        return [
            'reservations' => (clone $reservationQuery)->count(),
            'status_counts' => $statusCounts,
            'invoice_status_counts' => Invoice::query()
                ->whereIn('invoice_id', (clone $invoiceIds))
                ->selectRaw('invoice_status_id, COUNT(*) AS aggregate')
                ->groupBy('invoice_status_id')
                ->pluck('aggregate', 'invoice_status_id')
                ->map(fn (mixed $count): int => (int) $count)
                ->all(),
            'future_arrivals' => $this->arrivalCount($filter, $futureStart, $filter->end),
            'future_confirmed_arrivals' => $this->arrivalCount($filter, $futureStart, $filter->end, ReservationStatus::Confirmed),
            'future_open_arrivals' => $this->arrivalCount($filter, $futureStart, $filter->end, ReservationStatus::Open),
            'arrivals_today' => $todayIsInPeriod ? $this->movementCount($filter, 'arrival_date', $today) : 0,
            'departures_today' => $todayIsInPeriod ? $this->movementCount($filter, 'departure_date', $today) : 0,
            'unbilled' => $unbilledQuery->where(fn (Builder $query): Builder => $query
                ->whereNull('invoice_id')
                ->orWhere('invoice_id', 0)
                ->orWhereHas('invoice', fn (Builder $invoice): Builder => $invoice
                    ->where('invoice_status_id', InvoiceStatus::Draft->value)))->count(),
            'occupied_room_nights' => $occupied,
            'actual_room_nights' => $actual,
            'forecast_room_nights' => $forecast,
            'confirmed_forecast_room_nights' => $confirmedForecast,
            'open_forecast_room_nights' => $openForecast,
            'available_room_nights' => $available,
            'unused_room_nights' => max(0, $available - $occupied),
            'occupancy_rate' => $available > 0 ? round(min(100, ($occupied / $available) * 100), 1) : 0.0,
            'reservation_value' => round((float) ReservationAmount::query()
                ->whereIn('reservation_id', $reservationIds)
                ->sum('reservation_total'), 2),
            'invoiced_value' => round((float) InvoiceAmount::query()
                ->whereIn('invoice_id', (clone $invoiceIds))
                ->whereHas('invoice', fn (Builder $query): Builder => $query
                    ->where('invoice_status_id', '!=', InvoiceStatus::Draft->value))
                ->sum('invoice_total'), 2),
            'payments_received' => round((float) Payment::query()
                ->whereIn('invoice_id', (clone $invoiceIds))
                ->whereDate('payment_date', '>=', $filter->start->toDateString())
                ->whereDate('payment_date', '<', $filter->end->toDateString())
                ->sum('payment_amount'), 2),
            'partially_paid' => InvoiceAmount::query()
                ->whereIn('invoice_id', (clone $invoiceIds))
                ->where('invoice_paid', '>', 0)
                ->where('invoice_balance', '>', 0)
                ->count(),
            'conflicts' => $this->conflictsFromItems($items)->count(),
        ];
    }

    /**
     * @return array{labels: list<string>, reservations: list<int>, arrivals: list<int>, occupancy: list<float>, previous_year_occupancy: list<float>, reservation_values: list<float>, invoice_values: list<float>, payments: list<float>}
     */
    public function monthlySeries(ReservationStatisticsFilter $filter): array
    {
        $months = $this->months($filter);
        $occupancyItems = $this->occupancyItems($filter);
        $previousYearFilter = $filter->previousYear();
        $previousYearItems = $this->occupancyItems($previousYearFilter);
        $reservationValues = $this->reservationValuesByMonth($filter);
        $invoiceValues = $this->invoiceValuesByMonth($filter);
        $paymentValues = $this->paymentValuesByMonth($filter);

        $reservations = $this->reservationsByMonth($filter);
        $arrivals = $this->arrivalsByMonth($filter);
        $occupancy = [];
        $previousYearOccupancy = [];

        foreach ($months as $month) {
            $monthStart = $month->greaterThan($filter->start) ? $month : $filter->start;
            $monthEnd = $month->addMonth()->lessThan($filter->end) ? $month->addMonth() : $filter->end;
            $monthFilter = $filter->between($monthStart, $monthEnd);
            $available = $this->availableRoomNights($monthFilter);
            $occupied = $this->occupiedRoomNights($occupancyItems, $monthStart, $monthEnd);
            $occupancy[] = $available > 0 ? round(min(100, ($occupied / $available) * 100), 1) : 0.0;

            $previousStart = $monthStart->subYear();
            $previousEnd = $monthEnd->subYear();
            $previousAvailable = $this->availableRoomNights($filter->between($previousStart, $previousEnd));
            $previousOccupied = $this->occupiedRoomNights($previousYearItems, $previousStart, $previousEnd);
            $previousYearOccupancy[] = $previousAvailable > 0
                ? round(min(100, ($previousOccupied / $previousAvailable) * 100), 1)
                : 0.0;
        }

        return [
            'labels' => $months->map(fn (CarbonImmutable $month): string => $month->translatedFormat('M Y'))->all(),
            'reservations' => $months->map(fn (CarbonImmutable $month): int => $reservations[$month->format('Y-m')] ?? 0)->all(),
            'arrivals' => $months->map(fn (CarbonImmutable $month): int => $arrivals[$month->format('Y-m')] ?? 0)->all(),
            'occupancy' => $occupancy,
            'previous_year_occupancy' => $previousYearOccupancy,
            'reservation_values' => $months->map(fn (CarbonImmutable $month): float => $reservationValues[$month->format('Y-m')] ?? 0.0)->all(),
            'invoice_values' => $months->map(fn (CarbonImmutable $month): float => $invoiceValues[$month->format('Y-m')] ?? 0.0)->all(),
            'payments' => $months->map(fn (CarbonImmutable $month): float => $paymentValues[$month->format('Y-m')] ?? 0.0)->all(),
        ];
    }

    /** @return Collection<int, array{room_id: int, room: string, active: bool, available: int, occupied: int, unused: int, occupancy_rate: float, conflicts: int}> */
    public function roomMetrics(ReservationStatisticsFilter $filter): Collection
    {
        $rooms = Room::query()
            ->when($filter->roomId, fn (Builder $query, int $roomId): Builder => $query->whereKey($roomId))
            ->orderByRaw('CAST(room_number AS UNSIGNED)')
            ->orderBy('room_number')
            ->get();
        $items = $this->occupancyItems($filter);
        $conflicts = $this->conflictsFromItems($items)->groupBy('room_id');

        return $rooms->map(function (Room $room) use ($filter, $items, $conflicts): array {
            $roomItems = $items->where('room_id', $room->getKey());
            $available = $room->room_active ? $filter->days() : 0;
            $occupied = $this->occupiedRoomNights($roomItems, $filter->start, $filter->end);

            return [
                'room_id' => (int) $room->getKey(),
                'room' => $room->displayName(),
                'active' => $room->room_active,
                'available' => $available,
                'occupied' => $occupied,
                'unused' => max(0, $available - $occupied),
                'occupancy_rate' => $available > 0 ? round(min(100, ($occupied / $available) * 100), 1) : 0.0,
                'conflicts' => $conflicts->get($room->getKey(), collect())->count(),
            ];
        })->sortByDesc('occupied')->values();
    }

    /** @return Collection<int, array{room_id: int, room: string, first_item_id: int, second_item_id: int, starts_on: string, ends_on: string}> */
    public function conflicts(ReservationStatisticsFilter $filter): Collection
    {
        return $this->conflictsFromItems($this->occupancyItems($filter));
    }

    /**
     * The returned item intervals use an inclusive arrival and exclusive departure boundary.
     * This method is also the shared source for the official tourism-statistics prefill.
     *
     * @return Collection<int, ReservationItem>
     */
    public function occupancyItems(ReservationStatisticsFilter $filter): Collection
    {
        return ReservationItem::query()
            ->with(['reservation.client', 'room'])
            ->whereNotNull('room_id')
            ->whereNotNull('arrival_date')
            ->whereNotNull('departure_date')
            ->whereDate('arrival_date', '<', $filter->end->toDateString())
            ->whereDate('departure_date', '>', $filter->start->toDateString())
            ->when($filter->roomId, fn (Builder $query, int $roomId): Builder => $query->where('room_id', $roomId))
            ->whereHas('reservation', function (Builder $query) use ($filter): void {
                if ($filter->status === ReservationStatus::Canceled) {
                    $query->whereRaw('1 = 0');

                    return;
                }

                if ($filter->status !== null) {
                    $query->where('reservation_status_id', $filter->status->value);

                    return;
                }

                $query->where('reservation_status_id', '!=', ReservationStatus::Canceled->value);
            })
            ->get();
    }

    public function overlapNights(ReservationItem $item, ReservationStatisticsFilter $filter): int
    {
        $start = CarbonImmutable::instance($item->arrival_date)->max($filter->start);
        $end = CarbonImmutable::instance($item->departure_date)->min($filter->end);

        return $end->greaterThan($start) ? (int) $start->diffInDays($end) : 0;
    }

    public function occupiedRoomNightsFor(ReservationStatisticsFilter $filter): int
    {
        return $this->occupiedRoomNights(
            $this->occupancyItems($filter),
            $filter->start,
            $filter->end,
        );
    }

    private function reservationsOverlapping(ReservationStatisticsFilter $filter): Builder
    {
        return $this->applyReservationFilters(
            Reservation::query()->whereHas('items', fn (Builder $query): Builder => $query
                ->whereNotNull('room_id')
                ->whereDate('arrival_date', '<', $filter->end->toDateString())
                ->whereDate('departure_date', '>', $filter->start->toDateString())
                ->when($filter->roomId, fn (Builder $query, int $roomId): Builder => $query->where('room_id', $roomId))),
            $filter,
        );
    }

    private function applyReservationFilters(Builder $query, ReservationStatisticsFilter $filter): Builder
    {
        return $query->when(
            $filter->status,
            fn (Builder $query, ReservationStatus $status): Builder => $query->where('reservation_status_id', $status->value),
        );
    }

    private function arrivalCount(
        ReservationStatisticsFilter $filter,
        CarbonImmutable $start,
        CarbonImmutable $end,
        ?ReservationStatus $status = null,
    ): int {
        if (! $start->lessThan($end)) {
            return 0;
        }

        return ReservationItem::query()
            ->whereNotNull('room_id')
            ->whereDate('arrival_date', '>=', $start->toDateString())
            ->whereDate('arrival_date', '<', $end->toDateString())
            ->when($filter->roomId, fn (Builder $query, int $roomId): Builder => $query->where('room_id', $roomId))
            ->whereHas('reservation', function (Builder $query) use ($filter, $status): void {
                $selectedStatus = $status ?? $filter->status;

                if ($selectedStatus === ReservationStatus::Canceled) {
                    $query->whereRaw('1 = 0');
                } elseif ($selectedStatus !== null) {
                    $query->where('reservation_status_id', $selectedStatus->value);
                } else {
                    $query->where('reservation_status_id', '!=', ReservationStatus::Canceled->value);
                }
            })
            ->distinct('reservation_id')
            ->count('reservation_id');
    }

    private function movementCount(ReservationStatisticsFilter $filter, string $column, CarbonImmutable $date): int
    {
        return ReservationItem::query()
            ->whereNotNull('room_id')
            ->whereDate($column, $date->toDateString())
            ->when($filter->roomId, fn (Builder $query, int $roomId): Builder => $query->where('room_id', $roomId))
            ->whereHas('reservation', function (Builder $query) use ($filter): void {
                if ($filter->status === ReservationStatus::Canceled) {
                    $query->whereRaw('1 = 0');
                } elseif ($filter->status !== null) {
                    $query->where('reservation_status_id', $filter->status->value);
                } else {
                    $query->where('reservation_status_id', '!=', ReservationStatus::Canceled->value);
                }
            })
            ->distinct('reservation_id')
            ->count('reservation_id');
    }

    private function availableRoomNights(ReservationStatisticsFilter $filter): int
    {
        $rooms = Room::query()
            ->where('room_active', true)
            ->when($filter->roomId, fn (Builder $query, int $roomId): Builder => $query->whereKey($roomId))
            ->count();

        return $rooms * $filter->days();
    }

    /** @param Collection<int, ReservationItem> $items */
    private function occupiedRoomNights(
        Collection $items,
        CarbonImmutable $start,
        CarbonImmutable $end,
    ): int {
        return (int) $items
            ->groupBy('room_id')
            ->sum(function (Collection $roomItems) use ($start, $end): int {
                $intervals = $roomItems->map(function (ReservationItem $item) use ($start, $end): array {
                    return [
                        'start' => CarbonImmutable::instance($item->arrival_date)->max($start),
                        'end' => CarbonImmutable::instance($item->departure_date)->min($end),
                    ];
                })->filter(fn (array $interval): bool => $interval['end']->greaterThan($interval['start']))
                    ->sortBy('start')
                    ->values();

                if ($intervals->isEmpty()) {
                    return 0;
                }

                $nights = 0;
                $currentStart = $intervals[0]['start'];
                $currentEnd = $intervals[0]['end'];

                foreach ($intervals->slice(1) as $interval) {
                    if ($interval['start']->lessThanOrEqualTo($currentEnd)) {
                        $currentEnd = $interval['end']->greaterThan($currentEnd) ? $interval['end'] : $currentEnd;

                        continue;
                    }

                    $nights += (int) $currentStart->diffInDays($currentEnd);
                    $currentStart = $interval['start'];
                    $currentEnd = $interval['end'];
                }

                return $nights + (int) $currentStart->diffInDays($currentEnd);
            });
    }

    /** @return Collection<int, array{room_id: int, room: string, first_item_id: int, second_item_id: int, starts_on: string, ends_on: string}> */
    private function conflictsFromItems(Collection $items): Collection
    {
        $conflicts = collect();

        foreach ($items->groupBy('room_id') as $roomItems) {
            $active = collect();

            foreach ($roomItems->sortBy('arrival_date') as $item) {
                $arrival = CarbonImmutable::instance($item->arrival_date);
                $departure = CarbonImmutable::instance($item->departure_date);
                $active = $active->filter(fn (ReservationItem $activeItem): bool => $activeItem->departure_date->greaterThan($arrival));

                foreach ($active as $activeItem) {
                    $conflicts->push([
                        'room_id' => (int) $item->room_id,
                        'room' => $item->room?->displayName() ?? (string) $item->room_id,
                        'first_item_id' => (int) $activeItem->getKey(),
                        'second_item_id' => (int) $item->getKey(),
                        'starts_on' => CarbonImmutable::instance($activeItem->arrival_date)->max($arrival)->toDateString(),
                        'ends_on' => CarbonImmutable::instance($activeItem->departure_date)->min($departure)->toDateString(),
                    ]);
                }

                $active->push($item);
            }
        }

        return $conflicts->values();
    }

    /** @return Collection<int, CarbonImmutable> */
    private function months(ReservationStatisticsFilter $filter): Collection
    {
        $months = collect();
        $cursor = $filter->start->startOfMonth();
        $lastMonth = $filter->end->subDay()->startOfMonth();

        while ($cursor->lessThanOrEqualTo($lastMonth) && $months->count() < 60) {
            $months->push($cursor);
            $cursor = $cursor->addMonth();
        }

        return $months;
    }

    /** @return Collection<string, int> */
    private function reservationsByMonth(ReservationStatisticsFilter $filter): Collection
    {
        return $this->applyReservationFilters(Reservation::query(), $filter)
            ->when($filter->roomId, fn (Builder $query, int $roomId): Builder => $query
                ->whereHas('items', fn (Builder $items): Builder => $items->where('room_id', $roomId)))
            ->whereDate('reservation_date_created', '>=', $filter->start->toDateString())
            ->whereDate('reservation_date_created', '<', $filter->end->toDateString())
            ->get(['reservation_date_created'])
            ->countBy(fn (Reservation $reservation): string => $reservation->reservation_date_created->format('Y-m'));
    }

    /** @return Collection<string, int> */
    private function arrivalsByMonth(ReservationStatisticsFilter $filter): Collection
    {
        return ReservationItem::query()
            ->whereNotNull('room_id')
            ->whereDate('arrival_date', '>=', $filter->start->toDateString())
            ->whereDate('arrival_date', '<', $filter->end->toDateString())
            ->when($filter->roomId, fn (Builder $query, int $roomId): Builder => $query->where('room_id', $roomId))
            ->whereHas('reservation', function (Builder $query) use ($filter): void {
                if ($filter->status === ReservationStatus::Canceled) {
                    $query->whereRaw('1 = 0');
                } elseif ($filter->status !== null) {
                    $query->where('reservation_status_id', $filter->status->value);
                } else {
                    $query->where('reservation_status_id', '!=', ReservationStatus::Canceled->value);
                }
            })
            ->get(['reservation_id', 'arrival_date'])
            ->unique(fn (ReservationItem $item): string => $item->reservation_id.'-'.$item->arrival_date->format('Y-m'))
            ->countBy(fn (ReservationItem $item): string => $item->arrival_date->format('Y-m'));
    }

    /** @return Collection<string, float> */
    private function reservationValuesByMonth(ReservationStatisticsFilter $filter): Collection
    {
        return $this->applyReservationFilters(Reservation::query(), $filter)
            ->with('amount')
            ->when($filter->roomId, fn (Builder $query, int $roomId): Builder => $query
                ->whereHas('items', fn (Builder $items): Builder => $items->where('room_id', $roomId)))
            ->whereDate('reservation_date_created', '>=', $filter->start->toDateString())
            ->whereDate('reservation_date_created', '<', $filter->end->toDateString())
            ->get()
            ->groupBy(fn (Reservation $reservation): string => $reservation->reservation_date_created->format('Y-m'))
            ->map(fn (Collection $reservations): float => round((float) $reservations->sum(
                fn (Reservation $reservation): float => (float) ($reservation->amount?->reservation_total ?? 0),
            ), 2));
    }

    /** @return Collection<string, float> */
    private function invoiceValuesByMonth(ReservationStatisticsFilter $filter): Collection
    {
        $eligibleInvoiceIds = $this->applyReservationFilters(Reservation::query(), $filter)
            ->when($filter->roomId, fn (Builder $query, int $roomId): Builder => $query
                ->whereHas('items', fn (Builder $items): Builder => $items->where('room_id', $roomId)))
            ->where('invoice_id', '>', 0)
            ->select('invoice_id');

        return Invoice::query()
            ->with('amount')
            ->whereIn('invoice_id', $eligibleInvoiceIds)
            ->where('invoice_status_id', '!=', InvoiceStatus::Draft->value)
            ->whereDate('invoice_date_created', '>=', $filter->start->toDateString())
            ->whereDate('invoice_date_created', '<', $filter->end->toDateString())
            ->get()
            ->groupBy(fn (Invoice $invoice): string => $invoice->invoice_date_created->format('Y-m'))
            ->map(fn (Collection $invoices): float => round((float) $invoices->sum(
                fn (Invoice $invoice): float => (float) ($invoice->amount?->invoice_total ?? 0),
            ), 2));
    }

    /** @return Collection<string, float> */
    private function paymentValuesByMonth(ReservationStatisticsFilter $filter): Collection
    {
        $eligibleInvoiceIds = $this->applyReservationFilters(Reservation::query(), $filter)
            ->when($filter->roomId, fn (Builder $query, int $roomId): Builder => $query
                ->whereHas('items', fn (Builder $items): Builder => $items->where('room_id', $roomId)))
            ->where('invoice_id', '>', 0)
            ->select('invoice_id');

        return Payment::query()
            ->whereIn('invoice_id', $eligibleInvoiceIds)
            ->whereDate('payment_date', '>=', $filter->start->toDateString())
            ->whereDate('payment_date', '<', $filter->end->toDateString())
            ->get(['payment_date', 'payment_amount'])
            ->groupBy(fn (Payment $payment): string => $payment->payment_date->format('Y-m'))
            ->map(fn (Collection $payments): float => round((float) $payments->sum('payment_amount'), 2));
    }
}

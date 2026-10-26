<?php

namespace App\Features\TourismStatistics\Services;

use App\Features\Reservations\Enums\ReservationStatus;
use App\Features\Reservations\Models\ReservationItem;
use App\Features\Reservations\Models\Room;
use App\Features\TourismStatistics\Data\TourismReportPrefill;
use App\Features\TourismStatistics\Support\ResidenceCountryCatalog;
use Carbon\CarbonImmutable;

final class PopulateTourismReportFromReservations
{
    public function __construct(private readonly ResidenceCountryMapper $countryMapper) {}

    public function handle(int $year, int $month): TourismReportPrefill
    {
        $periodStart = CarbonImmutable::create($year, $month, 1)->startOfDay();
        $periodEnd = $periodStart->addMonth();
        $activeRooms = Room::query()->where('room_active', true);
        $bedsOffered = (int) (clone $activeRooms)->sum('room_capacity');
        $availableGuestRoomDays = (int) (clone $activeRooms)->count() * $periodStart->daysInMonth;
        $rows = collect(ResidenceCountryCatalog::emptyRowsByCode())->keyBy('country_key');
        $occupiedGuestRoomDays = 0;

        $items = ReservationItem::query()
            ->with('reservation.client')
            ->whereNotNull('room_id')
            ->whereNotNull('arrival_date')
            ->whereNotNull('departure_date')
            ->where('arrival_date', '<', $periodEnd->toDateString())
            ->where('departure_date', '>', $periodStart->toDateString())
            ->whereHas('reservation', fn ($query) => $query
                ->where('reservation_status_id', '!=', ReservationStatus::Canceled->value))
            ->get();

        foreach ($items as $item) {
            $arrival = CarbonImmutable::instance($item->arrival_date);
            $departure = CarbonImmutable::instance($item->departure_date);
            $overlapStart = $arrival->greaterThan($periodStart) ? $arrival : $periodStart;
            $overlapEnd = $departure->lessThan($periodEnd) ? $departure : $periodEnd;
            $overnightStays = (int) $overlapStart->diffInDays($overlapEnd);
            $occupiedGuestRoomDays += $overnightStays;
            $countryKey = $this->countryMapper->countryKey($item->reservation?->client?->client_country);
            $row = $rows->get($countryKey);
            $row['arrivals'] = (int) ($row['arrivals'] ?? 0);
            $row['overnight_stays'] = (int) ($row['overnight_stays'] ?? 0) + $overnightStays;

            if ($arrival->greaterThanOrEqualTo($periodStart) && $arrival->lessThan($periodEnd)) {
                $row['arrivals'] = (int) ($row['arrivals'] ?? 0) + 1;
            }

            $rows->put($countryKey, $row);
        }

        return new TourismReportPrefill(
            bedsOffered: $bedsOffered,
            availableGuestRoomDays: $availableGuestRoomDays,
            occupiedGuestRoomDays: $occupiedGuestRoomDays,
            countryValues: $rows->values()->all(),
            reservationItemCount: $items->count(),
        );
    }
}

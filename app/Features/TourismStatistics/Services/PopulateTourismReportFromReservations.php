<?php

namespace App\Features\TourismStatistics\Services;

use App\Features\Reservations\Data\ReservationStatisticsFilter;
use App\Features\Reservations\Models\Room;
use App\Features\Reservations\Services\ReservationStatisticsService;
use App\Features\TourismStatistics\Data\TourismReportPrefill;
use App\Features\TourismStatistics\Support\ResidenceCountryCatalog;
use Carbon\CarbonImmutable;

final class PopulateTourismReportFromReservations
{
    public function __construct(
        private readonly ResidenceCountryMapper $countryMapper,
        private readonly ReservationStatisticsService $reservationStatistics,
    ) {}

    public function handle(int $year, int $month): TourismReportPrefill
    {
        $periodStart = CarbonImmutable::create($year, $month, 1)->startOfDay();
        $periodEnd = $periodStart->addMonth();
        $activeRooms = Room::query()->where('room_active', true);
        $bedsOffered = (int) (clone $activeRooms)->sum('room_capacity');
        $availableGuestRoomDays = (int) (clone $activeRooms)->count() * $periodStart->daysInMonth;
        $rows = collect(ResidenceCountryCatalog::emptyRowsByCode())->keyBy('country_key');
        $filter = new ReservationStatisticsFilter($periodStart, $periodEnd);
        $items = $this->reservationStatistics->occupancyItems($filter);
        $occupiedGuestRoomDays = $this->reservationStatistics->occupiedRoomNightsFor($filter);

        foreach ($items as $item) {
            $arrival = CarbonImmutable::instance($item->arrival_date);
            $overnightStays = $this->reservationStatistics->overlapNights($item, $filter);
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

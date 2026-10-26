<?php

namespace App\Features\TourismStatistics\Data;

final readonly class TourismReportPrefill
{
    /**
     * @param  list<array{country_key: string, arrivals: ?int, overnight_stays: ?int}>  $countryValues
     */
    public function __construct(
        public int $bedsOffered,
        public int $availableGuestRoomDays,
        public int $occupiedGuestRoomDays,
        public array $countryValues,
        public int $reservationItemCount,
    ) {}
}

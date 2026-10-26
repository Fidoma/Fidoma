<?php

namespace App\Features\Reservations\Support;

use App\Features\Reservations\Data\ReservationStatisticsFilter;
use App\Features\Reservations\Enums\ReservationStatus;
use App\Features\Reservations\Filament\Resources\Reservations\ReservationResource;
use Carbon\CarbonInterface;

final class ReservationStatisticsLinks
{
    /** @param array<string, mixed> $additionalFilters */
    public static function forFilter(ReservationStatisticsFilter $filter, array $additionalFilters = []): string
    {
        $filters = [];

        if ($filter->roomId !== null) {
            $filters['room_id'] = ['value' => $filter->roomId];
        }

        if ($filter->status !== null) {
            $filters['reservation_status_id'] = ['value' => $filter->status->value];
        }

        return self::forStayPeriod($filter->start, $filter->end, [
            ...$filters,
            ...$additionalFilters,
        ]);
    }

    /** @param array<string, mixed> $additionalFilters */
    public static function forStayPeriod(
        CarbonInterface $start,
        CarbonInterface $endExclusive,
        array $additionalFilters = [],
    ): string {
        return self::toList([
            'stay_period' => [
                'from' => $start->toDateString(),
                'until' => $endExclusive->subDay()->toDateString(),
            ],
            ...$additionalFilters,
        ]);
    }

    public static function status(ReservationStatus $status): string
    {
        return self::toList([
            'reservation_status_id' => ['value' => $status->value],
        ]);
    }

    /** @param array<string, mixed> $filters */
    public static function toList(array $filters): string
    {
        return ReservationResource::getUrl('index', ['filters' => $filters]);
    }
}

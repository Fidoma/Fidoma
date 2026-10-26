<?php

namespace App\Features\Reservations\Filament\Widgets;

use App\Features\Reservations\Data\ReservationStatisticsFilter;
use App\Features\Reservations\Services\ReservationStatisticsService;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Filament\Widgets\Widget;

class ReservationConflicts extends Widget
{
    use InteractsWithPageFilters;

    protected string $view = 'reservations::widgets.reservation-conflicts';

    protected int|string|array $columnSpan = 'full';

    protected function getViewData(): array
    {
        return [
            'conflicts' => app(ReservationStatisticsService::class)
                ->conflicts(ReservationStatisticsFilter::fromArray($this->pageFilters)),
        ];
    }
}

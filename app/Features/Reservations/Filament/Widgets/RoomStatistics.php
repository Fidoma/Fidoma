<?php

namespace App\Features\Reservations\Filament\Widgets;

use App\Features\Reservations\Data\ReservationStatisticsFilter;
use App\Features\Reservations\Services\ReservationStatisticsService;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Filament\Widgets\Widget;

class RoomStatistics extends Widget
{
    use InteractsWithPageFilters;

    protected string $view = 'reservations::widgets.room-statistics';

    protected int|string|array $columnSpan = 'full';

    protected function getViewData(): array
    {
        return [
            'rooms' => app(ReservationStatisticsService::class)
                ->roomMetrics(ReservationStatisticsFilter::fromArray($this->pageFilters)),
        ];
    }
}

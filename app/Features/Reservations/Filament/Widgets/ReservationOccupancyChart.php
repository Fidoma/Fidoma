<?php

namespace App\Features\Reservations\Filament\Widgets;

use App\Features\Reservations\Data\ReservationStatisticsFilter;
use App\Features\Reservations\Services\ReservationStatisticsService;
use Filament\Widgets\ChartWidget;
use Filament\Widgets\Concerns\InteractsWithPageFilters;

class ReservationOccupancyChart extends ChartWidget
{
    use InteractsWithPageFilters;

    protected ?string $maxHeight = '320px';

    protected int|string|array $columnSpan = 1;

    public function getHeading(): string
    {
        return __('reservations::messages.statistics.charts.occupancy');
    }

    public function getDescription(): string
    {
        return __('reservations::messages.statistics.charts.occupancy_description');
    }

    protected function getData(): array
    {
        $series = app(ReservationStatisticsService::class)
            ->monthlySeries(ReservationStatisticsFilter::fromArray($this->pageFilters));

        return [
            'datasets' => [
                [
                    'label' => __('reservations::messages.statistics.charts.current'),
                    'data' => $series['occupancy'],
                    'borderColor' => '#f59e0b',
                    'backgroundColor' => 'rgba(245, 158, 11, 0.12)',
                    'fill' => true,
                    'tension' => 0.3,
                ],
                [
                    'label' => __('reservations::messages.statistics.charts.previous_year'),
                    'data' => $series['previous_year_occupancy'],
                    'borderColor' => '#94a3b8',
                    'borderDash' => [6, 5],
                    'tension' => 0.3,
                ],
            ],
            'labels' => $series['labels'],
        ];
    }

    protected function getOptions(): array
    {
        return [
            'scales' => [
                'y' => ['beginAtZero' => true, 'max' => 100],
            ],
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }
}

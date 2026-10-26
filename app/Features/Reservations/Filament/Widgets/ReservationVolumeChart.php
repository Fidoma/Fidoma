<?php

namespace App\Features\Reservations\Filament\Widgets;

use App\Features\Reservations\Data\ReservationStatisticsFilter;
use App\Features\Reservations\Services\ReservationStatisticsService;
use Filament\Widgets\ChartWidget;
use Filament\Widgets\Concerns\InteractsWithPageFilters;

class ReservationVolumeChart extends ChartWidget
{
    use InteractsWithPageFilters;

    protected ?string $maxHeight = '320px';

    protected int|string|array $columnSpan = 1;

    public function getHeading(): string
    {
        return __('reservations::messages.statistics.charts.volume');
    }

    public function getDescription(): string
    {
        return __('reservations::messages.statistics.charts.volume_description');
    }

    protected function getData(): array
    {
        $series = app(ReservationStatisticsService::class)
            ->monthlySeries(ReservationStatisticsFilter::fromArray($this->pageFilters));

        return [
            'datasets' => [
                [
                    'label' => __('reservations::messages.statistics.charts.reservations'),
                    'data' => $series['reservations'],
                    'backgroundColor' => '#f59e0b',
                    'borderRadius' => 5,
                ],
                [
                    'label' => __('reservations::messages.statistics.charts.arrivals'),
                    'data' => $series['arrivals'],
                    'backgroundColor' => '#64748b',
                    'borderRadius' => 5,
                ],
            ],
            'labels' => $series['labels'],
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }
}

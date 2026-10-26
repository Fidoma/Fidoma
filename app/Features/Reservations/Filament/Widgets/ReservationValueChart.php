<?php

namespace App\Features\Reservations\Filament\Widgets;

use App\Features\Reservations\Data\ReservationStatisticsFilter;
use App\Features\Reservations\Services\ReservationStatisticsService;
use Filament\Widgets\ChartWidget;
use Filament\Widgets\Concerns\InteractsWithPageFilters;

class ReservationValueChart extends ChartWidget
{
    use InteractsWithPageFilters;

    protected ?string $maxHeight = '320px';

    protected int|string|array $columnSpan = 'full';

    public function getHeading(): string
    {
        return __('reservations::messages.statistics.charts.values');
    }

    public function getDescription(): string
    {
        return __('reservations::messages.statistics.charts.values_description');
    }

    protected function getData(): array
    {
        $series = app(ReservationStatisticsService::class)
            ->monthlySeries(ReservationStatisticsFilter::fromArray($this->pageFilters));

        return [
            'datasets' => [
                [
                    'label' => __('reservations::messages.statistics.charts.reservation_value'),
                    'data' => $series['reservation_values'],
                    'borderColor' => '#f59e0b',
                    'tension' => 0.3,
                ],
                [
                    'label' => __('reservations::messages.statistics.charts.invoice_value'),
                    'data' => $series['invoice_values'],
                    'borderColor' => '#6366f1',
                    'tension' => 0.3,
                ],
                [
                    'label' => __('reservations::messages.statistics.charts.payments'),
                    'data' => $series['payments'],
                    'borderColor' => '#10b981',
                    'tension' => 0.3,
                ],
            ],
            'labels' => $series['labels'],
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }
}

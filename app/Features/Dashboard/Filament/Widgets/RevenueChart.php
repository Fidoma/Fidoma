<?php

namespace App\Features\Dashboard\Filament\Widgets;

use App\Features\Dashboard\Services\DashboardMetrics;
use App\Features\Dashboard\Support\DashboardPeriod;
use Filament\Widgets\ChartWidget;
use Filament\Widgets\Concerns\InteractsWithPageFilters;

class RevenueChart extends ChartWidget
{
    use InteractsWithPageFilters;

    protected static ?int $sort = 2;

    protected ?string $heading = 'Umsatzentwicklung';

    protected ?string $description = 'Rechnungsumsatz im Vergleich zum Vorjahr';

    protected ?string $maxHeight = '310px';

    protected int|string|array $columnSpan = 1;

    protected function getData(): array
    {
        $series = app(DashboardMetrics::class)->revenueSeries(DashboardPeriod::fromFilters($this->pageFilters));

        return [
            'datasets' => [
                [
                    'label' => 'Aktueller Zeitraum',
                    'data' => $series['current'],
                    'borderColor' => '#f59e0b',
                    'backgroundColor' => 'rgba(245, 158, 11, 0.12)',
                    'fill' => true,
                    'tension' => 0.35,
                ],
                [
                    'label' => 'Vorjahr',
                    'data' => $series['previous'],
                    'borderColor' => '#94a3b8',
                    'backgroundColor' => 'transparent',
                    'borderDash' => [6, 5],
                    'tension' => 0.35,
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

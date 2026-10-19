<?php

namespace App\Features\Dashboard\Filament\Widgets;

use App\Features\Dashboard\Services\DashboardMetrics;
use App\Features\Dashboard\Support\DashboardPeriod;
use Filament\Widgets\ChartWidget;
use Filament\Widgets\Concerns\InteractsWithPageFilters;

class CashflowChart extends ChartWidget
{
    use InteractsWithPageFilters;

    protected static ?int $sort = 3;

    protected ?string $heading = 'Einnahmen und Ausgaben';

    protected ?string $description = 'Gebuchte Banktransaktionen';

    protected ?string $maxHeight = '310px';

    protected int|string|array $columnSpan = 1;

    public function getHeading(): string
    {
        return __('dashboard::messages.charts.cashflow');
    }

    public function getDescription(): string
    {
        return __('dashboard::messages.charts.cashflow_description');
    }

    protected function getData(): array
    {
        $series = app(DashboardMetrics::class)->cashflowSeries(DashboardPeriod::fromFilters($this->pageFilters));

        return [
            'datasets' => [
                ['label' => __('dashboard::messages.charts.income'), 'data' => $series['income'], 'backgroundColor' => '#10b981', 'borderRadius' => 6],
                ['label' => __('dashboard::messages.charts.expenses'), 'data' => $series['expenses'], 'backgroundColor' => '#f97316', 'borderRadius' => 6],
            ],
            'labels' => $series['labels'],
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }
}

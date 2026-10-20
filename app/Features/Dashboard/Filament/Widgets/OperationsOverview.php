<?php

namespace App\Features\Dashboard\Filament\Widgets;

use App\Features\Dashboard\Services\DashboardMetrics;
use App\Features\Dashboard\Support\DashboardPeriod;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Filament\Widgets\Widget;

class OperationsOverview extends Widget
{
    use InteractsWithPageFilters;

    protected static ?int $sort = 4;

    protected string $view = 'dashboard::widgets.operations-overview';

    protected int|string|array $columnSpan = 'full';

    protected function getViewData(): array
    {
        $metrics = app(DashboardMetrics::class);

        return [
            'documents' => $metrics->documentStats(),
            'quotes' => $metrics->quoteStats(DashboardPeriod::fromFilters($this->pageFilters)),
        ];
    }
}

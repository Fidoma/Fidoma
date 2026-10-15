<?php

namespace App\Filament\Widgets;

use App\Filament\Support\DashboardMetrics;
use App\Filament\Support\DashboardPeriod;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Filament\Widgets\Widget;

class OperationsOverview extends Widget
{
    use InteractsWithPageFilters;

    protected static ?int $sort = 4;

    protected string $view = 'filament.widgets.operations-overview';

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

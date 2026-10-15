<?php

namespace App\Filament\Pages;

use App\Filament\Widgets\CashflowChart;
use App\Filament\Widgets\DashboardKpis;
use App\Filament\Widgets\DashboardTasks;
use App\Filament\Widgets\OperationsOverview;
use App\Filament\Widgets\RevenueChart;
use Carbon\CarbonImmutable;
use Filament\Pages\Dashboard as BaseDashboard;
use Filament\Widgets\Widget;
use Illuminate\Contracts\View\View;

class Dashboard extends BaseDashboard
{
    protected static ?string $title = 'Dashboard';

    /** @var array<string, mixed> */
    public array $filters = [];

    public string $startDate = '';

    public string $endDate = '';

    public function mount(): void
    {
        $today = CarbonImmutable::today();

        $this->startDate = $today->startOfMonth()->toDateString();
        $this->endDate = $today->endOfMonth()->toDateString();

        $this->applyDateFilter();
    }

    public function applyDateFilter(): void
    {
        $this->validate([
            'startDate' => ['required', 'date'],
            'endDate' => ['required', 'date', 'after_or_equal:startDate'],
        ], [
            'endDate.after_or_equal' => 'Das Enddatum muss nach dem Startdatum liegen.',
        ]);

        $this->filters = [
            'period' => 'custom',
            'start_date' => $this->startDate,
            'end_date' => $this->endDate,
        ];
    }

    /** @return array<class-string<Widget>> */
    public function getWidgets(): array
    {
        return [
            DashboardKpis::class,
            RevenueChart::class,
            CashflowChart::class,
            OperationsOverview::class,
            DashboardTasks::class,
        ];
    }

    public function getColumns(): int|array
    {
        return ['default' => 1, 'xl' => 2];
    }

    public function getHeader(): ?View
    {
        return view('filament.pages.dashboard-header');
    }
}

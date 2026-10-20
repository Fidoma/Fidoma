<?php

namespace App\Features\Dashboard\Filament\Pages;

use App\Features\BankTransactions\Models\BankTransaction;
use App\Features\Dashboard\Filament\Widgets\CashflowChart;
use App\Features\Dashboard\Filament\Widgets\DashboardKpis;
use App\Features\Dashboard\Filament\Widgets\DashboardTasks;
use App\Features\Dashboard\Filament\Widgets\OperationsOverview;
use App\Features\Dashboard\Filament\Widgets\RevenueChart;
use App\Features\Invoices\Models\Invoice;
use App\Features\Quotes\Models\Quote;
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

    public string $selectedPeriod = 'custom';

    public function mount(): void
    {
        $today = CarbonImmutable::today();

        $this->startDate = $today->startOfMonth()->toDateString();
        $this->endDate = $today->endOfMonth()->toDateString();

        $this->updateDateFilters();
    }

    public function applyDateFilter(): void
    {
        $this->validate([
            'startDate' => ['nullable', 'date', 'required_with:endDate'],
            'endDate' => ['nullable', 'date', 'required_with:startDate', 'after_or_equal:startDate'],
        ], [
            'endDate.after_or_equal' => __('dashboard::messages.validation.end_after_start'),
        ]);

        $this->selectedPeriod = 'custom';
        $this->updateDateFilters();
    }

    public function selectPeriod(string $period): void
    {
        if (! in_array($period, ['q1', 'q2', 'q3', 'q4', 'year', 'reset'], true)) {
            return;
        }

        if ($period === 'reset') {
            $this->clearDateFilter();

            return;
        }

        $this->selectedPeriod = $period;
        $today = CarbonImmutable::today();
        [$start, $end] = match ($period) {
            'q1' => [$today->startOfYear(), $today->startOfYear()->addMonths(3)->subDay()],
            'q2' => [$today->startOfYear()->addMonths(3), $today->startOfYear()->addMonths(6)->subDay()],
            'q3' => [$today->startOfYear()->addMonths(6), $today->startOfYear()->addMonths(9)->subDay()],
            'q4' => [$today->startOfYear()->addMonths(9), $today->endOfYear()],
            'year' => [$today->startOfYear(), $today->endOfYear()],
        };

        $this->startDate = $start->toDateString();
        $this->endDate = $end->toDateString();
        $this->resetValidation(['startDate', 'endDate']);
        $this->updateDateFilters();
    }

    public function clearDateFilter(): void
    {
        $this->startDate = '';
        $this->endDate = '';
        $this->selectedPeriod = 'custom';
        $this->resetValidation(['startDate', 'endDate']);
        $this->updateDateFilters();
    }

    public function periodLabel(): string
    {
        return match ($this->selectedPeriod) {
            'q1' => 'Q1',
            'q2' => 'Q2',
            'q3' => 'Q3',
            'q4' => 'Q4',
            'year' => __('dashboard::messages.period.year'),
            default => __('dashboard::messages.period.range'),
        };
    }

    private function updateDateFilters(): void
    {
        [$startDate, $endDate] = filled($this->startDate) && filled($this->endDate)
            ? [$this->startDate, $this->endDate]
            : $this->allTimeRange();

        $this->filters = [
            'period' => 'custom',
            'start_date' => $startDate,
            'end_date' => $endDate,
        ];
    }

    /** @return array{string, string} */
    private function allTimeRange(): array
    {
        $today = CarbonImmutable::today();
        $dates = collect([
            Invoice::query()->min('invoice_date_created'),
            Invoice::query()->max('invoice_date_created'),
            BankTransaction::query()->min('booking_date'),
            BankTransaction::query()->max('booking_date'),
            Quote::query()->min('quote_date_created'),
            Quote::query()->max('quote_date_created'),
        ])->filter();

        return [
            CarbonImmutable::parse($dates->min() ?: $today->startOfMonth())->toDateString(),
            CarbonImmutable::parse($dates->max() ?: $today)->max($today)->toDateString(),
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
        return view('dashboard::dashboard-header');
    }
}

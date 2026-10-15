<?php

namespace App\Filament\Pages;

use App\Features\BankTransactions\Models\BankTransaction;
use App\Features\Invoices\Models\Invoice;
use App\Features\Quotes\Models\Quote;
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
            'startDate' => ['nullable', 'date', 'required_with:endDate'],
            'endDate' => ['nullable', 'date', 'required_with:startDate', 'after_or_equal:startDate'],
        ], [
            'endDate.after_or_equal' => 'Das Enddatum muss nach dem Startdatum liegen.',
        ]);

        [$startDate, $endDate] = filled($this->startDate) && filled($this->endDate)
            ? [$this->startDate, $this->endDate]
            : $this->allTimeRange();

        $this->filters = [
            'period' => 'custom',
            'start_date' => $startDate,
            'end_date' => $endDate,
        ];
    }

    public function clearDateFilter(): void
    {
        $this->startDate = '';
        $this->endDate = '';
        $this->resetValidation(['startDate', 'endDate']);
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
        return view('filament.pages.dashboard-header');
    }
}

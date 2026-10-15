<?php

namespace App\Filament\Widgets;

use App\Features\BankTransactions\Filament\Resources\BankTransactions\BankTransactionResource;
use App\Features\Invoices\Filament\Resources\Invoices\InvoiceResource;
use App\Filament\Support\DashboardMetrics;
use App\Filament\Support\DashboardPeriod;
use Filament\Support\Icons\Heroicon;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class DashboardKpis extends StatsOverviewWidget
{
    use InteractsWithPageFilters;

    protected static ?int $sort = 1;

    protected ?string $pollingInterval = null;

    protected function getStats(): array
    {
        $period = DashboardPeriod::fromFilters($this->pageFilters);
        $metrics = app(DashboardMetrics::class);
        $revenue = $metrics->revenue($period);
        $previousRevenue = $metrics->previousRevenue($period);
        $change = $previousRevenue !== 0.0 ? (($revenue - $previousRevenue) / abs($previousRevenue)) * 100 : null;
        $receivables = $metrics->receivablesAt($period);
        $overdue = $metrics->receivablesAt($period, overdueOnly: true);

        return [
            Stat::make('Umsatz', $this->money($revenue))
                ->description($change === null ? 'Kein Vergleichswert' : number_format(abs($change), 1, ',', '.').' % '.($change >= 0 ? 'mehr' : 'weniger'))
                ->descriptionIcon($change === null ? Heroicon::OutlinedMinus : ($change >= 0 ? Heroicon::OutlinedArrowTrendingUp : Heroicon::OutlinedArrowTrendingDown))
                ->descriptionColor($change === null ? 'gray' : ($change >= 0 ? 'success' : 'danger'))
                ->icon(Heroicon::OutlinedChartBarSquare)
                ->color('primary')
                ->url(InvoiceResource::getUrl('index')),
            Stat::make('Offene Forderungen', $this->money($receivables['amount']))
                ->description($receivables['count'].' offene '.($receivables['count'] === 1 ? 'Rechnung' : 'Rechnungen').' zum Stichtag')
                ->descriptionIcon(Heroicon::OutlinedClock)
                ->icon(Heroicon::OutlinedBanknotes)
                ->color($receivables['amount'] > 0 ? 'warning' : 'success')
                ->url(InvoiceResource::getUrl('index', ['activeTab' => 'Sent'])),
            Stat::make('Zahlungseingänge', $this->money($metrics->bankIncome($period)))
                ->description('Gebuchte Bankeingänge · '.$period->label)
                ->descriptionIcon(Heroicon::OutlinedArrowDownLeft)
                ->icon(Heroicon::OutlinedBuildingLibrary)
                ->color('success')
                ->url(BankTransactionResource::getUrl('index')),
            Stat::make('Überfällige Rechnungen', $this->money($overdue['amount']))
                ->description($overdue['count'].' '.($overdue['count'] === 1 ? 'Rechnung' : 'Rechnungen').' überfällig')
                ->descriptionIcon(Heroicon::OutlinedExclamationTriangle)
                ->icon(Heroicon::OutlinedCalendarDays)
                ->color($overdue['count'] > 0 ? 'danger' : 'success')
                ->url(InvoiceResource::getUrl('index', ['activeTab' => 'overdue'])),
        ];
    }

    private function money(float $amount): string
    {
        return number_format($amount, 2, ',', '.').' €';
    }
}

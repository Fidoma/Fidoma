<?php

namespace App\Features\Dashboard\Filament\Widgets;

use App\Features\BankTransactions\Filament\Resources\BankTransactions\BankTransactionResource;
use App\Features\Dashboard\Services\DashboardMetrics;
use App\Features\Dashboard\Support\DashboardPeriod;
use App\Features\Documents\Filament\Pages\Documents;
use App\Features\Invoices\Filament\Resources\Invoices\InvoiceResource;
use App\Features\Quotes\Filament\Resources\Quotes\QuoteResource;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Filament\Widgets\Widget;

class DashboardTasks extends Widget
{
    use InteractsWithPageFilters;

    protected static ?int $sort = 5;

    protected string $view = 'features.dashboard.widgets.dashboard-tasks';

    protected int|string|array $columnSpan = 'full';

    protected function getViewData(): array
    {
        $metrics = app(DashboardMetrics::class);
        $period = DashboardPeriod::fromFilters($this->pageFilters);
        $overdue = $metrics->receivablesAt($period, overdueOnly: true);
        $documents = $metrics->documentStats();
        $quotes = $metrics->quoteStats($period);

        return ['tasks' => [
            [
                'label' => __('dashboard::messages.tasks.overdue_invoices'), 'count' => $overdue['count'],
                'description' => __('dashboard::messages.tasks.amount_open', ['amount' => number_format($overdue['amount'], 2, ',', '.')]),
                'icon' => 'heroicon-o-exclamation-triangle', 'color' => 'danger',
                'url' => InvoiceResource::getUrl('index', ['activeTab' => 'overdue']),
            ],
            [
                'label' => __('dashboard::messages.tasks.unassigned_transactions'), 'count' => $documents['unmapped_transactions'],
                'description' => __('dashboard::messages.tasks.unassigned_description'),
                'icon' => 'heroicon-o-building-library', 'color' => 'warning',
                'url' => BankTransactionResource::getUrl('index'),
            ],
            [
                'label' => __('dashboard::messages.tasks.documents'), 'count' => $documents['unprocessed_documents'],
                'description' => $documents['unprocessed_documents'] === null ? __('dashboard::messages.tasks.paperless_unavailable') : __('dashboard::messages.tasks.documents_unassigned'),
                'icon' => 'heroicon-o-document-text', 'color' => 'gray',
                'url' => Documents::getUrl(),
            ],
            [
                'label' => __('dashboard::messages.tasks.open_quotes'), 'count' => $quotes['open_count'],
                'description' => __('dashboard::messages.tasks.quote_value', ['amount' => number_format($quotes['open_value'], 2, ',', '.')]),
                'icon' => 'heroicon-o-document-duplicate', 'color' => 'info',
                'url' => QuoteResource::getUrl('index'),
            ],
        ]];
    }
}

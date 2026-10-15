<?php

namespace App\Filament\Widgets;

use App\Features\BankTransactions\Filament\Resources\BankTransactions\BankTransactionResource;
use App\Features\Invoices\Filament\Resources\Invoices\InvoiceResource;
use App\Features\Quotes\Filament\Resources\Quotes\QuoteResource;
use App\Filament\Pages\Documents;
use App\Filament\Support\DashboardMetrics;
use App\Filament\Support\DashboardPeriod;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Filament\Widgets\Widget;

class DashboardTasks extends Widget
{
    use InteractsWithPageFilters;

    protected static ?int $sort = 5;

    protected string $view = 'filament.widgets.dashboard-tasks';

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
                'label' => 'Überfällige Kundenrechnungen', 'count' => $overdue['count'],
                'description' => number_format($overdue['amount'], 2, ',', '.').' € offen',
                'icon' => 'heroicon-o-exclamation-triangle', 'color' => 'danger',
                'url' => InvoiceResource::getUrl('index', ['activeTab' => 'overdue']),
            ],
            [
                'label' => 'Banktransaktionen ohne Zuordnung', 'count' => $documents['unmapped_transactions'],
                'description' => 'Noch keinem Beleg oder Zahlungseingang zugeordnet',
                'icon' => 'heroicon-o-building-library', 'color' => 'warning',
                'url' => BankTransactionResource::getUrl('index'),
            ],
            [
                'label' => 'Dokumente zu verarbeiten', 'count' => $documents['unprocessed_documents'],
                'description' => $documents['unprocessed_documents'] === null ? 'Paperless derzeit nicht erreichbar' : 'Noch nicht vollständig zugeordnet',
                'icon' => 'heroicon-o-document-text', 'color' => 'gray',
                'url' => Documents::getUrl(),
            ],
            [
                'label' => 'Offene Angebote', 'count' => $quotes['open_count'],
                'description' => number_format($quotes['open_value'], 2, ',', '.').' € Angebotswert',
                'icon' => 'heroicon-o-document-duplicate', 'color' => 'info',
                'url' => QuoteResource::getUrl('index'),
            ],
        ]];
    }
}

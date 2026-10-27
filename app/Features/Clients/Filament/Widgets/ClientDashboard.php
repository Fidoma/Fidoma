<?php

namespace App\Features\Clients\Filament\Widgets;

use App\Features\Clients\Filament\Resources\Clients\ClientResource;
use App\Features\Clients\Services\ClientAnalytics;
use App\Features\Clients\Support\ClientAnalyticsPeriod;
use App\Features\Invoices\Filament\Resources\Invoices\InvoiceResource;
use Carbon\CarbonImmutable;
use Filament\Widgets\Widget;

class ClientDashboard extends Widget
{
    protected string $view = 'clients::widgets.client-dashboard';

    protected int|string|array $columnSpan = 'full';

    protected static bool $isLazy = false;

    public string $selectedPeriod = 'this_month';

    public string $startDate = '';

    public string $endDate = '';

    public string $rankingMetric = 'revenue';

    public function mount(): void
    {
        $this->selectPeriod('this_month');
    }

    public function selectPeriod(string $period): void
    {
        if (! in_array($period, ['this_month', 'this_year'], true)) {
            return;
        }

        $range = $period === 'this_year'
            ? ClientAnalyticsPeriod::thisYear()
            : ClientAnalyticsPeriod::thisMonth();

        $this->selectedPeriod = $period;
        $this->startDate = $range->start->toDateString();
        $this->endDate = $range->end->toDateString();
        $this->resetValidation();
    }

    public function applyCustomPeriod(): void
    {
        $this->validate([
            'startDate' => ['required', 'date'],
            'endDate' => ['required', 'date', 'after_or_equal:startDate'],
        ], [
            'endDate.after_or_equal' => __('clients::messages.dashboard.validation.end_after_start'),
        ]);

        $this->selectedPeriod = 'custom';
    }

    public function selectRanking(string $metric): void
    {
        if (in_array($metric, ['revenue', 'receivables', 'invoice_count'], true)) {
            $this->rankingMetric = $metric;
        }
    }

    protected function getViewData(): array
    {
        $period = ClientAnalyticsPeriod::custom($this->startDate, $this->endDate);
        $overview = app(ClientAnalytics::class)->overview($period);
        $rankings = $overview['rankings'];
        $ranking = $rankings[$this->rankingMetric];
        $maximum = max(1, (float) collect($ranking)->max('value'));
        $invoiceFilters = ['tableFilters' => ['invoice_date_created' => [
            'from' => $period->start->toDateString(),
            'until' => $period->end->toDateString(),
        ]]];

        return [
            'overview' => $overview,
            'ranking' => $ranking,
            'maximum' => $maximum,
            'allClientsUrl' => ClientResource::getUrl('index', ['activeTab' => 'all']),
            'activeClientsUrl' => ClientResource::getUrl('index', ['activeTab' => 'active']),
            'newClientsUrl' => ClientResource::getUrl('index', ['tableFilters' => ['client_date_created' => [
                'from' => $period->start->toDateString(),
                'until' => $period->end->toDateString(),
            ]]]),
            'invoicesUrl' => InvoiceResource::getUrl('index', $invoiceFilters),
            'receivablesUrl' => InvoiceResource::getUrl('index', [
                'activeTab' => 'Open',
                ...$invoiceFilters,
            ]),
        ];
    }

    public function clientUrl(int $clientId): string
    {
        return ClientResource::getUrl('view', ['record' => $clientId]);
    }

    public function periodLabel(): string
    {
        return $this->selectedPeriod === 'custom'
            ? CarbonImmutable::parse($this->startDate)->format('d.m.Y').' – '.CarbonImmutable::parse($this->endDate)->format('d.m.Y')
            : __('clients::messages.dashboard.periods.'.$this->selectedPeriod);
    }
}

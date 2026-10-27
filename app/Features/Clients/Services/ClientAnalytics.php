<?php

namespace App\Features\Clients\Services;

use App\Features\Clients\Models\Client;
use App\Features\Clients\Support\ClientAnalyticsPeriod;
use App\Features\Invoices\Enums\InvoiceStatus;
use App\Features\Invoices\Enums\InvoiceType;
use App\Features\Invoices\Models\Invoice;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Collection;

final class ClientAnalytics
{
    /**
     * @return array{
     *     total_clients: int,
     *     active_clients: int,
     *     revenue: float,
     *     receivables: float,
     *     average_revenue: float,
     *     new_clients: int,
     *     without_revenue_90_days: int,
     *     rankings: array<string, list<array{id: int, name: string, value: float|int}>>
     * }
     */
    public function overview(ClientAnalyticsPeriod $period): array
    {
        $clientCounts = Client::query()
            ->selectRaw('COUNT(*) as total_clients')
            ->selectRaw('SUM(CASE WHEN client_active = 1 THEN 1 ELSE 0 END) as active_clients')
            ->selectRaw(
                'SUM(CASE WHEN client_date_created BETWEEN ? AND ? THEN 1 ELSE 0 END) as new_clients',
                [$period->start, $period->end],
            )
            ->first();

        $byClient = $this->invoiceMetrics($period);
        $revenue = round((float) $byClient->sum('revenue'), 2);
        $receivables = round((float) $byClient->sum('receivables'), 2);
        $clientsWithRevenue = $byClient->filter(fn (object $row): bool => abs((float) $row->revenue) >= 0.005)->count();

        return [
            'total_clients' => (int) ($clientCounts?->total_clients ?? 0),
            'active_clients' => (int) ($clientCounts?->active_clients ?? 0),
            'revenue' => $revenue,
            'receivables' => $receivables,
            'average_revenue' => $clientsWithRevenue > 0 ? round($revenue / $clientsWithRevenue, 2) : 0.0,
            'new_clients' => (int) ($clientCounts?->new_clients ?? 0),
            'without_revenue_90_days' => $this->clientsWithoutRevenue($period),
            'rankings' => [
                'revenue' => $this->ranking($byClient, 'revenue'),
                'receivables' => $this->ranking($byClient, 'receivables'),
                'invoice_count' => $this->ranking($byClient, 'invoice_count'),
            ],
        ];
    }

    /** @return Collection<int, object> */
    private function invoiceMetrics(ClientAnalyticsPeriod $period): Collection
    {
        return Invoice::query()
            ->join('ip_clients', 'ip_clients.client_id', '=', 'ip_invoices.client_id')
            ->join('ip_invoice_amounts', 'ip_invoice_amounts.invoice_id', '=', 'ip_invoices.invoice_id')
            ->where('ip_invoices.invoice_status_id', '!=', InvoiceStatus::Draft->value)
            ->where('ip_invoices.is_archived', false)
            ->whereBetween('ip_invoices.invoice_date_created', [$period->start, $period->end])
            ->groupBy('ip_invoices.client_id', 'ip_clients.client_name')
            ->selectRaw('ip_invoices.client_id, ip_clients.client_name')
            ->selectRaw('SUM(ip_invoice_amounts.invoice_total) as revenue')
            ->selectRaw(
                'SUM(CASE WHEN ip_invoices.invoice_type = ? AND ip_invoice_amounts.invoice_balance > 0 THEN ip_invoice_amounts.invoice_balance ELSE 0 END) as receivables',
                [InvoiceType::Invoice->value],
            )
            ->selectRaw(
                'SUM(CASE WHEN ip_invoices.invoice_type = ? THEN 1 ELSE 0 END) as invoice_count',
                [InvoiceType::Invoice->value],
            )
            ->get();
    }

    private function clientsWithoutRevenue(ClientAnalyticsPeriod $period): int
    {
        $recentStart = $period->end->subDays(89)->startOfDay();
        $recentRevenue = Invoice::query()
            ->join('ip_invoice_amounts', 'ip_invoice_amounts.invoice_id', '=', 'ip_invoices.invoice_id')
            ->where('ip_invoices.invoice_status_id', '!=', InvoiceStatus::Draft->value)
            ->where('ip_invoices.is_archived', false)
            ->whereBetween('ip_invoices.invoice_date_created', [$recentStart, $period->end])
            ->groupBy('ip_invoices.client_id')
            ->selectRaw('ip_invoices.client_id, SUM(ip_invoice_amounts.invoice_total) as revenue');

        return Client::query()
            ->where('client_active', true)
            ->whereNotIn('client_id', function (Builder $query) use ($recentRevenue): void {
                $query->fromSub($recentRevenue, 'recent_client_revenue')
                    ->select('client_id')
                    ->whereRaw('ABS(revenue) >= 0.005');
            })
            ->count();
    }

    /**
     * @param  Collection<int, object>  $metrics
     * @return list<array{id: int, name: string, value: float|int}>
     */
    private function ranking(Collection $metrics, string $metric): array
    {
        return $metrics
            ->filter(fn (object $row): bool => (float) $row->{$metric} > 0)
            ->sortByDesc(fn (object $row): float => (float) $row->{$metric})
            ->take(5)
            ->values()
            ->map(fn (object $row): array => [
                'id' => (int) $row->client_id,
                'name' => (string) $row->client_name,
                'value' => $metric === 'invoice_count' ? (int) $row->{$metric} : round((float) $row->{$metric}, 2),
            ])
            ->all();
    }
}

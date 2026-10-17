<?php

namespace App\Features\Dashboard\Services;

use App\Features\BankTransactions\Models\BankTransaction;
use App\Features\BankTransactions\Models\BankTransactionMapping;
use App\Features\Dashboard\Support\DashboardPeriod;
use App\Features\Documents\Services\PaperlessClient;
use App\Features\Invoices\Enums\InvoiceStatus;
use App\Features\Invoices\Enums\InvoiceType;
use App\Features\Invoices\Models\Invoice;
use App\Features\Quotes\Enums\QuoteStatus;
use App\Features\Quotes\Models\Quote;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Throwable;

final class DashboardMetrics
{
    public function revenue(DashboardPeriod $period): float
    {
        return $this->revenueBetween($period->start, $period->end);
    }

    public function previousRevenue(DashboardPeriod $period): float
    {
        return $this->revenueBetween($period->previousStart, $period->previousEnd);
    }

    /** @return array{amount: float, count: int} */
    public function receivablesAt(DashboardPeriod $period, bool $overdueOnly = false): array
    {
        $invoices = Invoice::query()
            ->where('invoice_type', InvoiceType::Invoice->value)
            ->where('invoice_status_id', '!=', InvoiceStatus::Draft->value)
            ->where('is_archived', false)
            ->whereDate('invoice_date_created', '<=', $period->end)
            ->when($overdueOnly, fn ($query) => $query->whereDate('invoice_date_due', '<', $period->end->toDateString()))
            ->with(['amount', 'payments' => fn ($query) => $query->whereDate('payment_date', '<=', $period->end)])
            ->get();

        $balances = $invoices->map(fn (Invoice $invoice): float => max(
            0,
            round((float) ($invoice->amount?->invoice_total ?? 0) - (float) $invoice->payments->sum('payment_amount'), 2),
        ))->filter(fn (float $balance): bool => $balance > 0);

        return ['amount' => round((float) $balances->sum(), 2), 'count' => $balances->count()];
    }

    public function bankIncome(DashboardPeriod $period): float
    {
        return round((float) BankTransaction::query()
            ->where('pending', false)
            ->where('amount', '>', 0)
            ->whereBetween('booking_date', [$period->start, $period->end])
            ->sum('amount'), 2);
    }

    /** @return array{labels: list<string>, current: list<float>, previous: list<float>} */
    public function revenueSeries(DashboardPeriod $period): array
    {
        $months = $this->months($period);
        $current = $this->invoiceTotalsByMonth($period->start, $period->end);
        $previous = $this->invoiceTotalsByMonth($period->start->subYear(), $period->end->subYear());

        return [
            'labels' => $months->map(fn (CarbonImmutable $month): string => $month->translatedFormat('M Y'))->all(),
            'current' => $months->map(fn (CarbonImmutable $month): float => $current[$month->format('Y-m')] ?? 0.0)->all(),
            'previous' => $months->map(fn (CarbonImmutable $month): float => $previous[$month->subYear()->format('Y-m')] ?? 0.0)->all(),
        ];
    }

    /** @return array{labels: list<string>, income: list<float>, expenses: list<float>} */
    public function cashflowSeries(DashboardPeriod $period): array
    {
        $months = $this->months($period);
        $transactions = BankTransaction::query()
            ->where('pending', false)
            ->whereBetween('booking_date', [$period->start, $period->end])
            ->get(['booking_date', 'amount'])
            ->groupBy(fn (BankTransaction $transaction): string => $transaction->booking_date->format('Y-m'));

        return [
            'labels' => $months->map(fn (CarbonImmutable $month): string => $month->translatedFormat('M Y'))->all(),
            'income' => $months->map(fn (CarbonImmutable $month): float => round((float) $transactions->get($month->format('Y-m'), collect())->where('amount', '>', 0)->sum('amount'), 2))->all(),
            'expenses' => $months->map(fn (CarbonImmutable $month): float => abs(round((float) $transactions->get($month->format('Y-m'), collect())->where('amount', '<', 0)->sum('amount'), 2)))->all(),
        ];
    }

    /** @return array{unmapped_transactions: int, unprocessed_documents: ?int, assignment_rate: float} */
    public function documentStats(): array
    {
        $transactions = BankTransaction::query()->where('pending', false);
        $total = (clone $transactions)->count();
        $unmapped = (clone $transactions)->whereDoesntHave('mappings')->count();
        $rate = $total > 0 ? round((($total - $unmapped) / $total) * 100, 1) : 100.0;

        $unprocessed = Cache::remember('dashboard:paperless:unprocessed', now()->addMinutes(5), function (): ?int {
            try {
                $documentCount = app(PaperlessClient::class)->documents(perPage: 1)['count'];
                $mapped = BankTransactionMapping::query()->whereNotNull('document_id')->distinct()->count('document_id');

                return max(0, $documentCount - $mapped);
            } catch (Throwable $exception) {
                report($exception);

                return null;
            }
        });

        return ['unmapped_transactions' => $unmapped, 'unprocessed_documents' => $unprocessed, 'assignment_rate' => $rate];
    }

    /** @return array{open_value: float, open_count: int, approved_count: int, acceptance_rate: float} */
    public function quoteStats(DashboardPeriod $period): array
    {
        $base = Quote::query()->where('is_archived', false)->whereDate('quote_date_created', '<=', $period->end);
        $open = (clone $base)->whereIn('quote_status_id', [QuoteStatus::Draft->value, QuoteStatus::Sent->value]);
        $periodQuotes = Quote::query()->where('is_archived', false)
            ->whereBetween('quote_date_created', [$period->start, $period->end]);
        $approved = (clone $periodQuotes)->where('quote_status_id', QuoteStatus::Approved->value)->count();
        $decided = (clone $periodQuotes)->whereIn('quote_status_id', [QuoteStatus::Approved->value, QuoteStatus::Rejected->value])->count();

        return [
            'open_value' => round((float) (clone $open)->join('ip_quote_amounts', 'ip_quotes.quote_id', '=', 'ip_quote_amounts.quote_id')->sum('ip_quote_amounts.quote_total'), 2),
            'open_count' => (clone $open)->count(),
            'approved_count' => $approved,
            'acceptance_rate' => $decided > 0 ? round(($approved / $decided) * 100, 1) : 0.0,
        ];
    }

    private function revenueBetween(CarbonImmutable $start, CarbonImmutable $end): float
    {
        return round((float) Invoice::query()
            ->join('ip_invoice_amounts', 'ip_invoices.invoice_id', '=', 'ip_invoice_amounts.invoice_id')
            ->where('ip_invoices.invoice_status_id', '!=', InvoiceStatus::Draft->value)
            ->where('ip_invoices.is_archived', false)
            ->whereBetween('ip_invoices.invoice_date_created', [$start, $end])
            ->sum('ip_invoice_amounts.invoice_total'), 2);
    }

    /** @return Collection<string, float> */
    private function invoiceTotalsByMonth(CarbonImmutable $start, CarbonImmutable $end): Collection
    {
        return Invoice::query()
            ->where('invoice_status_id', '!=', InvoiceStatus::Draft->value)
            ->where('is_archived', false)
            ->whereBetween('invoice_date_created', [$start, $end])
            ->with('amount')
            ->get()
            ->groupBy(fn (Invoice $invoice): string => $invoice->invoice_date_created->format('Y-m'))
            ->map(fn (Collection $invoices): float => round((float) $invoices->sum(fn (Invoice $invoice): float => (float) ($invoice->amount?->invoice_total ?? 0)), 2));
    }

    /** @return Collection<int, CarbonImmutable> */
    private function months(DashboardPeriod $period): Collection
    {
        $months = collect();
        $cursor = $period->start->startOfMonth();
        $end = $period->end->startOfMonth();

        while ($cursor->lessThanOrEqualTo($end) && $months->count() < 24) {
            $months->push($cursor);
            $cursor = $cursor->addMonth();
        }

        return $months;
    }
}

<?php

namespace App\Features\Reports\Services;

use App\Features\Invoices\Enums\InvoiceStatus;
use App\Features\Invoices\Models\Invoice;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;

final class InvoiceReportService
{
    /** @return Builder<Invoice> */
    public function query(
        CarbonInterface $start,
        CarbonInterface $end,
        ?int $status = null,
        ?int $clientId = null,
        ?string $type = null,
    ): Builder {
        return Invoice::query()
            ->with(['client', 'amount'])
            ->where('is_archived', false)
            ->whereBetween('invoice_date_created', [$start->toDateString(), $end->toDateString()])
            ->when($type, fn (Builder $query, string $value): Builder => $query->where('invoice_type', $value))
            ->when($status, fn (Builder $query, int $value): Builder => $query->where('invoice_status_id', $value))
            ->when($clientId, fn (Builder $query, int $value): Builder => $query->where('client_id', $value));
    }

    /** @return array{count: int, paid: int, open: int, partial: int, total: float} */
    public function summary(
        CarbonInterface $start,
        CarbonInterface $end,
        ?int $status = null,
        ?int $clientId = null,
        ?string $type = null,
    ): array {
        $invoices = $this->query($start, $end, $status, $clientId, $type)->get();

        return [
            'count' => $invoices->count(),
            'paid' => $invoices->filter(fn (Invoice $invoice): bool => $this->paymentStatus($invoice) === 'Bezahlt')->count(),
            'open' => $invoices->filter(fn (Invoice $invoice): bool => $this->paymentStatus($invoice) === 'Offen')->count(),
            'partial' => $invoices->filter(fn (Invoice $invoice): bool => $this->paymentStatus($invoice) === 'Teilbezahlt')->count(),
            'total' => round((float) $invoices->sum(fn (Invoice $invoice): float => (float) ($invoice->amount?->invoice_total ?? 0)), 2),
        ];
    }

    public function paymentStatus(Invoice $invoice): string
    {
        if ($invoice->invoice_status_id === InvoiceStatus::Paid || (float) ($invoice->amount?->invoice_balance ?? 0) === 0.0) {
            return 'Bezahlt';
        }

        if ((float) ($invoice->amount?->invoice_paid ?? 0) > 0) {
            return 'Teilbezahlt';
        }

        return 'Offen';
    }
}

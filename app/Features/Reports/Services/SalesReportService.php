<?php

namespace App\Features\Reports\Services;

use App\Features\Invoices\Models\InvoiceItem;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;

final class SalesReportService
{
    /** @return Builder<InvoiceItem> */
    public function query(
        CarbonInterface $start,
        CarbonInterface $end,
        ?int $status = null,
        ?int $clientId = null,
        ?int $taxRateId = null,
        ?string $invoiceType = null,
    ): Builder {
        return InvoiceItem::query()
            ->with(['invoice.client', 'taxRate', 'amount'])
            ->when($taxRateId, fn (Builder $query, int $value): Builder => $query->where('item_tax_rate_id', $value))
            ->whereHas('invoice', fn (Builder $query): Builder => $query
                ->where('is_archived', false)
                ->whereBetween('invoice_date_created', [$start->toDateString(), $end->toDateString()])
                ->when($invoiceType, fn (Builder $query, string $value): Builder => $query->where('invoice_type', $value))
                ->when($status, fn (Builder $query, int $value): Builder => $query->where('invoice_status_id', $value))
                ->when($clientId, fn (Builder $query, int $value): Builder => $query->where('client_id', $value)));
    }

    /** @return array{count: int, net: float, tax: float, gross: float} */
    public function summary(
        CarbonInterface $start,
        CarbonInterface $end,
        ?int $status = null,
        ?int $clientId = null,
        ?int $taxRateId = null,
        ?string $invoiceType = null,
    ): array {
        $items = $this->query($start, $end, $status, $clientId, $taxRateId, $invoiceType)->get();

        return [
            'count' => $items->count(),
            'net' => round((float) $items->sum(fn (InvoiceItem $item): float => (float) ($item->amount?->item_subtotal ?? 0)), 2),
            'tax' => round((float) $items->sum(fn (InvoiceItem $item): float => (float) ($item->amount?->item_tax_total ?? 0)), 2),
            'gross' => round((float) $items->sum(fn (InvoiceItem $item): float => (float) ($item->amount?->item_total ?? 0)), 2),
        ];
    }
}

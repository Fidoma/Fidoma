<?php

namespace App\Features\Reports\Services;

use App\Features\Invoices\Enums\InvoiceStatus;
use App\Features\Invoices\Models\InvoiceItem;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;

final class TaxReportService
{
    /** @return Builder<InvoiceItem> */
    public function query(CarbonInterface $start, CarbonInterface $end): Builder
    {
        return InvoiceItem::query()
            ->selectRaw('MIN(ip_invoice_items.item_id) as item_id')
            ->selectRaw('ip_invoice_items.item_tax_rate_id as report_tax_rate_id')
            ->selectRaw('ip_tax_rates.tax_rate_name as report_tax_name')
            ->selectRaw('ip_tax_rates.tax_rate_percent as report_tax_percent')
            ->selectRaw('SUM(ip_invoice_item_amounts.item_subtotal) as report_net')
            ->selectRaw('SUM(ip_invoice_item_amounts.item_tax_total) as report_tax')
            ->selectRaw('SUM(ip_invoice_item_amounts.item_total) as report_gross')
            ->join('ip_invoice_item_amounts', 'ip_invoice_items.item_id', '=', 'ip_invoice_item_amounts.item_id')
            ->join('ip_invoices', 'ip_invoice_items.invoice_id', '=', 'ip_invoices.invoice_id')
            ->leftJoin('ip_tax_rates', 'ip_invoice_items.item_tax_rate_id', '=', 'ip_tax_rates.tax_rate_id')
            ->where('ip_invoices.is_archived', false)
            ->where('ip_invoices.invoice_status_id', '!=', InvoiceStatus::Draft->value)
            ->whereBetween('ip_invoices.invoice_date_created', [$start->toDateString(), $end->toDateString()])
            ->groupBy(
                'ip_invoice_items.item_tax_rate_id',
                'ip_tax_rates.tax_rate_name',
                'ip_tax_rates.tax_rate_percent',
            );
    }

    /** @return array{net: float, tax: float, gross: float, input_tax: null, difference: null} */
    public function summary(CarbonInterface $start, CarbonInterface $end): array
    {
        $rows = $this->query($start, $end)->get();

        return [
            'net' => round((float) $rows->sum('report_net'), 2),
            'tax' => round((float) $rows->sum('report_tax'), 2),
            'gross' => round((float) $rows->sum('report_gross'), 2),
            'input_tax' => null,
            'difference' => null,
        ];
    }

    public function label(InvoiceItem $row): string
    {
        if ($row->getAttribute('report_tax_rate_id') === null) {
            return 'Unklar';
        }

        $rate = (float) $row->getAttribute('report_tax_percent');
        $name = mb_strtolower((string) $row->getAttribute('report_tax_name'));

        if ($rate === 0.0 && (str_contains($name, 'steuerfrei') || str_contains($name, 'befreit'))) {
            return 'Steuerfrei';
        }

        return rtrim(rtrim(number_format($rate, 2, ',', ''), '0'), ',').' %';
    }
}

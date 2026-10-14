<?php

namespace App\Features\Invoices\Actions;

use App\Features\Invoices\Enums\InvoiceStatus;
use App\Features\Invoices\Models\Invoice;
use App\Features\Invoices\Models\InvoiceTaxRate;
use App\Features\Quotes\Services\QuoteAmountCalculator;

final class RecalculateInvoice
{
    public function __construct(private readonly QuoteAmountCalculator $calculator) {}

    public function handle(Invoice $invoice): Invoice
    {
        $items = $invoice->items()->with('taxRate')->get();
        $taxRates = $invoice->invoiceTaxRates()->with('taxRate')->get();
        $amounts = $this->calculator->calculate(
            $items->mapWithKeys(fn ($item): array => [$item->getKey() => [
                'quantity' => $item->item_quantity, 'price' => $item->item_price,
                'tax_percent' => $item->taxRate?->tax_rate_percent,
                'tax_included' => $item->item_tax_included,
                'discount_amount' => $item->item_discount_amount,
            ]])->all(),
            $taxRates->map(fn (InvoiceTaxRate $rate): array => [
                'tax_percent' => $rate->taxRate?->tax_rate_percent,
                'include_item_tax' => $rate->include_item_tax,
            ])->all(),
            (float) $invoice->invoice_discount_amount,
            (float) $invoice->invoice_discount_percent,
            $invoice->invoice_type->sign(),
        );

        foreach ($items as $item) {
            $itemAmount = $amounts['items'][$item->getKey()];
            $item->amount()->updateOrCreate([], [
                'item_subtotal' => $itemAmount['subtotal'], 'item_tax_total' => $itemAmount['tax'],
                'item_discount' => $itemAmount['discount'], 'item_total' => $itemAmount['total'],
            ]);
        }

        foreach ($taxRates as $rate) {
            $base = $amounts['subtotal'] + ($rate->include_item_tax ? $amounts['itemTax'] : 0);
            $rate->updateQuietly([
                'invoice_tax_rate_amount' => round($base * (float) ($rate->taxRate?->tax_rate_percent ?? 0) / 100, 2),
            ]);
        }

        $paid = round((float) $invoice->payments()->sum('payment_amount'), 2);
        $balance = round($amounts['total'] - $paid, 2);
        $invoice->amount()->updateOrCreate([], [
            'invoice_sign' => (string) $invoice->invoice_type->sign(), 'invoice_item_subtotal' => $amounts['subtotal'],
            'invoice_item_tax_total' => $amounts['itemTax'], 'invoice_tax_total' => $amounts['quoteTax'],
            'invoice_total' => $amounts['total'], 'invoice_paid' => $paid, 'invoice_balance' => $balance,
        ]);

        if ($amounts['total'] > 0 && $balance <= 0) {
            $invoice->updateQuietly(['invoice_status_id' => InvoiceStatus::Paid, 'is_read_only' => true]);
        } elseif ($balance > 0 && $invoice->invoice_status_id === InvoiceStatus::Paid) {
            $invoice->updateQuietly(['invoice_status_id' => InvoiceStatus::Sent, 'is_read_only' => false]);
        }

        return $invoice->refresh()->load('amount');
    }
}

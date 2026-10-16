<?php

namespace App\Features\Quotes\Actions;

use App\Features\Invoices\Actions\CreateInvoice;
use App\Features\Invoices\Actions\RecalculateInvoice;
use App\Features\Invoices\Enums\InvoiceStatus;
use App\Features\Invoices\Models\Invoice;
use App\Features\Invoices\Services\InvoiceDefaults;
use App\Features\Quotes\Enums\QuoteStatus;
use App\Features\Quotes\Models\Quote;
use Illuminate\Support\Facades\DB;

final class ConvertQuoteToInvoice
{
    public function handle(Quote $quote): Invoice
    {
        return DB::transaction(function () use ($quote): Invoice {
            /** @var Quote $locked */
            $locked = Quote::query()->lockForUpdate()->findOrFail($quote->getKey());

            if ($locked->invoice_id && ($invoice = Invoice::query()->find($locked->invoice_id))) {
                return $invoice;
            }

            $invoiceDefaults = app(InvoiceDefaults::class);
            $invoice = app(CreateInvoice::class)->handle([
                'client_id' => $locked->client_id,
                'invoice_status_id' => InvoiceStatus::Draft,
                'invoice_date_created' => now()->toDateString(),
                'invoice_date_due' => now()->addDays($invoiceDefaults->dueDays())->toDateString(),
                'invoice_discount_amount' => $locked->quote_discount_amount,
                'invoice_discount_percent' => $locked->quote_discount_percent,
                'invoice_terms' => $locked->notes ?: $invoiceDefaults->terms(),
                'payment_method' => $invoiceDefaults->paymentMethodId(),
            ]);

            foreach ($locked->items()->get() as $item) {
                $invoice->items()->create([
                    'item_tax_rate_id' => $item->item_tax_rate_id,
                    'item_tax_included' => $item->item_tax_included,
                    'item_product_id' => $item->item_product_id,
                    'item_date_added' => now()->toDateString(),
                    'item_name' => $item->item_name,
                    'item_description' => $item->item_description,
                    'item_quantity' => $item->item_quantity,
                    'item_price' => $item->item_price,
                    'item_discount_amount' => $item->item_discount_amount,
                    'item_order' => $item->item_order,
                    'item_is_recurring' => false,
                    'item_product_unit_id' => $item->item_product_unit_id,
                ]);
            }

            foreach ($locked->quoteTaxRates()->get() as $rate) {
                $invoice->invoiceTaxRates()->create([
                    'tax_rate_id' => $rate->tax_rate_id,
                    'include_item_tax' => $rate->include_item_tax,
                    'invoice_tax_rate_amount' => 0,
                ]);
            }

            app(RecalculateInvoice::class)->handle($invoice);
            $locked->update([
                'invoice_id' => $invoice->getKey(),
                'quote_status_id' => QuoteStatus::Approved,
            ]);

            return $invoice->refresh();
        });
    }
}

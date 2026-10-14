<?php

namespace App\Features\Invoices\Services;

use App\Features\Invoices\Models\Invoice;

final class InvoiceDocumentData
{
    public function prepare(Invoice $invoice): Invoice
    {
        return $invoice->loadMissing([
            'amount',
            'client',
            'invoiceTaxRates.taxRate',
            'items.amount',
            'items.product',
            'items.taxRate',
            'parentInvoice',
            'user',
        ]);
    }
}

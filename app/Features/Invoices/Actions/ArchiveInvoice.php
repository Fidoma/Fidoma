<?php

namespace App\Features\Invoices\Actions;

use App\Features\Invoices\Models\Invoice;

final class ArchiveInvoice
{
    public function handle(Invoice $invoice): Invoice
    {
        $invoice->update([
            'is_archived' => true,
            'is_read_only' => true,
        ]);

        return $invoice;
    }
}

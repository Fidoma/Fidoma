<?php

namespace App\Features\Invoices\Actions;

use App\Features\Invoices\Models\Invoice;
use App\Features\Invoices\Services\InvoiceAuditLogger;

final class ArchiveInvoice
{
    public function __construct(private readonly InvoiceAuditLogger $auditLogger) {}

    public function handle(Invoice $invoice): Invoice
    {
        $invoice->update([
            'is_archived' => true,
            'is_read_only' => true,
        ]);

        $this->auditLogger->log(
            $invoice,
            'invoice_archived',
            'Rechnung '.$invoice->invoice_number.' wurde archiviert.',
        );

        return $invoice;
    }
}

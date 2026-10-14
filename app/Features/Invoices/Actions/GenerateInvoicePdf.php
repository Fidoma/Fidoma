<?php

namespace App\Features\Invoices\Actions;

use App\Features\Invoices\Models\Invoice;
use App\Features\Invoices\Services\InvoiceDocumentData;
use Barryvdh\DomPDF\Facade\Pdf;
use Barryvdh\DomPDF\PDF as DomPdf;
use Illuminate\Support\Str;

final class GenerateInvoicePdf
{
    public function __construct(private readonly InvoiceDocumentData $documentData) {}

    public function handle(Invoice $invoice): DomPdf
    {
        return Pdf::loadView('features.invoices.documents.invoice', [
            'invoice' => $this->documentData->prepare($invoice),
            'preview' => false,
        ])->setPaper('a4');
    }

    public function filename(Invoice $invoice): string
    {
        $type = Str::slug($invoice->invoice_type->label());
        $number = Str::slug($invoice->invoice_number ?: (string) $invoice->getKey());

        return "{$type}-{$number}.pdf";
    }
}

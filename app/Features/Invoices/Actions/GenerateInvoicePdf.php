<?php

namespace App\Features\Invoices\Actions;

use App\Features\Invoices\Models\Invoice;
use App\Features\Invoices\Models\InvoiceLayout;
use App\Features\Invoices\Services\InvoiceDocumentData;
use App\Features\Invoices\Services\InvoiceTemplateData;
use App\Features\Invoices\Services\InvoiceTemplateRenderer;
use Barryvdh\DomPDF\Facade\Pdf;
use Barryvdh\DomPDF\PDF as DomPdf;
use Illuminate\Support\Str;

final class GenerateInvoicePdf
{
    public function __construct(
        private readonly InvoiceDocumentData $documentData,
        private readonly InvoiceTemplateData $templateData,
        private readonly InvoiceTemplateRenderer $templateRenderer,
    ) {}

    public function handle(Invoice $invoice): DomPdf
    {
        if ($layout = InvoiceLayout::query()->where('type', 'invoice')->first()) {
            return Pdf::loadHTML($this->templateRenderer->render(
                $layout->html,
                $layout->css,
                $this->templateData->fromInvoice($invoice),
            ))->setPaper('a4');
        }

        return Pdf::loadView('invoices::documents.invoice', [
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

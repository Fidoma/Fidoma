<?php

namespace App\Features\Invoices\Http\Controllers;

use App\Features\Invoices\Actions\GenerateInvoicePdf;
use App\Features\Invoices\Actions\GenerateInvoiceXRechnung;
use App\Features\Invoices\Models\Invoice;
use App\Features\Invoices\Services\InvoiceDocumentData;
use DomainException;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Response;

final class InvoiceDocumentController
{
    public function preview(Invoice $invoice, InvoiceDocumentData $documentData): View
    {
        return view('features.invoices.documents.invoice', [
            'invoice' => $documentData->prepare($invoice),
            'preview' => true,
        ]);
    }

    public function pdf(Invoice $invoice, GenerateInvoicePdf $generateInvoicePdf): Response
    {
        return $generateInvoicePdf
            ->handle($invoice)
            ->stream($generateInvoicePdf->filename($invoice));
    }

    public function xrechnung(Invoice $invoice, GenerateInvoiceXRechnung $generator): Response
    {
        try {
            return response($generator->handle($invoice), 200, [
                'Content-Type' => 'application/xml; charset=UTF-8',
                'Content-Disposition' => 'attachment; filename="'.$generator->filename($invoice).'"',
                'X-Content-Type-Options' => 'nosniff',
            ]);
        } catch (DomainException $exception) {
            return response($exception->getMessage(), 422, [
                'Content-Type' => 'text/plain; charset=UTF-8',
            ]);
        }
    }
}

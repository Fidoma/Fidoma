<?php

namespace App\Features\Invoices\Http\Controllers;

use App\Features\Invoices\Actions\GenerateInvoicePdf;
use App\Features\Invoices\Actions\GenerateInvoiceXRechnung;
use App\Features\Invoices\Models\Invoice;
use App\Features\Invoices\Models\InvoiceLayout;
use App\Features\Invoices\Services\InvoiceDocumentData;
use App\Features\Invoices\Services\InvoiceTemplateData;
use App\Features\Invoices\Services\InvoiceTemplateRenderer;
use DomainException;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Response;

final class InvoiceDocumentController
{
    public function preview(
        Invoice $invoice,
        InvoiceDocumentData $documentData,
        InvoiceTemplateData $templateData,
        InvoiceTemplateRenderer $templateRenderer,
    ): View|Response {
        if ($layout = InvoiceLayout::query()->where('type', 'invoice')->first()) {
            return response($templateRenderer->render(
                $layout->html,
                $layout->css,
                $templateData->fromInvoice($invoice),
            ))->header('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0');
        }

        return view('features.invoices.documents.invoice', [
            'invoice' => $documentData->prepare($invoice),
            'preview' => true,
        ]);
    }

    public function pdf(Invoice $invoice, GenerateInvoicePdf $generateInvoicePdf): Response
    {
        return $generateInvoicePdf
            ->handle($invoice)
            ->stream($generateInvoicePdf->filename($invoice))
            ->withHeaders([
                'Cache-Control' => 'no-store, no-cache, must-revalidate, max-age=0',
                'Pragma' => 'no-cache',
                'Expires' => '0',
            ]);
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

<?php

namespace App\Features\Invoices\Http\Controllers;

use App\Features\Invoices\Services\InvoiceTemplateData;
use App\Features\Invoices\Services\InvoiceTemplateRenderer;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Response;

final class InvoiceLayoutPreviewController
{
    public function __invoke(InvoiceTemplateRenderer $renderer, InvoiceTemplateData $data): Response
    {
        $draft = session('invoice-layout-preview');
        abort_unless(is_array($draft) && isset($draft['html'], $draft['css']), 404);

        return Pdf::loadHTML($renderer->render($draft['html'], $draft['css'], $data->sample()))
            ->setPaper('a4')
            ->stream('rechnungslayout-vorschau.pdf');
    }
}

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
        $draft = session('pdf-layout-preview');
        abort_unless(is_array($draft) && isset($draft['html'], $draft['css']), 404);

        $type = in_array($draft['type'] ?? null, ['invoice', 'quote', 'reservation'], true)
            ? $draft['type']
            : 'invoice';

        return Pdf::loadHTML($renderer->render($draft['html'], $draft['css'], $data->sample($type)))
            ->setPaper('a4')
            ->stream('rechnungslayout-vorschau.pdf');
    }
}

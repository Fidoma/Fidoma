<?php

namespace App\Features\Quotes\Actions;

use App\Features\Invoices\Models\InvoiceLayout;
use App\Features\Invoices\Services\InvoiceLayoutDefaults;
use App\Features\Invoices\Services\InvoiceTemplateData;
use App\Features\Invoices\Services\InvoiceTemplateRenderer;
use App\Features\Quotes\Models\Quote;
use Barryvdh\DomPDF\Facade\Pdf;
use Barryvdh\DomPDF\PDF as DomPdf;
use Illuminate\Support\Str;

final class GenerateQuotePdf
{
    public function __construct(
        private readonly InvoiceLayoutDefaults $layoutDefaults,
        private readonly InvoiceTemplateData $templateData,
        private readonly InvoiceTemplateRenderer $templateRenderer,
    ) {}

    public function handle(Quote $quote): DomPdf
    {
        $layout = InvoiceLayout::query()->where('type', 'quote')->first();

        return Pdf::loadHTML($this->templateRenderer->render(
            $layout?->html ?? $this->layoutDefaults->html('quote'),
            $layout?->css ?? $this->layoutDefaults->css(),
            $this->templateData->fromQuote($quote),
        ))->setPaper('a4');
    }

    public function filename(Quote $quote): string
    {
        $number = Str::slug($quote->quote_number ?: (string) $quote->getKey());

        return "angebot-{$number}.pdf";
    }
}

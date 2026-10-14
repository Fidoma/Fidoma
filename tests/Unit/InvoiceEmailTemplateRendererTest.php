<?php

namespace Tests\Unit;

use App\Features\Clients\Models\Client;
use App\Features\EmailTemplates\Services\InvoiceEmailTemplateRenderer;
use App\Features\Invoices\Enums\InvoiceStatus;
use App\Features\Invoices\Enums\InvoiceType;
use App\Features\Invoices\Models\Invoice;
use App\Features\Invoices\Models\InvoiceAmount;
use App\Features\Invoices\Services\InvoiceDocumentData;
use App\Models\User;
use Illuminate\Support\Collection;
use Tests\TestCase;

class InvoiceEmailTemplateRendererTest extends TestCase
{
    public function test_it_renders_invoice_placeholders_and_escapes_customer_values_for_html(): void
    {
        $invoice = new Invoice([
            'invoice_type' => InvoiceType::Invoice,
            'invoice_status_id' => InvoiceStatus::Sent,
            'invoice_number' => 'RE-1001',
            'invoice_date_created' => '2026-10-13',
            'invoice_date_due' => '2026-11-12',
            'invoice_terms' => 'Zahlbar innerhalb von 30 Tagen.',
        ]);

        $invoice->setRelation('client', new Client([
            'client_name' => '<script>alert(1)</script>',
            'client_email' => 'kunde@example.com',
        ]));
        $invoice->setRelation('amount', new InvoiceAmount([
            'invoice_total' => '119.00',
            'invoice_balance' => '119.00',
        ]));
        $invoice->setRelation('invoiceTaxRates', new Collection);
        $invoice->setRelation('items', new Collection);
        $invoice->setRelation('parentInvoice', null);
        $invoice->setRelation('user', new User([
            'name' => 'Erika Beispiel',
            'company_name' => 'Beispiel GmbH',
            'iban' => 'DE02120300000000202051',
        ]));

        $renderer = new InvoiceEmailTemplateRenderer(new InvoiceDocumentData);
        $result = $renderer->render(
            $invoice,
            '<p>Hallo {{{client_name}}}, Rechnung {{{invoice_number}}}: {{{invoice_total}}} – {{{user_company}}}, {{{user_iban}}}</p>',
            escapeValues: true,
        );

        self::assertSame(
            '<p>Hallo &lt;script&gt;alert(1)&lt;/script&gt;, Rechnung RE-1001: 119,00 € – Beispiel GmbH, DE02120300000000202051</p>',
            $result,
        );
    }
}

<?php

namespace Tests\Unit;

use App\Features\Clients\Models\Client;
use App\Features\Invoices\Actions\GenerateInvoiceXRechnung;
use App\Features\Invoices\Enums\InvoiceStatus;
use App\Features\Invoices\Enums\InvoiceType;
use App\Features\Invoices\Models\Invoice;
use App\Features\Invoices\Models\InvoiceAmount;
use App\Features\Invoices\Models\InvoiceItem;
use App\Features\Invoices\Models\InvoiceItemAmount;
use App\Features\Invoices\Services\InvoiceDocumentData;
use App\Features\TaxRates\Models\TaxRate;
use App\Models\User;
use Illuminate\Support\Collection;
use Tests\TestCase;

class GenerateInvoiceXRechnungTest extends TestCase
{
    public function test_it_generates_xrechnung_30_xml(): void
    {
        $seller = new User([
            'name' => 'Max Mustermann',
            'company_name' => 'Muster GmbH',
            'invoicing_contact' => 'Max Mustermann',
            'address_line_1' => 'Musterstraße 1',
            'postal_code' => '10115',
            'city' => 'Berlin',
            'country' => 'DE',
            'phone' => '+49 30 123456',
            'email' => 'rechnung@muster.test',
            'vat_id' => 'DE123456789',
            'iban' => 'DE02120300000000202051',
            'bic' => 'BYLADEM1001',
        ]);
        $seller->id = 1;

        $buyer = new Client([
            'client_name' => 'Erika Beispiel',
            'client_company' => 'Beispiel AG',
            'client_address_1' => 'Kundenweg 2',
            'client_zip' => '20095',
            'client_city' => 'Hamburg',
            'client_country' => 'DE',
            'client_email' => 'einkauf@beispiel.test',
            'client_einvoicing_reference' => '04011000-12345-67',
        ]);
        $buyer->client_id = 2;

        $taxRate = new TaxRate(['tax_rate_name' => '19 %', 'tax_rate_percent' => 19]);
        $itemAmount = new InvoiceItemAmount([
            'item_subtotal' => 200,
            'item_tax_total' => 38,
            'item_discount' => 0,
            'item_total' => 238,
        ]);
        $item = new InvoiceItem([
            'item_name' => 'Beratung',
            'item_description' => 'Technische Beratung',
            'item_quantity' => 2,
            'item_price' => 100,
            'item_discount_amount' => 0,
            'item_tax_included' => false,
            'item_product_unit' => 'Stunde',
        ]);
        $item->setRelation('amount', $itemAmount);
        $item->setRelation('taxRate', $taxRate);
        $item->setRelation('product', null);

        $invoice = new Invoice([
            'invoice_type' => InvoiceType::Invoice,
            'invoice_status_id' => InvoiceStatus::Open,
            'invoice_number' => 'RE-2026-1001',
            'invoice_date_created' => '2026-10-13',
            'invoice_date_due' => '2026-11-12',
            'invoice_discount_amount' => 0,
            'invoice_discount_percent' => 0,
            'invoice_terms' => 'Zahlbar innerhalb von 30 Tagen.',
        ]);
        $invoice->invoice_id = 10;
        $invoice->setRelation('user', $seller);
        $invoice->setRelation('client', $buyer);
        $invoice->setRelation('items', new Collection([$item]));
        $invoice->setRelation('amount', new InvoiceAmount([
            'invoice_total' => 238,
            'invoice_paid' => 0,
            'invoice_balance' => 238,
        ]));
        $invoice->setRelation('invoiceTaxRates', new Collection);
        $invoice->setRelation('parentInvoice', null);

        $xml = (new GenerateInvoiceXRechnung(new InvoiceDocumentData))->handle($invoice);

        $this->assertStringContainsString('CrossIndustryInvoice', $xml);
        $this->assertStringContainsString('RE-2026-1001', $xml);
        $this->assertStringContainsString('04011000-12345-67', $xml);
        $this->assertNotFalse(simplexml_load_string($xml));
    }
}

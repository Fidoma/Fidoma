<?php

namespace Tests\Feature\Reports;

use App\Features\Clients\Models\Client;
use App\Features\Invoices\Actions\RecalculateInvoice;
use App\Features\Invoices\Enums\InvoiceStatus;
use App\Features\Invoices\Enums\InvoiceType;
use App\Features\Invoices\Models\Invoice;
use App\Features\Invoices\Models\InvoiceItem;
use App\Features\PaymentMethods\Models\PaymentMethod;
use App\Features\Payments\Models\Payment;
use App\Features\Reports\Livewire\UstvaDetailsTable;
use App\Features\Reports\Services\UstvaReportService;
use App\Features\TaxRates\Models\TaxRate;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class UstvaReportServiceTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Client $client;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->client = Client::query()->create([
            'client_name' => 'Musterkunde GmbH',
            'client_active' => true,
        ]);
    }

    public function test_soll_report_groups_mixed_rates_and_excludes_drafts(): void
    {
        $rate19 = $this->taxRate('Standard', 19);
        $rate7 = $this->taxRate('Ermäßigt', 7);
        $invoice = $this->invoice('RE-100', InvoiceType::Invoice, InvoiceStatus::Open, '2030-10-10');
        $item19 = $this->item($invoice, $rate19, 100);
        $this->item($invoice, $rate7, 200, 2);
        app(RecalculateInvoice::class)->handle($invoice);

        $draft = $this->invoice('RE-DRAFT', InvoiceType::Invoice, InvoiceStatus::Draft, '2030-10-10');
        $this->item($draft, $rate19, 500);
        app(RecalculateInvoice::class)->handle($draft);

        $report = app(UstvaReportService::class)->report(
            CarbonImmutable::parse('2030-10-01'),
            CarbonImmutable::parse('2030-10-31'),
            'soll',
            2026,
        );

        $this->assertSame(100.0, $this->row($report, '81')['net']);
        $this->assertSame(19.0, $this->row($report, '81')['tax']);
        $this->assertSame(200.0, $this->row($report, '86')['net']);
        $this->assertSame(14.0, $this->row($report, '86')['tax']);
        $this->assertSame(300.0, $report['net']);
        $this->assertSame(33.0, $report['output_tax']);
        $this->assertSame(1, $this->row($report, '81')['excluded_draft_count']);
        $this->assertStringContainsString('Status „Entwurf“', implode(' ', $report['warnings']));

        Livewire::actingAs($this->user)
            ->test(UstvaDetailsTable::class, [
                'code' => '81',
                'startDate' => '2030-10-01',
                'endDate' => '2030-10-31',
                'taxationType' => 'soll',
            ])
            ->assertCanSeeTableRecords([$item19])
            ->searchTable('RE-100')
            ->assertCanSeeTableRecords([$item19])
            ->sortTable('item_name');
    }

    public function test_credit_and_cancellation_reduce_the_taxable_amount(): void
    {
        $rate = $this->taxRate('Standard', 19);

        foreach ([
            ['RE-200', InvoiceType::Invoice, 100],
            ['GU-200', InvoiceType::Credit, 20],
            ['ST-200', InvoiceType::Cancellation, 10],
        ] as [$number, $type, $price]) {
            $invoice = $this->invoice($number, $type, InvoiceStatus::Open, '2026-11-10');
            $this->item($invoice, $rate, $price);
            app(RecalculateInvoice::class)->handle($invoice);
        }

        $report = app(UstvaReportService::class)->report(
            CarbonImmutable::parse('2026-11-01'),
            CarbonImmutable::parse('2026-11-30'),
            'soll',
            2026,
        );

        $this->assertSame(70.0, $this->row($report, '81')['net']);
        $this->assertSame(13.3, $this->row($report, '81')['tax']);
    }

    public function test_ist_report_allocates_a_partial_payment_to_net_and_tax(): void
    {
        $rate = $this->taxRate('Standard', 19);
        $invoice = $this->invoice('RE-300', InvoiceType::Invoice, InvoiceStatus::Open, '2026-09-10');
        $this->item($invoice, $rate, 100);
        app(RecalculateInvoice::class)->handle($invoice);
        $method = PaymentMethod::query()->create(['payment_method_name' => 'Überweisung']);
        Payment::query()->create([
            'invoice_id' => $invoice->getKey(),
            'payment_method_id' => $method->getKey(),
            'payment_date' => '2026-10-15',
            'payment_amount' => 59.50,
            'payment_note' => 'Teilzahlung',
        ]);

        $report = app(UstvaReportService::class)->report(
            CarbonImmutable::parse('2026-10-01'),
            CarbonImmutable::parse('2026-10-31'),
            'ist',
            2026,
        );

        $this->assertSame(50.0, $this->row($report, '81')['net']);
        $this->assertSame(9.5, $this->row($report, '81')['tax']);
        $this->assertSame('15.10.2026', $this->row($report, '81')['details'][0]['tax_period']);
    }

    public function test_unknown_and_zero_percent_rates_are_not_silently_classified(): void
    {
        $rate = $this->taxRate('Nullsatz ohne Steuerkennzeichen', 0);
        $invoice = $this->invoice('RE-400', InvoiceType::Invoice, InvoiceStatus::Open, '2026-12-10');
        $this->item($invoice, $rate, 100);
        app(RecalculateInvoice::class)->handle($invoice);

        $report = app(UstvaReportService::class)->report(
            CarbonImmutable::parse('2026-12-01'),
            CarbonImmutable::parse('2026-12-31'),
            'soll',
            2026,
        );

        $this->assertSame(0.0, $report['output_tax']);
        $this->assertStringContainsString('keiner UStVA-Kennzahl', implode(' ', $report['warnings']));
        $this->assertFalse($report['complete']);
    }

    private function taxRate(string $name, float $percent): TaxRate
    {
        return TaxRate::query()->create(['tax_rate_name' => $name, 'tax_rate_percent' => $percent]);
    }

    private function invoice(
        string $number,
        InvoiceType $type,
        InvoiceStatus $status,
        string $date = '2026-10-10',
    ): Invoice {
        return Invoice::query()->create([
            'invoice_type' => $type,
            'user_id' => $this->user->getKey(),
            'client_id' => $this->client->getKey(),
            'invoice_group_id' => 1,
            'invoice_status_id' => $status,
            'is_read_only' => false,
            'is_archived' => false,
            'invoice_password' => '',
            'invoice_date_created' => $date,
            'invoice_time_created' => '10:00:00',
            'invoice_date_due' => CarbonImmutable::parse($date)->addDays(14)->toDateString(),
            'invoice_number' => $number,
            'invoice_discount_amount' => 0,
            'invoice_discount_percent' => 0,
            'invoice_terms' => '',
            'invoice_url_key' => strtolower($number),
            'payment_method' => 0,
            'creditinvoice_parent_id' => null,
            'invoice_einvoicing_version' => '',
        ]);
    }

    private function item(Invoice $invoice, TaxRate $rate, float $price, int $order = 1): InvoiceItem
    {
        return InvoiceItem::query()->create([
            'invoice_id' => $invoice->getKey(),
            'item_tax_rate_id' => $rate->getKey(),
            'item_tax_included' => false,
            'item_name' => 'Leistung',
            'item_quantity' => 1,
            'item_price' => $price,
            'item_discount_amount' => 0,
            'item_order' => $order,
        ]);
    }

    /** @param array<string, mixed> $report */
    private function row(array $report, string $code): array
    {
        return collect($report['rows'])->firstWhere('code', $code);
    }
}

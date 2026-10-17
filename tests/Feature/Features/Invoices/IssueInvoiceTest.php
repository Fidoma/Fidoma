<?php

namespace Tests\Feature\Features\Invoices;

use App\Features\Clients\Models\Client;
use App\Features\InvoiceGroups\Models\InvoiceGroup;
use App\Features\Invoices\Actions\CreateInvoice;
use App\Features\Invoices\Actions\IssueInvoice;
use App\Features\Invoices\Actions\RecalculateInvoice;
use App\Features\Invoices\Enums\InvoiceStatus;
use App\Features\Invoices\Models\Invoice;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class IssueInvoiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_creating_an_invoice_creates_an_unnumbered_draft(): void
    {
        $user = User::factory()->create();
        $client = Client::query()->create([
            'client_name' => 'Testkunde',
            'client_active' => true,
        ]);
        $group = InvoiceGroup::query()->findOrFail(3);
        $nextNumber = $group->invoice_group_next_id;

        $invoice = $this->actingAs($user)->createInvoice($client->getKey());

        $this->assertSame(InvoiceStatus::Draft, $invoice->invoice_status_id);
        $this->assertNull($invoice->invoice_number);
        $this->assertFalse($invoice->is_read_only);
        $this->assertSame($nextNumber, $group->refresh()->invoice_group_next_id);
    }

    public function test_issuing_a_draft_generates_its_number_once_and_opens_it(): void
    {
        $user = User::factory()->create();
        $client = Client::query()->create([
            'client_name' => 'Testkunde',
            'client_active' => true,
        ]);
        $invoice = $this->actingAs($user)->createInvoice($client->getKey());
        $group = InvoiceGroup::query()->findOrFail($invoice->invoice_group_id);
        $nextNumber = $group->invoice_group_next_id;

        $issued = app(IssueInvoice::class)->handle($invoice);

        $this->assertSame(InvoiceStatus::Open, $issued->invoice_status_id);
        $this->assertSame((string) $nextNumber, $issued->invoice_number);
        $this->assertTrue($issued->is_read_only);
        $this->assertSame($nextNumber + 1, $group->refresh()->invoice_group_next_id);

        $issuedAgain = app(IssueInvoice::class)->handle($issued);

        $this->assertSame($issued->invoice_number, $issuedAgain->invoice_number);
        $this->assertSame($nextNumber + 1, $group->refresh()->invoice_group_next_id);
    }

    public function test_a_draft_that_becomes_paid_is_numbered_before_the_status_changes(): void
    {
        $user = User::factory()->create();
        $client = Client::query()->create([
            'client_name' => 'Testkunde',
            'client_active' => true,
        ]);
        $invoice = $this->actingAs($user)->createInvoice($client->getKey());
        $invoice->items()->create([
            'item_tax_rate_id' => 0,
            'item_tax_included' => false,
            'item_name' => 'Leistung',
            'item_quantity' => 1,
            'item_price' => 100,
            'item_discount_amount' => 0,
            'item_order' => 1,
        ]);
        app(RecalculateInvoice::class)->handle($invoice);
        $invoice->payments()->create([
            'payment_method_id' => 1,
            'payment_date' => '2026-10-17',
            'payment_amount' => 100,
            'payment_note' => 'Direkt bezahlt',
        ]);

        $invoice = app(RecalculateInvoice::class)->handle($invoice);

        $this->assertSame(InvoiceStatus::Paid, $invoice->invoice_status_id);
        $this->assertNotNull($invoice->invoice_number);
        $this->assertTrue($invoice->is_read_only);
    }

    private function createInvoice(int $clientId): Invoice
    {
        return app(CreateInvoice::class)->handle([
            'client_id' => $clientId,
            'invoice_date_created' => '2026-10-17',
            'invoice_date_due' => '2026-11-16',
            'invoice_number' => 'MUST-NOT-BE-USED',
            'invoice_status_id' => InvoiceStatus::Paid,
            'invoice_discount_amount' => 0,
            'invoice_discount_percent' => 0,
        ]);
    }
}

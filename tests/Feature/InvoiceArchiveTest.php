<?php

namespace Tests\Feature;

use App\Features\Clients\Filament\Resources\Clients\ClientResource;
use App\Features\Clients\Models\Client;
use App\Features\Invoices\Actions\ArchiveInvoice;
use App\Features\Invoices\Actions\IssueInvoice;
use App\Features\Invoices\Enums\InvoiceStatus;
use App\Features\Invoices\Enums\InvoiceType;
use App\Features\Invoices\Filament\Resources\Invoices\InvoiceResource;
use App\Features\Invoices\Filament\Resources\Invoices\Pages\EditInvoice;
use App\Features\Invoices\Filament\Resources\Invoices\Pages\ListInvoices;
use App\Features\Invoices\Models\Invoice;
use App\Features\Invoices\Models\InvoiceItem;
use App\Features\TaxRates\Models\TaxRate;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Tests\TestCase;

class InvoiceArchiveTest extends TestCase
{
    use RefreshDatabase;

    public function test_archiving_an_invoice_keeps_the_record_and_makes_it_read_only(): void
    {
        $invoice = $this->createInvoice();
        $invoice = app(IssueInvoice::class)->handle($invoice);

        app(ArchiveInvoice::class)->handle($invoice);

        $this->assertDatabaseHas('ip_invoices', [
            'invoice_id' => $invoice->getKey(),
            'is_archived' => true,
            'is_read_only' => true,
        ]);
    }

    public function test_invoice_index_offers_archiving_instead_of_deletion(): void
    {
        $user = User::factory()->create();
        app(IssueInvoice::class)->handle($this->createInvoice());

        $this->actingAs($user)
            ->get('/admin/invoices')
            ->assertOk()
            ->assertSeeText('Archiviert')
            ->assertSeeText('Archivieren')
            ->assertDontSeeText('Löschen');
    }

    public function test_a_draft_invoice_can_be_deleted(): void
    {
        $user = User::factory()->create();
        $invoice = $this->createInvoice();

        Livewire::actingAs($user)
            ->test(ListInvoices::class)
            ->assertTableActionVisible('delete', $invoice)
            ->callTableAction('delete', $invoice);

        $this->assertModelMissing($invoice);
    }

    public function test_draft_invoice_can_be_issued_and_becomes_numbered_and_read_only(): void
    {
        $user = User::factory()->create();
        $invoice = $this->createInvoice();
        $client = Client::query()->create([
            'client_name' => 'Testkunde',
            'client_active' => true,
        ]);
        $taxRate = TaxRate::query()->create([
            'tax_rate_name' => '19 %',
            'tax_rate_percent' => 19,
        ]);
        $invoice->update([
            'client_id' => $client->getKey(),
            'invoice_date_created' => '2027-01-10',
            'invoice_date_due' => '2027-02-10',
        ]);
        InvoiceItem::query()->create([
            'invoice_id' => $invoice->getKey(),
            'item_tax_rate_id' => $taxRate->getKey(),
            'item_tax_included' => false,
            'item_name' => 'Leistung',
            'item_quantity' => 1,
            'item_price' => 100,
            'item_discount_amount' => 0,
            'item_order' => 1,
        ]);

        Livewire::actingAs($user)
            ->test(EditInvoice::class, ['record' => $invoice->getRouteKey()])
            ->assertFormFieldEnabled('invoice_number')
            ->assertSee('Ausgestellt')
            ->assertSee('Zeile hinzufügen')
            ->assertSee('fi-fo-summary-table-actions')
            ->set('data.invoice_terms', 'Zahlbar innerhalb von 14 Tagen.')
            ->set('data.issue_invoice', true)
            ->assertHasNoErrors()
            ->assertNotified('Rechnung wurde ausgestellt.');

        $invoice->refresh();

        $this->assertSame(InvoiceStatus::Open, $invoice->invoice_status_id);
        $this->assertNotNull($invoice->invoice_number);
        $this->assertTrue($invoice->is_read_only);
        $this->assertSame('Zahlbar innerhalb von 14 Tagen.', $invoice->invoice_terms);
    }

    public function test_invoice_number_remains_editable_after_the_invoice_is_issued(): void
    {
        $user = User::factory()->create();
        $client = Client::query()->create([
            'client_name' => 'Testkunde',
            'client_active' => true,
        ]);
        $invoice = $this->createInvoice();
        $invoice->update(['client_id' => $client->getKey()]);
        $invoice = app(IssueInvoice::class)->handle($invoice);
        $originalDueDate = $invoice->invoice_date_due->toDateString();

        Livewire::actingAs($user)
            ->test(EditInvoice::class, ['record' => $invoice->getRouteKey()])
            ->assertFormFieldEnabled('invoice_number')
            ->assertFormFieldDisabled('client_id')
            ->assertFormFieldDisabled('invoice_date_due')
            ->set('data.invoice_number', 'RE-MANUELL-2026-1')
            ->set('data.invoice_date_due', '2027-12-31')
            ->call('save')
            ->assertHasNoErrors();

        $invoice->refresh();

        $this->assertSame('RE-MANUELL-2026-1', $invoice->invoice_number);
        $this->assertSame($originalDueDate, $invoice->invoice_date_due->toDateString());
    }

    public function test_invoice_list_uses_colored_numbers_and_filters_counterparts_by_type(): void
    {
        $user = User::factory()->create();
        $invoice = $this->createInvoice();
        $credit = $this->createInvoice();
        $credit->update([
            'invoice_number' => 'GU-TEST-1',
            'invoice_type' => InvoiceType::Credit,
        ]);
        $cancellation = $this->createInvoice();
        $cancellation->update([
            'invoice_number' => 'ST-TEST-1',
            'invoice_type' => InvoiceType::Cancellation,
        ]);

        $component = Livewire::actingAs($user)
            ->test(ListInvoices::class)
            ->assertSee('Gutschrift')
            ->assertSee('Storniert')
            ->assertDontSee('Belegart')
            ->set('activeTab', InvoiceType::Credit->value)
            ->assertCanSeeTableRecords([$credit])
            ->assertCanNotSeeTableRecords([$invoice, $cancellation])
            ->set('activeTab', InvoiceType::Cancellation->value)
            ->assertCanSeeTableRecords([$cancellation])
            ->assertCanNotSeeTableRecords([$invoice, $credit]);

        $component
            ->set('activeTab', 'all')
            ->assertTableColumnExists(
                'invoice_number',
                fn ($column): bool => $column->getUrl() === InvoiceResource::getUrl('edit', ['record' => $invoice]),
                $invoice,
            )
            ->assertTableColumnExists(
                'client.client_name',
                fn ($column): bool => $column->getUrl() === ClientResource::getUrl('view', ['record' => $invoice->client_id]),
                $invoice,
            )
            ->assertTableColumnExists(
                'invoice_date_created',
                fn ($column): bool => $column->getUrl() === null,
                $invoice,
            );

        $this->assertNull($component->instance()->getTable()->getRecordUrl($invoice));
    }

    private function createInvoice(): Invoice
    {
        return Invoice::query()->create([
            'invoice_type' => InvoiceType::Invoice,
            'user_id' => 1,
            'client_id' => 1,
            'invoice_group_id' => 3,
            'invoice_status_id' => InvoiceStatus::Draft,
            'is_read_only' => false,
            'invoice_date_created' => '2026-10-13',
            'invoice_time_created' => '12:00:00',
            'invoice_date_due' => '2026-11-12',
            'invoice_number' => null,
            'invoice_discount_amount' => 0,
            'invoice_discount_percent' => 0,
            'invoice_terms' => '',
            'invoice_url_key' => Str::random(32),
            'payment_method' => 0,
            'invoice_einvoicing_version' => '',
        ]);
    }
}

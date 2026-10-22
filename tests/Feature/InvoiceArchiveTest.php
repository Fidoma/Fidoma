<?php

namespace Tests\Feature;

use App\Features\Clients\Models\Client;
use App\Features\Invoices\Actions\ArchiveInvoice;
use App\Features\Invoices\Actions\IssueInvoice;
use App\Features\Invoices\Enums\InvoiceBillingType;
use App\Features\Invoices\Enums\InvoiceStatus;
use App\Features\Invoices\Enums\InvoiceType;
use App\Features\Invoices\Filament\Resources\Invoices\Pages\EditInvoice;
use App\Features\Invoices\Filament\Resources\Invoices\Pages\ListInvoices;
use App\Features\Invoices\Models\Invoice;
use App\Features\Invoices\Models\InvoiceItem;
use App\Features\TaxRates\Models\TaxRate;
use App\Models\User;
use Filament\Actions\Testing\TestAction;
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
            ->assertSee('Rechnung ausstellen')
            ->assertSee('Zeile hinzufügen')
            ->assertSee('fi-fo-table-repeater')
            ->callAction(
                TestAction::make('editPaymentTerms')->schemaComponent(),
                ['invoice_terms' => 'Zahlbar innerhalb von 14 Tagen.'],
            )
            ->assertSet('data.invoice_terms', 'Zahlbar innerhalb von 14 Tagen.')
            ->callAction(TestAction::make('issueInvoice')->schemaComponent())
            ->assertHasNoErrors()
            ->assertFormFieldDisabled('invoice_number')
            ->assertFormFieldDisabled('client_id')
            ->assertFormFieldDisabled('invoice_date_created')
            ->assertFormFieldDisabled('invoice_date_due')
            ->assertFormFieldDisabled('items')
            ->assertNotified('Rechnung wurde ausgestellt.');

        $invoice->refresh();

        $this->assertSame(InvoiceStatus::Open, $invoice->invoice_status_id);
        $this->assertNotNull($invoice->invoice_number);
        $this->assertTrue($invoice->is_read_only);
        $this->assertSame('Zahlbar innerhalb von 14 Tagen.', $invoice->invoice_terms);
    }

    public function test_invoice_number_is_read_only_after_the_invoice_is_issued(): void
    {
        $user = User::factory()->create();
        $client = Client::query()->create([
            'client_name' => 'Testkunde',
            'client_active' => true,
        ]);
        $invoice = $this->createInvoice();
        $invoice->update(['client_id' => $client->getKey()]);
        $invoice = app(IssueInvoice::class)->handle($invoice);
        Livewire::actingAs($user)
            ->test(EditInvoice::class, ['record' => $invoice->getRouteKey()])
            ->assertFormFieldDisabled('invoice_number')
            ->assertFormFieldDisabled('client_id')
            ->assertFormFieldDisabled('invoice_date_due')
            ->assertSee('Rechnung ausstellen')
            ->assertSee('Archivieren');
    }

    public function test_invoice_lifecycle_statuses_are_available_as_tabs(): void
    {
        $user = User::factory()->create();
        $draft = $this->createInvoice();
        $open = $this->createInvoice();
        $open->update([
            'invoice_number' => 'RE-OFFEN',
            'invoice_status_id' => InvoiceStatus::Open,
        ]);
        $paid = $this->createInvoice();
        $paid->update([
            'invoice_number' => 'RE-BEZAHLT',
            'invoice_status_id' => InvoiceStatus::Paid,
        ]);
        $overdue = $this->createInvoice();
        $overdue->update([
            'invoice_number' => 'RE-UEBERFAELLIG',
            'invoice_status_id' => InvoiceStatus::Open,
            'invoice_date_due' => today()->subDay(),
        ]);
        $overdue->amount()->create([
            'invoice_sign' => 1,
            'invoice_item_subtotal' => 100,
            'invoice_item_tax_total' => 0,
            'invoice_tax_total' => 0,
            'invoice_total' => 100,
            'invoice_paid' => 0,
            'invoice_balance' => 100,
        ]);
        $archived = $this->createInvoice();
        $archived->update([
            'invoice_number' => 'RE-ARCHIVIERT',
            'invoice_status_id' => InvoiceStatus::Open,
            'is_archived' => true,
        ]);

        Livewire::actingAs($user)
            ->test(ListInvoices::class)
            ->assertTableFilterExists('invoice_number')
            ->assertTableFilterExists('client_id')
            ->assertTableFilterExists('project_id')
            ->assertTableFilterExists('billing_type')
            ->assertTableFilterExists('invoice_date_created')
            ->assertTableFilterExists('invoice_date_due')
            ->assertTableFilterExists('invoice_total')
            ->assertTableFilterExists('invoice_paid')
            ->assertTableFilterExists('invoice_balance')
            ->assertTableColumnExists('billing_type', fn ($column): bool => $column->isToggleable()
                && ! $column->isToggledHiddenByDefault())
            ->assertTableColumnExists('invoice_number', fn ($column): bool => $column->isToggleable())
            ->assertTableColumnExists('client.client_name', fn ($column): bool => $column->isToggleable())
            ->assertTableColumnExists('project.project_number', fn ($column): bool => $column->isToggleable())
            ->assertTableColumnExists('invoice_date_created', fn ($column): bool => $column->isToggleable())
            ->assertTableColumnExists('invoice_date_due', fn ($column): bool => $column->isToggleable())
            ->assertTableColumnExists('invoice_status_id', fn ($column): bool => $column->isToggleable())
            ->assertTableColumnExists('amount.invoice_total', fn ($column): bool => $column->isToggleable())
            ->assertTableColumnExists('amount.invoice_paid', fn ($column): bool => $column->isToggleable())
            ->assertTableColumnExists('amount.invoice_balance', fn ($column): bool => $column->isToggleable())
            ->set('activeTab', InvoiceStatus::Draft->name)
            ->assertCanSeeTableRecords([$draft])
            ->assertCanNotSeeTableRecords([$open, $paid, $overdue, $archived])
            ->set('activeTab', 'overdue')
            ->assertCanSeeTableRecords([$overdue])
            ->assertCanNotSeeTableRecords([$draft, $open, $paid, $archived])
            ->set('activeTab', 'archived')
            ->assertCanSeeTableRecords([$archived])
            ->assertCanNotSeeTableRecords([$draft, $open, $paid, $overdue])
            ->set('activeTab', 'all')
            ->filterTable('invoice_number', ['value' => 'UEBERFAELLIG'])
            ->assertCanSeeTableRecords([$overdue])
            ->assertCanNotSeeTableRecords([$draft, $open, $paid, $archived])
            ->filterTable('invoice_number', ['value' => null])
            ->filterTable('invoice_total', ['from' => 90, 'until' => 110])
            ->assertCanSeeTableRecords([$overdue])
            ->assertCanNotSeeTableRecords([$draft, $open, $paid, $archived]);

        $table = Livewire::actingAs($user)
            ->test(ListInvoices::class)
            ->instance()
            ->getTable();

        $this->assertTrue($table->persistsFiltersInSession());
        $this->assertTrue($table->persistsColumnsInSession());
    }

    public function test_invoice_filter_configuration_is_restored_from_the_session(): void
    {
        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(ListInvoices::class)
            ->filterTable('billing_type', InvoiceBillingType::Progress->value);

        Livewire::actingAs($user)
            ->test(ListInvoices::class)
            ->assertSet('tableFilters.billing_type.value', InvoiceBillingType::Progress->value);
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

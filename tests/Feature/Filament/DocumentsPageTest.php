<?php

namespace Tests\Feature\Filament;

use App\Features\BankTransactions\Actions\CreateBankTransaction;
use App\Features\BankTransactions\Models\BankTransactionMapping;
use App\Features\Clients\Models\Client;
use App\Features\Documents\Filament\Pages\Documents;
use App\Features\Documents\Livewire\AssignBankTransaction;
use App\Features\Documents\Services\PaperlessClient;
use App\Features\Invoices\Enums\InvoiceStatus;
use App\Features\Invoices\Enums\InvoiceType;
use App\Features\Invoices\Livewire\AssignInvoiceBankTransaction;
use App\Features\Invoices\Livewire\InvoiceMappings;
use App\Features\Invoices\Models\Invoice;
use App\Features\Invoices\Models\InvoiceAmount;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Forms\Components\Toggle;
use Filament\Support\Enums\Width;
use Filament\Tables\Enums\FiltersLayout;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;
use Tests\TestCase;

class DocumentsPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_unauthenticated_user_is_redirected_to_login(): void
    {
        $this->get('/admin/documents')->assertRedirect('/admin/login');
    }

    public function test_authenticated_user_can_manually_load_paperless_documents_without_a_separate_assignment_column(): void
    {
        Config::set('services.paperless.url', 'https://paperless.test');
        Config::set('services.paperless.token', 'test-token');
        Config::set('services.paperless.storage_path_id', 42);
        Config::set('services.paperless.document_type_id', 7);
        Config::set('services.paperless.invoice_number_field_id', 9);
        Http::preventStrayRequests();
        Http::fake([
            'paperless.test/api/documents/*' => Http::response([
                'count' => 1,
                'results' => [[
                    'id' => 77,
                    'title' => 'Eingangsrechnung Oktober',
                    'document_date' => '2026-10-14',
                    'tags' => [3],
                    'custom_fields' => [
                        ['field' => 9, 'value' => 'RE-2026-4711'],
                    ],
                ]],
            ]),
            'paperless.test/api/correspondents/*' => Http::response(['results' => []]),
            'paperless.test/api/tags/*' => Http::response(['results' => [
                ['id' => 3, 'name' => 'Eingangsrechnung'],
            ]]),
        ]);

        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(Documents::class)
            ->assertSet('tableFilters.status.value', null)
            ->assertSee('Aktualisieren')
            ->assertDontSee('Eingangsrechnung Oktober')
            ->callTableAction('refreshPaperlessDocuments')
            ->assertSee('Eingangsrechnung Oktober')
            ->assertSee('Rechnungsnummer')
            ->assertSee('RE-2026-4711')
            ->assertTableFilterExists('paperless_tag')
            ->assertSee('Eingangsrechnung')
            ->assertDontSee('Zuordnen');

        Livewire::actingAs($user)
            ->test(Documents::class)
            ->assertSee('Eingangsrechnung Oktober')
            ->assertSee('RE-2026-4711');

        Http::assertSent(fn ($request): bool => str_contains($request->url(), '/api/documents/')
            && str_contains($request->url(), 'storage_path__id=42')
            && str_contains($request->url(), 'document_type__id=7'));
        $this->assertCount(1, collect(Http::recorded())
            ->filter(fn (array $record): bool => str_contains($record[0]->url(), '/api/documents/')));
    }

    public function test_page_is_named_assignments_and_offers_income_and_expense_tabs(): void
    {
        Config::set('services.paperless.url', 'https://paperless.test');
        Config::set('services.paperless.token', 'test-token');
        Http::fake([
            'paperless.test/api/documents/*' => Http::response(['count' => 0, 'results' => []]),
            'paperless.test/api/correspondents/*' => Http::response(['results' => []]),
        ]);

        Livewire::actingAs(User::factory()->create())
            ->test(Documents::class)
            ->assertSee('Zuordnungen')
            ->assertSee('Einnahmen')
            ->assertSee('Ausgaben')
            ->assertSet('activeTab', 'expenses')
            ->call('showTab', 'income')
            ->assertSet('activeTab', 'income');
    }

    public function test_income_tab_lists_invoices_with_mapping_relevant_columns(): void
    {
        $user = User::factory()->create();
        $client = Client::query()->create([
            'client_name' => 'Musterkunde GmbH',
            'client_active' => true,
        ]);
        $invoice = Invoice::query()->create([
            'invoice_type' => InvoiceType::Invoice,
            'user_id' => $user->getKey(),
            'client_id' => $client->getKey(),
            'invoice_group_id' => 1,
            'invoice_status_id' => InvoiceStatus::Open,
            'is_read_only' => false,
            'is_archived' => false,
            'invoice_password' => '',
            'invoice_date_created' => '2026-10-14',
            'invoice_time_created' => '10:00:00',
            'invoice_date_due' => '2026-10-28',
            'invoice_number' => 'RE-2026-1001',
            'invoice_discount_amount' => 0,
            'invoice_discount_percent' => 0,
            'invoice_terms' => '',
            'invoice_url_key' => 'income-mapping-test',
            'payment_method' => 0,
            'creditinvoice_parent_id' => null,
            'invoice_einvoicing_version' => '',
        ]);
        InvoiceAmount::query()->create([
            'invoice_id' => $invoice->getKey(),
            'invoice_total' => 119,
            'invoice_paid' => 0,
            'invoice_balance' => 119,
        ]);

        $incoming = app(CreateBankTransaction::class)->handle([
            'account_iban' => 'DE02120300000000202051',
            'booking_date' => '2026-10-15',
            'amount' => '119.0000',
            'currency' => 'EUR',
            'counterparty_name' => 'Musterkunde GmbH',
            'purpose' => 'Zahlung RE-2026-1001',
            'pending' => false,
        ]);
        $outgoing = app(CreateBankTransaction::class)->handle([
            'account_iban' => 'DE02120300000000202051',
            'booking_date' => '2026-10-15',
            'amount' => '-119.0000',
            'currency' => 'EUR',
            'counterparty_name' => 'Musterkunde GmbH',
            'purpose' => 'Ausgabe',
            'pending' => false,
        ]);

        $component = Livewire::actingAs($user)
            ->test(InvoiceMappings::class)
            ->assertSee('RE-2026-1001')
            ->assertSee('Rechnungsdatum')
            ->assertSee('Fällig am')
            ->assertSee('Gesamt')
            ->assertSee('Offen')
            ->assertSee('Nicht zugeordnet');

        $this->assertSame('openAssignment', $component->instance()->getTable()->getRecordAction($invoice));

        $component
            ->call('openAssignment', $invoice->getKey())
            ->assertActionMounted('assign');

        $mountedAction = $component->instance()->getMountedAction();
        $this->assertInstanceOf(Action::class, $mountedAction);
        $this->assertTrue($mountedAction->isModalSlideOver());
        $this->assertSame(Width::TwoExtraLarge, $mountedAction->getModalWidth());

        Livewire::actingAs($user)
            ->test(AssignInvoiceBankTransaction::class, ['invoiceId' => $invoice->getKey()])
            ->assertSee('Rechnungsinformationen')
            ->assertSee('Banktransaktionen')
            ->assertSee('Verknüpft')
            ->assertCanSeeTableRecords([$incoming])
            ->assertCanNotSeeTableRecords([$outgoing])
            ->call('applyTableSearch', '119.00')
            ->assertSet('tableSearch', '119.00')
            ->assertCanSeeTableRecords([$incoming]);
    }

    public function test_paperless_documents_are_not_filtered_by_path_or_type_when_settings_are_empty(): void
    {
        Config::set('services.paperless.url', 'https://paperless-without-path.test');
        Config::set('services.paperless.token', 'test-token');
        Config::set('services.paperless.storage_path_id');
        Config::set('services.paperless.document_type_id');
        Http::preventStrayRequests();
        Http::fake([
            'paperless-without-path.test/api/documents/*' => Http::response([
                'count' => 0,
                'results' => [],
            ]),
            'paperless-without-path.test/api/correspondents/*' => Http::response(['results' => []]),
        ]);

        app(PaperlessClient::class)->documents();

        Http::assertSent(fn ($request): bool => str_contains($request->url(), '/api/documents/')
            && ! str_contains($request->url(), 'tags__id=')
            && ! str_contains($request->url(), 'storage_path__id=')
            && ! str_contains($request->url(), 'document_type__id='));
    }

    public function test_expense_documents_can_be_filtered_across_all_paperless_pages_and_refreshed(): void
    {
        Config::set('services.paperless.url', 'https://paperless-all-documents.test');
        Config::set('services.paperless.token', 'test-token');
        Http::preventStrayRequests();
        Http::fake(function ($request) {
            if (str_contains($request->url(), '/api/correspondents/')) {
                return Http::response(['results' => []]);
            }

            if (str_contains($request->url(), '/api/tags/')) {
                return Http::response(['results' => [
                    ['id' => 10, 'name' => 'Bezahlt'],
                    ['id' => 20, 'name' => 'Offen'],
                ]]);
            }

            $page = (int) ($request->data()['page'] ?? 1);

            return Http::response([
                'count' => 2,
                'results' => [[
                    'id' => $page === 1 ? 77 : 88,
                    'title' => $page === 1 ? 'Bereits zugeordnet' : 'Noch nicht zugeordnet',
                    'document_date' => $page === 1 ? '2026-10-15' : '2026-10-14',
                    'tags' => [$page === 1 ? 10 : 20],
                    'custom_fields' => [],
                ]],
            ]);
        });

        $transaction = app(CreateBankTransaction::class)->handle([
            'account_iban' => 'DE02120300000000202051',
            'booking_date' => '2026-10-15',
            'amount' => '-119.0000',
            'currency' => 'EUR',
            'purpose' => 'Bereits zugeordnete Ausgabe',
            'pending' => false,
        ]);
        BankTransactionMapping::query()->create([
            'bank_transaction_id' => $transaction->getKey(),
            'payment_id' => null,
            'document_id' => 77,
        ]);

        $user = User::factory()->create();
        $component = Livewire::actingAs($user)
            ->test(Documents::class)
            ->assertTableActionExists('refreshPaperlessDocuments')
            ->assertTableFilterExists('status')
            ->assertTableFilterExists('paperless_tag')
            ->assertTableFilterExists('document_date')
            ->assertDontSee('Bereits zugeordnet')
            ->assertDontSee('Noch nicht zugeordnet')
            ->callTableAction('refreshPaperlessDocuments')
            ->assertSee('Bereits zugeordnet')
            ->assertSee('Noch nicht zugeordnet')
            ->filterTable('status', 'unassigned')
            ->assertDontSee('Bereits zugeordnet')
            ->assertSee('Noch nicht zugeordnet')
            ->filterTable('status')
            ->assertSee('Bereits zugeordnet')
            ->assertSee('Noch nicht zugeordnet')
            ->filterTable('paperless_tag', 10)
            ->assertSee('Bereits zugeordnet')
            ->assertDontSee('Noch nicht zugeordnet')
            ->filterTable('paperless_tag')
            ->filterTable('document_date', ['from' => '2026-10-15', 'until' => '2026-10-16'])
            ->assertSee('Ausstellungsdatum von 15.10.2026')
            ->assertSee('Ausstellungsdatum bis 16.10.2026')
            ->assertSee('Bereits zugeordnet')
            ->assertDontSee('Noch nicht zugeordnet')
            ->filterTable('document_date', ['from' => null, 'until' => null])
            ->filterTable('status', 'unassigned')
            ->assertDontSee('Bereits zugeordnet')
            ->assertSee('Noch nicht zugeordnet')
            ->callTableAction('refreshPaperlessDocuments')
            ->assertSet('tableFilters.status.value', 'unassigned')
            ->assertSee('Noch nicht zugeordnet');

        Livewire::actingAs($user)
            ->test(Documents::class)
            ->assertSet('tableFilters.status.value', 'unassigned')
            ->assertDontSee('Bereits zugeordnet')
            ->assertSee('Noch nicht zugeordnet');

        $documentRequests = collect(Http::recorded())
            ->filter(fn (array $record): bool => str_contains($record[0]->url(), '/api/documents/'));

        $this->assertCount(4, $documentRequests);
        $this->assertSame([1, 2, 1, 2], $documentRequests
            ->map(fn (array $record): int => (int) ($record[0]->data()['page'] ?? 1))
            ->values()
            ->all());
    }

    public function test_assignment_action_opens_the_slide_over_content(): void
    {
        Config::set('services.paperless.url', 'https://paperless.test');
        Config::set('services.paperless.token', 'test-token');
        Http::preventStrayRequests();
        Http::fake([
            'paperless.test/api/documents/77/' => Http::response([
                'id' => 77,
                'title' => 'Eingangsrechnung Oktober',
                'document_date' => '2026-10-14',
                'custom_fields' => [],
            ]),
            'paperless.test/api/documents/*' => Http::response([
                'count' => 1,
                'results' => [[
                    'id' => 77,
                    'title' => 'Eingangsrechnung Oktober',
                    'document_date' => '2026-10-14',
                    'custom_fields' => [],
                ]],
            ]),
            'paperless.test/api/correspondents/*' => Http::response(['results' => []]),
        ]);
        $user = User::factory()->create();

        $component = Livewire::actingAs($user)->test(Documents::class);

        $this->assertSame('openAssignment', $component->instance()->getTable()->getRecordAction(['id' => 77]));

        $component
            ->call('openAssignment', 77)
            ->assertActionMounted('assign');

        $paperlessPreviewScript = data_get($component->effects, 'xjs.0.expression', '');

        $this->assertStringContainsString('window.paperlessDocumentPreview', $paperlessPreviewScript);
        $this->assertStringNotContainsString('.focus()', $paperlessPreviewScript);
        $this->assertStringContainsString(
            'https://paperless.test/documents/77/details',
            str_replace('\\/', '/', $paperlessPreviewScript),
        );

        $mountedAction = $component->instance()->getMountedAction();

        $this->assertInstanceOf(Action::class, $mountedAction);
        $this->assertTrue($mountedAction->isModalSlideOver());
        $this->assertSame(Width::TwoExtraLarge, $mountedAction->getModalWidth());

        Livewire::actingAs($user)
            ->test(AssignBankTransaction::class, ['documentId' => 77])
            ->assertSee('Dokumentinformationen')
            ->assertSee('Banktransaktionen')
            ->assertSee('Link');
    }

    public function test_assignment_slide_over_always_loads_fresh_paperless_document_data(): void
    {
        Config::set('services.paperless.url', 'https://paperless-fresh-document.test');
        Config::set('services.paperless.token', 'test-token');
        Http::preventStrayRequests();
        Http::fake([
            'paperless-fresh-document.test/api/documents/99/' => Http::sequence()
                ->push([
                    'id' => 99,
                    'title' => 'Lieferantenrechnung',
                    'document_date' => '2026-10-14',
                    'correspondent' => ['name' => 'Alter Lieferant'],
                    'custom_fields' => [],
                ])
                ->push([
                    'id' => 99,
                    'title' => 'Lieferantenrechnung',
                    'document_date' => '2026-10-14',
                    'correspondent' => ['name' => 'Neuer Lieferant'],
                    'custom_fields' => [],
                ]),
            'paperless-fresh-document.test/api/correspondents/*' => Http::response(['results' => []]),
        ]);

        $this->assertSame('Alter Lieferant', app(PaperlessClient::class)->document(99)->supplier);

        Livewire::actingAs(User::factory()->create())
            ->test(AssignBankTransaction::class, ['documentId' => 99])
            ->assertSet('document.supplier', 'Neuer Lieferant')
            ->assertSee('Neuer Lieferant')
            ->assertDontSee('Alter Lieferant');
    }

    public function test_assignment_slide_over_uses_document_data_as_initial_search_and_filters(): void
    {
        Config::set('services.paperless.url', 'https://paperless-defaults.test/');
        Config::set('services.paperless.token', 'test-token');
        Config::set('services.paperless.amount_field_id', 5);
        Config::set('services.paperless.invoice_number_field_id', 6);
        Http::preventStrayRequests();
        Http::fake([
            'paperless-defaults.test/api/documents/91/' => Http::response([
                'id' => 91,
                'title' => 'Lieferantenrechnung',
                'document_date' => '2026-10-14',
                'correspondent' => ['name' => 'Muster Lieferant GmbH'],
                'custom_fields' => [
                    ['field' => 5, 'value' => '125,50'],
                    ['field' => 6, 'value' => 'RE-2026-100'],
                ],
            ]),
            'paperless-defaults.test/api/correspondents/*' => Http::response(['results' => []]),
        ]);

        $component = Livewire::withQueryParams(['page' => 3])
            ->actingAs(User::factory()->create())
            ->test(AssignBankTransaction::class, ['documentId' => 91]);

        $statusToggle = $component->instance()->assignmentStatusToggle->getComponent('showAssigned');

        $this->assertInstanceOf(Toggle::class, $statusToggle);
        $this->assertTrue($statusToggle->isLabelHidden());
        $this->assertSame('documentBankTransactionsPage', $component->instance()->getTablePaginationPageName());
        $this->assertSame(1, $component->instance()->getTablePage());

        $component
            ->assertSet('tableSearch', '')
            ->assertSet('showAssigned', false)
            ->assertSee('Paperless ID')
            ->assertSee('91')
            ->assertSee('Dokument in Paperless öffnen')
            ->assertSeeHtml('href="https://paperless-defaults.test/documents/91/details"')
            ->assertSeeHtml('target="paperless-document-preview"')
            ->assertSeeHtml('window.paperlessDocumentPreview')
            ->assertSeeHtml('document-information')
            ->assertSee('Nicht zugeordnet')
            ->assertSee('Lieferant als Filter hinzufügen')
            ->assertSee('Rechnungsnummer als Filter hinzufügen')
            ->assertSee('Rechnungsbetrag als Filter hinzufügen')
            ->call('applyInvoiceNumberFilter')
            ->assertSet('tableFilters.invoice_number.value', 'RE-2026-100')
            ->assertSee('Rechnungsnummer: RE-2026-100')
            ->call('applySupplierFilter')
            ->assertSet('tableFilters.supplier.value', 'Muster Lieferant GmbH')
            ->assertSee('Lieferant: Muster Lieferant GmbH')
            ->call('applyAmountFilter')
            ->assertSet('tableFilters.amount.value', 125.5)
            ->assertSee('Betrag: 125,50 EUR')
            ->assertSet('tableSearch', '')
            ->call('removeTableFilter', 'invoice_number', 'value')
            ->assertSet('tableFilters.invoice_number.value', null)
            ->assertDontSee('Rechnungsnummer: RE-2026-100');
    }

    public function test_assignment_slide_over_filters_transactions_by_invoice_number(): void
    {
        Config::set('services.paperless.url', 'https://paperless-invoice-filter.test');
        Config::set('services.paperless.token', 'test-token');
        Config::set('services.paperless.amount_field_id', 5);
        Config::set('services.paperless.invoice_number_field_id', 6);
        Http::preventStrayRequests();
        Http::fake([
            'paperless-invoice-filter.test/api/documents/92/' => Http::response([
                'id' => 92,
                'title' => 'Lieferantenrechnung',
                'document_date' => '2026-10-14',
                'correspondent' => ['name' => 'Muster Lieferant GmbH'],
                'custom_fields' => [
                    ['field' => 5, 'value' => '125,50'],
                    ['field' => 6, 'value' => 'RE-4711'],
                ],
            ]),
            'paperless-invoice-filter.test/api/correspondents/*' => Http::response(['results' => []]),
        ]);

        $matching = app(CreateBankTransaction::class)->handle([
            'account_iban' => 'DE02120300000000202051',
            'booking_date' => '2026-10-14',
            'amount' => '-125.5000',
            'currency' => 'EUR',
            'counterparty_name' => 'Muster Lieferant GmbH',
            'purpose' => 'Zahlung der Rechnung RE-4711',
            'pending' => false,
        ]);
        $unrelated = app(CreateBankTransaction::class)->handle([
            'account_iban' => 'DE02120300000000202051',
            'booking_date' => '2026-10-14',
            'amount' => '-125.5000',
            'currency' => 'EUR',
            'counterparty_name' => 'Anderer Lieferant',
            'purpose' => 'Zahlung der Rechnung RE-9999',
            'pending' => false,
        ]);
        $assigned = app(CreateBankTransaction::class)->handle([
            'account_iban' => 'DE02120300000000202051',
            'booking_date' => '2026-10-14',
            'amount' => '-80.0000',
            'currency' => 'EUR',
            'counterparty_name' => 'Zugeordneter Lieferant',
            'purpose' => 'Bereits bezahlte Rechnung',
            'pending' => false,
        ]);
        BankTransactionMapping::query()->create([
            'bank_transaction_id' => $assigned->getKey(),
            'payment_id' => null,
            'document_id' => 500,
        ]);

        $component = Livewire::actingAs(User::factory()->create())
            ->test(AssignBankTransaction::class, ['documentId' => 92]);

        $this->assertSame(
            ['supplier', 'invoice_number', 'amount'],
            array_keys($component->instance()->getTable()->getFilters()),
        );
        $this->assertSame(FiltersLayout::Hidden, $component->instance()->getTable()->getFiltersLayout());

        $component
            ->searchTable('RE-4711')
            ->assertCanSeeTableRecords([$matching])
            ->assertCanNotSeeTableRecords([$unrelated, $assigned])
            ->searchTable()
            ->call('applyInvoiceNumberFilter')
            ->call('applySupplierFilter')
            ->call('applyAmountFilter')
            ->assertCanSeeTableRecords([$matching])
            ->assertCanNotSeeTableRecords([$unrelated, $assigned])
            ->call('removeTableFilters')
            ->assertCanSeeTableRecords([$matching, $unrelated])
            ->assertCanNotSeeTableRecords([$assigned])
            ->set('showAssigned', true)
            ->assertSee('Bereits zugeordnet')
            ->assertSee('#500')
            ->assertSeeHtml('href="https://paperless-invoice-filter.test/documents/500/details"')
            ->assertSeeHtml('paperless-document-preview')
            ->assertCanSeeTableRecords([$assigned])
            ->assertCanNotSeeTableRecords([$matching, $unrelated]);
    }
}

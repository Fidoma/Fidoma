<?php

namespace Tests\Feature\Filament;

use App\Features\BankTransactions\Actions\CreateBankTransaction;
use App\Features\BankTransactions\Models\BankTransactionMapping;
use App\Features\Documents\Livewire\AssignBankTransaction;
use App\Features\Documents\Services\PaperlessClient;
use App\Filament\Pages\Documents;
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

    public function test_authenticated_user_sees_paperless_documents_without_a_separate_assignment_column(): void
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
                    'custom_fields' => [
                        ['field' => 9, 'value' => 'RE-2026-4711'],
                    ],
                ]],
            ]),
            'paperless.test/api/correspondents/*' => Http::response(['results' => []]),
        ]);

        $this->actingAs(User::factory()->create())
            ->get('/admin/documents')
            ->assertOk()
            ->assertSee('Eingangsrechnung Oktober')
            ->assertSee('Rechnungsnummer')
            ->assertSee('RE-2026-4711')
            ->assertDontSee('Zuordnen');

        Http::assertSent(fn ($request): bool => str_contains($request->url(), '/api/documents/')
            && str_contains($request->url(), 'storage_path__id=42')
            && str_contains($request->url(), 'document_type__id=7'));
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
            && ! str_contains($request->url(), 'storage_path__id=')
            && ! str_contains($request->url(), 'document_type__id='));
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

        $component = Livewire::actingAs(User::factory()->create())
            ->test(AssignBankTransaction::class, ['documentId' => 91]);

        $statusToggle = $component->instance()->assignmentStatusToggle->getComponent('showAssigned');

        $this->assertInstanceOf(Toggle::class, $statusToggle);
        $this->assertTrue($statusToggle->isLabelHidden());

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
            ->set('showAssigned', true)
            ->assertSee('Bereits zugeordnet')
            ->assertCanSeeTableRecords([$assigned])
            ->assertCanNotSeeTableRecords([$matching, $unrelated]);
    }
}

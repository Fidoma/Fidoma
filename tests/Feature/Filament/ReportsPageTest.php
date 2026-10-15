<?php

namespace Tests\Feature\Filament;

use App\Features\BankTransactions\Actions\CreateBankTransaction;
use App\Features\PaymentMethods\Models\PaymentMethod;
use App\Features\Reports\Services\PaymentReportService;
use App\Filament\Pages\Reports;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ReportsPageTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        CarbonImmutable::setTestNow();

        parent::tearDown();
    }

    public function test_unauthenticated_user_is_redirected_to_login(): void
    {
        $this->get('/admin/reports')->assertRedirect('/admin/login');
    }

    public function test_reports_page_opens_the_ustva_directly_without_a_secondary_tab_bar(): void
    {
        CarbonImmutable::setTestNow('2026-10-15');

        $this->actingAs(User::factory()->create())
            ->get('/admin/reports')
            ->assertOk()
            ->assertSee('Umsatzsteuer')
            ->assertSee('Zahlungen')
            ->assertSee('Rechnungen')
            ->assertSee('Gesamter Nettoumsatz')
            ->assertSee('Gesamte Umsatzsteuer')
            ->assertSee('Abziehbare Vorsteuer')
            ->assertSee('Umsatzsteuerdifferenz')
            ->assertSee('Von')
            ->assertSee('Bis')
            ->assertSee('Soll-Versteuerung')
            ->assertSee('Ist-Versteuerung')
            ->assertDontSee('Übersicht')
            ->assertDontSee('UStVA-Vorschau')
            ->assertDontSee('Quartal');
    }

    public function test_report_tabs_can_be_changed(): void
    {
        CarbonImmutable::setTestNow('2026-10-15');

        Livewire::actingAs(User::factory()->create())
            ->test(Reports::class)
            ->call('setTab', 'payments')
            ->assertSet('activeTab', 'payments')
            ->assertSee('Zahlungseingänge')
            ->call('setTab', 'invoices')
            ->assertSet('activeTab', 'invoices')
            ->assertSee('Teilbezahlt');
    }

    public function test_ustva_preview_can_be_opened_and_exported(): void
    {
        CarbonImmutable::setTestNow('2026-10-15');

        Livewire::actingAs(User::factory()->create())
            ->test(Reports::class)
            ->set('startDate', '2026-10-01')
            ->set('endDate', '2026-10-31')
            ->assertSee('Steuerpflichtige Umsätze zum Steuersatz von 19 %')
            ->assertSee('Weitere steuerliche Sachverhalte')
            ->assertSee('Auswertung unvollständig')
            ->call('exportCsv')
            ->assertFileDownloaded('fidoma-ustva-2026-10-01-2026-10-31.csv');
    }

    public function test_ustva_detail_action_opens_its_slide_over(): void
    {
        CarbonImmutable::setTestNow('2026-10-15');

        Livewire::actingAs(User::factory()->create())
            ->test(Reports::class)
            ->mountAction('viewUstvaDetails', ['code' => '86'])
            ->assertActionMounted('viewUstvaDetails')
            ->assertSee('Steuerpflichtige Umsätze zum Steuersatz von 7 %');
    }

    public function test_ustva_custom_period_can_be_exported_as_excel_and_pdf(): void
    {
        CarbonImmutable::setTestNow('2026-10-15');

        Livewire::actingAs(User::factory()->create())
            ->test(Reports::class)
            ->set('startDate', '2026-04-01')
            ->set('endDate', '2026-06-30')
            ->call('exportExcel')
            ->assertFileDownloaded('fidoma-ustva-2026-04-01-2026-06-30.xlsx')
            ->call('exportUstvaPdf')
            ->assertFileDownloaded('fidoma-ustva-2026-04-01-2026-06-30.pdf');
    }

    public function test_taxation_type_can_be_switched_with_tabs(): void
    {
        Livewire::actingAs(User::factory()->create())
            ->test(Reports::class)
            ->call('setTaxationType', 'ist')
            ->assertSet('taxationType', 'ist')
            ->call('setTaxationType', 'soll')
            ->assertSet('taxationType', 'soll');
    }

    public function test_date_filter_can_be_cleared(): void
    {
        Livewire::actingAs(User::factory()->create())
            ->test(Reports::class)
            ->set('startDate', '2026-10-01')
            ->set('endDate', '2026-10-31')
            ->call('clearDateFilter')
            ->assertSet('startDate', null)
            ->assertSet('endDate', null);
    }

    public function test_csv_export_uses_the_selected_report_and_period(): void
    {
        CarbonImmutable::setTestNow('2026-10-15');

        Livewire::actingAs(User::factory()->create())
            ->test(Reports::class)
            ->call('setTab', 'invoices')
            ->call('exportCsv')
            ->assertFileDownloaded('fidoma-rechnungen-alle-zeitraeume.csv');
    }

    public function test_excel_export_uses_the_selected_report_and_period(): void
    {
        CarbonImmutable::setTestNow('2026-10-15');

        Livewire::actingAs(User::factory()->create())
            ->test(Reports::class)
            ->call('setTab', 'payments')
            ->call('exportExcel')
            ->assertFileDownloaded('fidoma-zahlungen-alle-zeitraeume.xlsx');
    }

    public function test_payment_method_filter_includes_unmapped_transactions_with_matching_booking_text(): void
    {
        $method = PaymentMethod::query()->create(['payment_method_name' => 'Überweisung']);
        $transaction = app(CreateBankTransaction::class)->handle([
            'account_iban' => 'DE02120300000000202051',
            'booking_date' => '2026-10-15',
            'value_date' => '2026-10-15',
            'amount' => '125.0000',
            'currency' => 'EUR',
            'booking_text' => 'Überweisung',
            'counterparty_name' => 'Musterkunde GmbH',
            'purpose' => 'Rechnung RE-100',
            'pending' => false,
        ]);

        $records = app(PaymentReportService::class)
            ->query(
                CarbonImmutable::parse('2026-10-01'),
                CarbonImmutable::parse('2026-10-31'),
                paymentMethodId: $method->getKey(),
            )
            ->get();

        $this->assertTrue($records->contains($transaction));
    }
}

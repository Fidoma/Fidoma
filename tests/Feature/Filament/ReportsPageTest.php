<?php

namespace Tests\Feature\Filament;

use App\Features\PaymentMethods\Models\PaymentMethod;
use App\Features\Payments\Models\Payment;
use App\Features\Reports\Filament\Pages\Reports;
use App\Features\Reports\Services\PaymentReportService;
use App\Features\TaxRates\Models\TaxRate;
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
            ->assertSee('Verkauf')
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
            ->assertSee('Gesamtzahlungen')
            ->assertSee('Anzahl Zahlungen')
            ->assertSee('Zahlungsdatum')
            ->assertSee('Zahlungsart')
            ->assertTableFilterExists('payment_method_id')
            ->assertSee('Kunde')
            ->assertSee('Notiz')
            ->assertDontSee('Richtung')
            ->assertDontSee('Bankkonto')
            ->assertDontSee('Verwendungszweck')
            ->call('setTab', 'invoices')
            ->assertSet('activeTab', 'invoices')
            ->assertTableFilterExists('invoice_type')
            ->assertTableFilterExists('invoice_status_id')
            ->assertTableFilterExists('client_id')
            ->filterTable('invoice_type', 'credit')
            ->assertSet('tableFilters.invoice_type.value', 'credit')
            ->assertSee('Teilbezahlt')
            ->assertTableBulkActionExists('downloadPdfs')
            ->assertTableBulkActionExists('downloadXmls')
            ->assertTableBulkActionHasLabel('downloadXmls', 'XML als ZIP')
            ->call('setTab', 'sales')
            ->assertSet('activeTab', 'sales')
            ->assertTableFilterExists('invoice_type')
            ->assertTableFilterExists('invoice_status_id')
            ->assertTableFilterExists('client_id')
            ->assertTableFilterExists('item_tax_rate_id')
            ->assertSee('Kunde / Lieferant')
            ->assertSee('Position')
            ->assertSee('Rechnungsdatum')
            ->assertTableColumnExists('taxRate.tax_rate_percent', fn ($column): bool => $column->isSortable())
            ->assertTableColumnExists('amount.item_subtotal', fn ($column): bool => $column->isSortable())
            ->assertTableColumnExists('amount.item_tax_total', fn ($column): bool => $column->isSortable())
            ->assertTableColumnExists('amount.item_total', fn ($column): bool => $column->isSortable())
            ->sortTable('taxRate.tax_rate_percent')
            ->sortTable('amount.item_subtotal')
            ->sortTable('amount.item_tax_total')
            ->sortTable('amount.item_total')
            ->assertSee('Netto')
            ->assertSee('Umsatzsteuer')
            ->assertSee('Brutto');
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

    public function test_ustva_custom_period_can_be_exported_as_pdf(): void
    {
        CarbonImmutable::setTestNow('2026-10-15');

        Livewire::actingAs(User::factory()->create())
            ->test(Reports::class)
            ->set('startDate', '2026-04-01')
            ->set('endDate', '2026-06-30')
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
        CarbonImmutable::setTestNow('2026-10-15');

        Livewire::actingAs(User::factory()->create())
            ->test(Reports::class)
            ->assertSee('Q1')
            ->assertSee('Q2')
            ->assertSee('Q3')
            ->assertSee('Q4')
            ->assertSee('Jahr')
            ->assertSee('Gesamt')
            ->call('selectPeriod', 'q3')
            ->assertSet('selectedPeriod', 'q3')
            ->assertSet('startDate', '2026-07-01')
            ->assertSet('endDate', '2026-09-30')
            ->set('startDate', '2026-10-01')
            ->set('endDate', '2026-10-31')
            ->call('selectPeriod', 'reset')
            ->assertSet('selectedPeriod', 'custom')
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

    public function test_sales_export_uses_the_selected_report_and_period(): void
    {
        Livewire::actingAs(User::factory()->create())
            ->test(Reports::class)
            ->call('setTab', 'sales')
            ->call('exportCsv')
            ->assertFileDownloaded('fidoma-verkauf-alle-zeitraeume.csv');
    }

    public function test_sales_can_be_filtered_by_item_tax_rate(): void
    {
        $taxRate = TaxRate::query()->create([
            'tax_rate_name' => 'Ermäßigt',
            'tax_rate_percent' => 7,
        ]);

        Livewire::actingAs(User::factory()->create())
            ->test(Reports::class)
            ->call('setTab', 'sales')
            ->assertTableFilterExists('item_tax_rate_id')
            ->filterTable('item_tax_rate_id', $taxRate->getKey())
            ->assertSet('tableFilters.item_tax_rate_id.value', $taxRate->getKey());
    }

    public function test_payment_method_filter_uses_payments(): void
    {
        $method = PaymentMethod::query()->create(['payment_method_name' => 'Überweisung']);
        $payment = Payment::query()->create([
            'invoice_id' => 0,
            'payment_method_id' => $method->getKey(),
            'payment_date' => '2026-10-15',
            'payment_amount' => 125,
            'payment_note' => 'Rechnung RE-100',
        ]);

        $records = app(PaymentReportService::class)
            ->query(
                CarbonImmutable::parse('2026-10-01'),
                CarbonImmutable::parse('2026-10-31'),
                paymentMethodId: $method->getKey(),
            )
            ->get();

        $this->assertTrue($records->contains($payment));
    }
}

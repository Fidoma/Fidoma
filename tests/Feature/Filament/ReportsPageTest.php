<?php

namespace Tests\Feature\Filament;

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

    public function test_reports_page_shows_all_report_tabs_and_period_filters(): void
    {
        CarbonImmutable::setTestNow('2026-10-15');

        $this->actingAs(User::factory()->create())
            ->get('/admin/reports')
            ->assertOk()
            ->assertSee('Umsatzsteuer')
            ->assertSee('Zahlungen')
            ->assertSee('Rechnungen')
            ->assertSee('Aktueller Monat')
            ->assertSee('Benutzerdefiniert');
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

    public function test_csv_export_uses_the_selected_report_and_period(): void
    {
        CarbonImmutable::setTestNow('2026-10-15');

        Livewire::actingAs(User::factory()->create())
            ->test(Reports::class)
            ->call('setTab', 'invoices')
            ->call('exportCsv')
            ->assertFileDownloaded('fidoma-rechnungen-2026-10-01-2026-10-31.csv');
    }

    public function test_excel_export_uses_the_selected_report_and_period(): void
    {
        CarbonImmutable::setTestNow('2026-10-15');

        Livewire::actingAs(User::factory()->create())
            ->test(Reports::class)
            ->call('setTab', 'payments')
            ->call('exportExcel')
            ->assertFileDownloaded('fidoma-zahlungen-2026-10-01-2026-10-31.xlsx');
    }
}

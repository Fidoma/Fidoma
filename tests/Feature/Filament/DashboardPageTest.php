<?php

namespace Tests\Feature\Filament;

use App\Features\Dashboard\Filament\Pages\Dashboard;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class DashboardPageTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        CarbonImmutable::setTestNow();

        parent::tearDown();
    }

    public function test_empty_date_fields_can_be_applied_without_validation_error(): void
    {
        CarbonImmutable::setTestNow('2026-10-15');

        Livewire::actingAs(User::factory()->create())
            ->test(Dashboard::class)
            ->call('clearDateFilter')
            ->call('applyDateFilter')
            ->assertHasNoErrors(['startDate', 'endDate'])
            ->assertSet('startDate', '')
            ->assertSet('endDate', '')
            ->assertSet('filters.start_date', fn (string $date): bool => $date <= '2026-10-15')
            ->assertSet('filters.end_date', '2026-10-15');
    }

    public function test_dashboard_period_dropdown_applies_quarters_year_and_resets_dates(): void
    {
        CarbonImmutable::setTestNow('2026-10-15');

        Livewire::actingAs(User::factory()->create())
            ->test(Dashboard::class)
            ->assertSee('Q1')
            ->assertSee('Q2')
            ->assertSee('Q3')
            ->assertSee('Q4')
            ->assertSee('Jahr')
            ->assertSee('Gesamt')
            ->assertDontSee('Zurücksetzen')
            ->assertDontSee('Benutzerdefiniert')
            ->call('selectPeriod', 'q2')
            ->assertSet('selectedPeriod', 'q2')
            ->assertSet('filters.start_date', '2026-04-01')
            ->assertSet('filters.end_date', '2026-06-30')
            ->call('selectPeriod', 'year')
            ->assertSet('selectedPeriod', 'year')
            ->assertSet('filters.start_date', '2026-01-01')
            ->assertSet('filters.end_date', '2026-12-31')
            ->call('selectPeriod', 'reset')
            ->assertSet('selectedPeriod', 'custom')
            ->assertSet('startDate', '')
            ->assertSet('endDate', '')
            ->assertSet('filters.start_date', '2026-10-01')
            ->assertSet('filters.end_date', '2026-10-15')
            ->set('startDate', '2026-02-10')
            ->set('endDate', '2026-02-20')
            ->call('applyDateFilter')
            ->assertHasNoErrors(['startDate', 'endDate'])
            ->assertSet('filters.start_date', '2026-02-10')
            ->assertSet('filters.end_date', '2026-02-20');
    }
}

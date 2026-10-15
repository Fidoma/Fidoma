<?php

namespace Tests\Feature\Filament;

use App\Filament\Pages\Dashboard;
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
}

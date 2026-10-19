<?php

namespace Tests\Feature\TradeFairs;

use App\Features\TradeFairs\Livewire\UpcomingTradeFairsTable;
use App\Features\TradeFairs\Models\TradeFair;
use App\Features\TradeFairs\Services\TradeFairCsvImporter;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class TradeFairTest extends TestCase
{
    use RefreshDatabase;

    public function test_csv_import_is_repeatable_updates_dates_and_classifies_locations(): void
    {
        $path = $this->csv(<<<'CSV'
Startdatum;Enddatum;Name;Slogan;Ort;Land;Webseite
"16. 03. 2027";"18. 03. 2027";"LogiMAT";"Internationale Fachmesse";"Messepiazza 1, 70629 Stuttgart";Deutschland;www.logimat-messe.de/de
"01. 04. 2027";"02. 04. 2027";"Nordmesse";;Hamburg;Deutschland;
"03. 05. 2027";"04. 05. 2027";"Ohne Ort";;;Deutschland;
CSV);
        $importer = app(TradeFairCsvImporter::class);

        $first = $importer->import($path);
        $second = $importer->import($path);

        $this->assertSame(['created' => 3, 'updated' => 0, 'unchanged' => 0, 'failed' => 0], $first);
        $this->assertSame(['created' => 0, 'updated' => 0, 'unchanged' => 3, 'failed' => 0], $second);
        $this->assertDatabaseCount('trade_fairs', 3);
        $this->assertDatabaseHas('trade_fairs', [
            'name' => 'LogiMAT',
            'start_date' => '2027-03-16',
            'end_date' => '2027-03-18',
            'website' => 'https://www.logimat-messe.de/de',
            'is_local' => true,
        ]);
        $this->assertDatabaseHas('trade_fairs', ['name' => 'Nordmesse', 'is_local' => false]);
        $this->assertDatabaseHas('trade_fairs', ['name' => 'Ohne Ort', 'is_local' => null]);

        file_put_contents($path, str_replace('18. 03. 2027', '19. 03. 2027', file_get_contents($path)));
        $updated = $importer->import($path);

        $this->assertSame(1, $updated['updated']);
        $this->assertDatabaseHas('trade_fairs', ['name' => 'LogiMAT', 'end_date' => '2027-03-19']);
        $this->assertDatabaseCount('trade_fairs', 3);

        file_put_contents($path, str_replace('Messepiazza 1, 70629 Stuttgart', 'Messe Stuttgart, 70629 Stuttgart', file_get_contents($path)));
        $locationUpdated = $importer->import($path);

        $this->assertSame(1, $locationUpdated['updated']);
        $this->assertDatabaseHas('trade_fairs', ['name' => 'LogiMAT', 'location' => 'Messe Stuttgart, 70629 Stuttgart']);
        $this->assertDatabaseCount('trade_fairs', 3);
    }

    public function test_import_reports_invalid_rows_without_stopping_valid_rows(): void
    {
        $path = $this->csv(<<<'CSV'
Startdatum;Enddatum;Name;Slogan;Ort;Land;Webseite
"31. 02. 2027";"02. 03. 2027";"Ungültig";;Stuttgart;Deutschland;
"16. 03. 2027";"18. 03. 2027";"LogiMAT";;Stuttgart;Deutschland;
CSV);
        $errors = [];

        $result = app(TradeFairCsvImporter::class)->import(
            $path,
            function (int $line, string $reason) use (&$errors): void {
                $errors[$line] = $reason;
            },
        );

        $this->assertSame(['created' => 1, 'updated' => 0, 'unchanged' => 0, 'failed' => 1], $result);
        $this->assertArrayHasKey(2, $errors);
        $this->assertDatabaseHas('trade_fairs', ['name' => 'LogiMAT']);
    }

    public function test_authenticated_apis_return_only_active_local_overlapping_fairs(): void
    {
        $today = CarbonImmutable::today('Europe/Berlin');
        $start = $today->addDays(14);
        $end = $start->addDays(2);
        $this->actingAs(User::factory()->create());
        $visible = TradeFair::query()->create($this->fair([
            'name' => 'LogiMAT', 'start_date' => $start->toDateString(), 'end_date' => $end->toDateString(),
        ]));
        TradeFair::query()->create($this->fair([
            'identity_key' => hash('sha256', 'remote'), 'name' => 'Hamburg', 'is_local' => false,
        ]));
        TradeFair::query()->create($this->fair([
            'identity_key' => hash('sha256', 'inactive'), 'name' => 'Inaktiv', 'is_active' => false,
        ]));

        $this->getJson(route('trade-fairs.upcoming'))
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $visible->getKey())
            ->assertJsonPath('data.0.start_date', $start->toDateString());

        $this->getJson(route('trade-fairs.range', [
            'start' => $start->addDay()->toDateString(),
            'end' => $end->addDays(2)->toDateString(),
        ]))
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.name', 'LogiMAT');

        $this->getJson(route('trade-fairs.range', [
            'start' => $end->addDay()->toDateString(),
            'end' => $end->addDays(2)->toDateString(),
        ]))
            ->assertOk()
            ->assertJsonCount(0, 'data');
    }

    public function test_trade_fair_apis_require_authentication(): void
    {
        $this->getJson(route('trade-fairs.upcoming'))->assertUnauthorized();
        $this->getJson(route('trade-fairs.range', ['start' => '2027-03-01', 'end' => '2027-04-01']))
            ->assertUnauthorized();
    }

    public function test_upcoming_trade_fairs_use_a_filterable_paginated_filament_table(): void
    {
        $this->actingAs(User::factory()->create());
        $past = TradeFair::query()->create($this->fair([
            'identity_key' => hash('sha256', 'past'),
            'name' => 'Vergangene Messe',
            'start_date' => '2026-01-01',
            'end_date' => '2026-01-02',
        ]));
        $future = TradeFair::query()->create($this->fair([
            'identity_key' => hash('sha256', 'future'),
            'name' => 'Zukünftige Messe',
            'start_date' => '2030-01-01',
            'end_date' => '2030-01-02',
        ]));

        $component = Livewire::test(UpcomingTradeFairsTable::class)
            ->assertCanSeeTableRecords([$past, $future])
            ->assertTableColumnExists('name')
            ->assertTableColumnExists('start_date')
            ->assertTableColumnExists('end_date')
            ->assertTableColumnExists('location')
            ->assertTableColumnExists('website')
            ->assertTableFilterExists('period');

        $this->assertSame([5, 10, 25, 50], $component->instance()->getTable()->getPaginationPageOptions());
        $this->assertSame(5, $component->instance()->getTable()->getDefaultPaginationPageOption());
        $this->assertSame('upcoming-trade-fairs', $component->instance()->getTable()->getQueryStringIdentifier());
    }

    /** @param array<string, mixed> $overrides
     * @return array<string, mixed>
     */
    private function fair(array $overrides = []): array
    {
        return array_merge([
            'identity_key' => hash('sha256', 'logimat'),
            'name' => 'Messe',
            'start_date' => '2027-03-10',
            'end_date' => '2027-03-12',
            'is_local' => true,
            'is_active' => true,
            'source' => 'test',
        ], $overrides);
    }

    private function csv(string $contents): string
    {
        $path = tempnam(sys_get_temp_dir(), 'trade-fairs-');
        $this->assertNotFalse($path);
        file_put_contents($path, $contents."\n");

        return $path;
    }
}

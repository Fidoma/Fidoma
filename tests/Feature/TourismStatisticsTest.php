<?php

namespace Tests\Feature;

use App\Features\Clients\Models\Client;
use App\Features\Reservations\Actions\CreateReservation as CreateReservationAction;
use App\Features\Reservations\Enums\ReservationStatus;
use App\Features\Reservations\Models\Room;
use App\Features\TaxRates\Models\TaxRate;
use App\Features\TourismStatistics\Filament\Resources\TourismReports\Pages\CreateTourismReport;
use App\Features\TourismStatistics\Filament\Resources\TourismReports\TourismReportResource;
use App\Features\TourismStatistics\Models\TourismReport;
use App\Features\TourismStatistics\Services\HotelStatisticsCsvExporter;
use App\Features\TourismStatistics\Services\PopulateTourismReportFromReservations;
use App\Features\TourismStatistics\Services\ResidenceCountryMapper;
use App\Features\TourismStatistics\Services\TourismReportPlausibility;
use App\Features\TourismStatistics\Support\ResidenceCountryCatalog;
use App\Features\Units\Models\Unit;
use App\Models\User;
use Filament\Actions\Testing\TestAction;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Livewire\Livewire;
use Tests\TestCase;

class TourismStatisticsTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_open_the_tourism_statistics_form_from_the_statistics_group(): void
    {
        $this->actingAs(User::factory()->create());
        Filament::setCurrentPanel(Filament::getPanel('admin'));

        $this->assertSame('Tourismusstatistik', TourismReportResource::getNavigationLabel());
        $this->assertSame('Statistiken', TourismReportResource::getNavigationGroup());

        $this->get(TourismReportResource::getUrl('create'))
            ->assertOk()
            ->assertSeeText('Berichtsmonat')
            ->assertSeeText('Aus Reservierungen füllen')
            ->assertSeeText('Ankünfte und Übernachtungen nach Wohnsitzland')
            ->assertSeeTextInOrder(['Code', 'Wohnsitzland'])
            ->assertSeeTextInOrder(['Ohne Angabe', 'Summe', 'Ankünfte gesamt', 'Übernachtungen gesamt'])
            ->assertSeeText('Deutschland')
            ->assertSeeText('13');

        $createPage = Livewire::actingAs(User::factory()->create())
            ->test(CreateTourismReport::class)
            ->assertFormFieldDoesNotExist('establishment_id');

        $this->assertNull($createPage->instance()->getHeading());
        $this->assertFalse(Schema::hasTable('tourism_establishments'));
        $this->assertFalse(Schema::hasColumn('tourism_reports', 'establishment_id'));
    }

    public function test_export_headings_match_the_official_hote_sample_exactly(): void
    {
        $line = trim((string) file_get_contents(base_path('tests/Fixtures/IMPORT_Musterdatei_HOTE.header.csv')));
        $expected = str_getcsv($line, ';', '"', '');

        if (array_last($expected) === '') {
            array_pop($expected);
        }

        $actual = app(HotelStatisticsCsvExporter::class)->headings();

        $this->assertCount(116, $actual);
        $this->assertSame($expected, $actual);
        $this->assertSame('ANK_Golfstaaten', $actual[7]);
        $this->assertSame('ANK_Zypern', $actual[60]);
        $this->assertSame('Bemerkungen', $actual[61]);
        $this->assertSame('UEB_Golfstaaten', $actual[62]);
        $this->assertSame('UEB_Zypern', $actual[115]);
        $this->assertCount(54, collect(ResidenceCountryCatalog::all())->pluck('code')->filter()->unique());
        $this->assertSame(
            collect(ResidenceCountryCatalog::sortedByCode())->pluck('code')->sort()->values()->all(),
            collect(ResidenceCountryCatalog::sortedByCode())->pluck('code')->all(),
        );
        $this->assertIsInt(ResidenceCountryCatalog::find('germany')['code']);
        $this->assertSame(13, ResidenceCountryCatalog::sortedByCode()[0]['code']);
        $this->assertSame(
            collect(ResidenceCountryCatalog::sortedByCode())->pluck('key')->all(),
            collect(ResidenceCountryCatalog::emptyRowsByCode())->pluck('country_key')->all(),
        );
    }

    public function test_reservation_data_prefills_capacity_room_days_and_country_values_for_the_month(): void
    {
        Room::query()->create(['room_number' => '101', 'room_capacity' => 2, 'room_active' => true]);
        Room::query()->create(['room_number' => '102', 'room_capacity' => 3, 'room_active' => true]);
        Room::query()->create(['room_number' => '103', 'room_capacity' => 4, 'room_active' => false]);

        $this->createReservationStay('DE', ReservationStatus::Confirmed, '2026-10-03', '2026-10-06', 1);
        $this->createReservationStay('BE', ReservationStatus::Open, '2026-09-29', '2026-10-02', 2);
        $this->createReservationStay('AE', ReservationStatus::Confirmed, '2026-10-31', '2026-11-02', 1);
        $this->createReservationStay('DE', ReservationStatus::Canceled, '2026-10-10', '2026-10-12', 1);

        $prefill = app(PopulateTourismReportFromReservations::class)->handle(2026, 10);
        $rows = collect($prefill->countryValues)->keyBy('country_key');

        $this->assertSame(5, $prefill->bedsOffered);
        $this->assertSame(62, $prefill->availableGuestRoomDays);
        $this->assertSame(5, $prefill->occupiedGuestRoomDays);
        $this->assertSame(3, $prefill->reservationItemCount);
        $this->assertSame(1, $rows->get('germany')['arrivals']);
        $this->assertSame(3, $rows->get('germany')['overnight_stays']);
        $this->assertSame(0, $rows->get('belgium')['arrivals']);
        $this->assertSame(1, $rows->get('belgium')['overnight_stays']);
        $this->assertSame(1, $rows->get('gulf_states')['arrivals']);
        $this->assertSame(1, $rows->get('gulf_states')['overnight_stays']);
    }

    public function test_create_form_action_applies_the_automatic_prefill(): void
    {
        Room::query()->create(['room_number' => '201', 'room_capacity' => 2, 'room_active' => true]);
        Filament::setCurrentPanel(Filament::getPanel('admin'));

        Livewire::actingAs(User::factory()->create())
            ->test(CreateTourismReport::class)
            ->fillForm([
                'reporting_month' => 2,
                'reporting_year' => 2025,
            ])
            ->callAction(TestAction::make('populateFromReservations')->schemaComponent())
            ->assertSet('data.beds_offered', 2)
            ->assertSet('data.available_guest_room_days', 28)
            ->assertSet('data.occupied_guest_room_days', 0)
            ->assertSet('data.data_source', 'reservations')
            ->assertNotified('Meldung wurde automatisch ausgefüllt');
    }

    public function test_client_residence_mapper_supports_iso_codes_names_and_fallback(): void
    {
        $mapper = app(ResidenceCountryMapper::class);

        $this->assertSame('germany', $mapper->countryKey('DE'));
        $this->assertSame('germany', $mapper->countryKey('Deutschland'));
        $this->assertSame('gulf_states', $mapper->countryKey('AE'));
        $this->assertSame('other_africa', $mapper->countryKey('MA'));
        $this->assertSame('not_specified', $mapper->countryKey(null));
        $this->assertSame('not_specified', $mapper->countryKey('unbekannt'));
    }

    public function test_monthly_report_can_be_created_manually_with_all_country_positions(): void
    {
        $user = User::factory()->create();
        $rows = ResidenceCountryCatalog::emptyRows();
        $rows[6]['arrivals'] = 120;
        $rows[6]['overnight_stays'] = 260;
        Filament::setCurrentPanel(Filament::getPanel('admin'));

        Livewire::actingAs($user)
            ->test(CreateTourismReport::class)
            ->fillForm([
                'reporting_month' => 8,
                'reporting_year' => 2026,
                'beds_offered' => 30,
                'available_guest_room_days' => 700,
                'occupied_guest_room_days' => 420,
                'countryValues' => $rows,
                'remarks' => 'Manuelle Monatsmeldung',
            ])
            ->call('create')
            ->assertHasNoFormErrors()
            ->assertRedirect();

        $report = TourismReport::query()->firstOrFail();

        $this->assertSame('manual', $report->data_source);
        $this->assertCount(54, $report->countryValues);
        $this->assertSame(13, ResidenceCountryCatalog::find($report->countryValues->first()->country_key)['code']);
        $this->assertSame(120, $report->countryValues->firstWhere('country_key', 'germany')?->arrivals);
        $this->assertSame(260, $report->totalOvernightStays());
    }

    public function test_export_has_116_fields_windows_1252_crlf_and_optional_header(): void
    {
        $report = $this->createReport();
        $report->update(['remarks' => 'Gäste aus Köln']);
        $report->countryValues()->where('country_key', 'germany')->update([
            'arrivals' => 123456,
            'overnight_stays' => 234567,
        ]);
        $report->countryValues()->where('country_key', 'belgium')->update([
            'arrivals' => 12,
            'overnight_stays' => 20,
        ]);

        $content = app(HotelStatisticsCsvExporter::class)->content($report->fresh(), true);

        $this->assertStringContainsString("\r\n", $content);
        $this->assertStringNotContainsString("\xEF\xBB\xBF", $content);
        $this->assertStringContainsString("G\xE4ste aus K\xF6ln", $content);

        $lines = explode("\r\n", rtrim($content, "\r\n"));
        $this->assertCount(2, $lines);
        $this->assertCount(116, str_getcsv($lines[0], ';', '"', ''));

        $row = str_getcsv($lines[1], ';', '"', '');
        $this->assertCount(116, $row);
        $this->assertSame('1', $row[0]);
        $this->assertSame('123456', $row[13]);
        $this->assertSame('G'.chr(0xE4).'ste aus K'.chr(0xF6).'ln', $row[61]);
        $this->assertSame('234567', $row[68]);
    }

    public function test_missing_values_are_exported_as_empty_fields_and_sample_values_are_never_imported(): void
    {
        $report = $this->createReport();
        $row = app(HotelStatisticsCsvExporter::class)->row($report->fresh());

        $this->assertSame('', (string) $row[2]);
        $this->assertSame('', (string) $row[7]);
        $this->assertSame('', (string) $row[61]);
        $this->assertSame('', (string) $row[115]);
        $this->assertDatabaseMissing('tourism_reports', ['remarks' => 'Test Importschnittstelle HOTE 45412']);
    }

    public function test_download_filename_uses_the_company_name_from_the_user_profile(): void
    {
        $this->actingAs(User::factory()->create([
            'name' => 'Arianit Mustermann',
            'company_name' => 'Hotel Grüner Baum',
        ]));

        $response = app(HotelStatisticsCsvExporter::class)->download($this->createReport());

        $this->assertStringContainsString(
            'HOTE_hotel-gruner-baum_2026-09.csv',
            (string) $response->headers->get('Content-Disposition'),
        );
    }

    public function test_plausibility_enforces_country_limits_and_overnight_stays(): void
    {
        $report = $this->createReport();
        $report->countryValues()->where('country_key', 'germany')->update([
            'arrivals' => 999999,
            'overnight_stays' => 999999,
        ]);
        $report->countryValues()->where('country_key', 'belgium')->update([
            'arrivals' => 100000,
            'overnight_stays' => 99999,
        ]);

        $errors = app(TourismReportPlausibility::class)->validate($report->fresh('countryValues'));

        $this->assertFalse($errors->has('country_values.germany.arrivals'));
        $this->assertTrue($errors->has('country_values.belgium.arrivals'));
        $this->assertTrue($errors->has('country_values.belgium.overnight_stays'));
    }

    private function createReport(): TourismReport
    {
        $report = TourismReport::query()->create([
            'reporting_year' => 2026,
            'reporting_month' => 9,
            'beds_offered' => 42,
        ]);

        $report->countryValues()->createMany(ResidenceCountryCatalog::emptyRows());

        return $report;
    }

    private function createReservationStay(
        string $country,
        ReservationStatus $status,
        string $arrival,
        string $departure,
        int $roomId,
    ): void {
        $client = Client::query()->create([
            'client_name' => 'Gast '.$country.$arrival,
            'client_country' => $country,
            'client_active' => true,
        ]);
        $this->actingAs(User::factory()->create());
        $reservation = app(CreateReservationAction::class)->handle([
            'client_id' => $client->getKey(),
            'reservation_status_id' => $status,
            'reservation_date_created' => '2026-09-01',
            'reservation_discount_amount' => 0,
            'reservation_discount_percent' => 0,
        ]);

        $reservation->items()->create([
            'room_id' => $roomId,
            'arrival_date' => $arrival,
            'departure_date' => $departure,
            'item_name' => 'Übernachtung',
            'item_tax_rate_id' => TaxRate::query()->firstOrFail()->getKey(),
            'item_product_unit_id' => Unit::query()->firstOrFail()->getKey(),
            'item_quantity' => 1,
            'item_price' => 0,
            'item_discount_amount' => 0,
            'item_order' => 1,
            'item_tax_included' => false,
        ]);
    }
}

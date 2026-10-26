<?php

namespace Tests\Feature;

use App\Features\Clients\Models\Client;
use App\Features\Invoices\Enums\InvoiceStatus;
use App\Features\Invoices\Models\Invoice;
use App\Features\Payments\Models\Payment;
use App\Features\Reservations\Actions\ConvertReservationToInvoice;
use App\Features\Reservations\Actions\CreateReservation;
use App\Features\Reservations\Actions\RecalculateReservation;
use App\Features\Reservations\Data\ReservationStatisticsFilter;
use App\Features\Reservations\Enums\ReservationStatus;
use App\Features\Reservations\Filament\Widgets\ReservationConflicts;
use App\Features\Reservations\Filament\Widgets\ReservationListKpis;
use App\Features\Reservations\Filament\Widgets\ReservationOccupancyChart;
use App\Features\Reservations\Filament\Widgets\ReservationStatisticsKpis;
use App\Features\Reservations\Filament\Widgets\ReservationValueChart;
use App\Features\Reservations\Filament\Widgets\ReservationVolumeChart;
use App\Features\Reservations\Filament\Widgets\RoomStatistics;
use App\Features\Reservations\Models\Reservation;
use App\Features\Reservations\Models\Room;
use App\Features\Reservations\Services\ReservationStatisticsService;
use App\Features\TaxRates\Models\TaxRate;
use App\Features\Units\Models\Unit;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ReservationStatisticsTest extends TestCase
{
    use RefreshDatabase;

    private Client $client;

    private TaxRate $taxRate;

    private Unit $unit;

    protected function setUp(): void
    {
        parent::setUp();

        $this->clearStatisticsFixtures();
        CarbonImmutable::setTestNow('2026-10-15 12:00:00');
        $this->actingAs(User::factory()->create());
        $this->client = Client::query()->create([
            'client_name' => 'Statistikgast',
            'client_active' => true,
        ]);
        $this->taxRate = TaxRate::query()->firstOrFail();
        $this->unit = Unit::query()->firstOrFail();
    }

    protected function tearDown(): void
    {
        $this->clearStatisticsFixtures();
        CarbonImmutable::setTestNow();

        parent::tearDown();
    }

    public function test_room_nights_use_exclusive_departures_and_deduplicate_overlaps(): void
    {
        $roomOne = $this->room('1');
        $roomTwo = $this->room('2');
        $confirmed = $this->reservation(ReservationStatus::Confirmed, [
            [$roomOne, '2026-10-31', '2026-11-03'],
            [$roomTwo, '2026-11-10', '2026-11-12'],
        ]);
        $this->reservation(ReservationStatus::Open, [
            [$roomOne, '2026-11-02', '2026-11-05'],
        ]);
        $this->reservation(ReservationStatus::Canceled, [
            [$roomTwo, '2026-11-01', '2026-11-10'],
        ]);
        $this->reservation(ReservationStatus::Confirmed, [
            [$roomTwo, '2026-11-30', '2026-12-02'],
        ]);

        $filter = new ReservationStatisticsFilter(
            CarbonImmutable::parse('2026-11-01'),
            CarbonImmutable::parse('2026-12-01'),
        );
        $summary = app(ReservationStatisticsService::class)->summary($filter);

        $this->assertSame(4, $summary['reservations']);
        $this->assertSame(2, $summary['status_counts'][ReservationStatus::Confirmed->value]);
        $this->assertSame(1, $summary['status_counts'][ReservationStatus::Open->value]);
        $this->assertSame(1, $summary['status_counts'][ReservationStatus::Canceled->value]);
        $this->assertSame(7, $summary['occupied_room_nights']);
        $this->assertSame(60, $summary['available_room_nights']);
        $this->assertSame(53, $summary['unused_room_nights']);
        $this->assertSame(1, $summary['conflicts']);
        $this->assertSame(3, $summary['future_arrivals']);

        $roomMetrics = app(ReservationStatisticsService::class)->roomMetrics($filter);
        $this->assertSame(4, $roomMetrics->firstWhere('room_id', $roomOne->getKey())['occupied']);
        $this->assertSame(3, $roomMetrics->firstWhere('room_id', $roomTwo->getKey())['occupied']);
        $this->assertSame(1, $roomMetrics->firstWhere('room_id', $roomOne->getKey())['conflicts']);

        $this->assertSame(2, $confirmed->items()->count());
    }

    public function test_status_room_and_date_filters_keep_actual_occupancy_separate_from_forecast(): void
    {
        CarbonImmutable::setTestNow('2026-11-03 12:00:00');
        $roomOne = $this->room('1');
        $roomTwo = $this->room('2');
        $this->reservation(ReservationStatus::Confirmed, [
            [$roomOne, '2026-11-01', '2026-11-05'],
        ]);
        $this->reservation(ReservationStatus::Open, [
            [$roomTwo, '2026-11-03', '2026-11-07'],
        ]);
        $this->reservation(ReservationStatus::Canceled, [
            [$roomOne, '2026-11-02', '2026-11-06'],
        ]);

        $filter = new ReservationStatisticsFilter(
            CarbonImmutable::parse('2026-11-01'),
            CarbonImmutable::parse('2026-11-08'),
        );
        $summary = app(ReservationStatisticsService::class)->summary($filter);
        $this->assertSame(8, $summary['occupied_room_nights']);
        $this->assertSame(2, $summary['actual_room_nights']);
        $this->assertSame(6, $summary['forecast_room_nights']);
        $this->assertSame(2, $summary['confirmed_forecast_room_nights']);
        $this->assertSame(4, $summary['open_forecast_room_nights']);

        $confirmed = app(ReservationStatisticsService::class)->summary(new ReservationStatisticsFilter(
            $filter->start,
            $filter->end,
            $roomOne->getKey(),
            ReservationStatus::Confirmed,
        ));
        $canceled = app(ReservationStatisticsService::class)->summary(new ReservationStatisticsFilter(
            $filter->start,
            $filter->end,
            status: ReservationStatus::Canceled,
        ));

        $this->assertSame(4, $confirmed['occupied_room_nights']);
        $this->assertSame(7, $confirmed['available_room_nights']);
        $this->assertSame(1, $canceled['reservations']);
        $this->assertSame(0, $canceled['occupied_room_nights']);
        $this->assertSame(0, $canceled['future_arrivals']);
    }

    public function test_reservation_invoice_and_payment_values_are_reported_separately(): void
    {
        $room = $this->room('10');
        $reservation = $this->reservation(ReservationStatus::Confirmed, [
            [$room, '2026-11-01', '2026-11-03'],
        ], 100);
        $invoice = app(ConvertReservationToInvoice::class)->handle($reservation);
        $filter = new ReservationStatisticsFilter(
            CarbonImmutable::parse('2026-11-01'),
            CarbonImmutable::parse('2026-12-01'),
        );

        $draftSummary = app(ReservationStatisticsService::class)->summary($filter);
        $this->assertGreaterThan(0, $draftSummary['reservation_value']);
        $this->assertSame(0.0, $draftSummary['invoiced_value']);
        $this->assertSame(1, $draftSummary['unbilled']);
        $this->assertSame(1, $draftSummary['invoice_status_counts'][InvoiceStatus::Draft->value]);

        $invoice->update([
            'invoice_status_id' => InvoiceStatus::Open,
            'invoice_number' => 'RE-STAT-1',
        ]);
        Payment::query()->create([
            'invoice_id' => $invoice->getKey(),
            'payment_method_id' => 1,
            'payment_date' => '2026-11-15',
            'payment_amount' => 40,
            'payment_note' => '',
        ]);
        $invoice->amount()->update([
            'invoice_paid' => 40,
            'invoice_balance' => (float) $invoice->amount->invoice_total - 40,
        ]);

        $summary = app(ReservationStatisticsService::class)->summary($filter);
        $this->assertSame((float) $reservation->amount->reservation_total, $summary['reservation_value']);
        $this->assertSame((float) $invoice->amount->invoice_total, $summary['invoiced_value']);
        $this->assertSame(40.0, $summary['payments_received']);
        $this->assertSame(1, $summary['partially_paid']);
        $this->assertSame(0, $summary['unbilled']);
        $this->assertSame(1, $summary['invoice_status_counts'][InvoiceStatus::Open->value]);
    }

    public function test_statistics_widgets_render_with_shared_page_filters(): void
    {
        $filters = [
            'start_date' => '2026-11-01',
            'end_date' => '2026-11-30',
            'room_id' => null,
            'status' => null,
        ];

        Livewire::test(ReservationStatisticsKpis::class, ['pageFilters' => $filters])
            ->assertSee('Reservierungen insgesamt')
            ->assertSee('Zimmerauslastung');
        Livewire::test(ReservationVolumeChart::class, ['pageFilters' => $filters])
            ->assertSee('Reservierungen und Anreisen');
        Livewire::test(ReservationOccupancyChart::class, ['pageFilters' => $filters])
            ->assertSee('Zimmerauslastung im Monatsverlauf');
        Livewire::test(ReservationValueChart::class, ['pageFilters' => $filters])
            ->assertSee('Reservierungswerte und Rechnungsentwicklung');
        Livewire::test(RoomStatistics::class, ['pageFilters' => $filters])
            ->assertSee('Zimmerauswertung');
        Livewire::test(ReservationConflicts::class, ['pageFilters' => $filters])
            ->assertDontSee('Zimmerüberschneidung');
        Livewire::test(ReservationListKpis::class)
            ->assertSee('Aktive Reservierungen')
            ->assertSee('Anreisen in 30 Tagen');
    }

    private function room(string $number): Room
    {
        return Room::query()->create([
            'room_number' => $number,
            'room_capacity' => 2,
            'room_active' => true,
        ]);
    }

    private function clearStatisticsFixtures(): void
    {
        Reservation::query()->get()->each->delete();
        Invoice::query()->get()->each->delete();
        Room::query()->delete();
    }

    /** @param list<array{0: Room, 1: string, 2: string}> $stays */
    private function reservation(ReservationStatus $status, array $stays, float $price = 50): Reservation
    {
        $reservation = app(CreateReservation::class)->handle([
            'client_id' => $this->client->getKey(),
            'reservation_date_created' => '2026-10-15',
            'reservation_status_id' => $status,
            'reservation_discount_amount' => 0,
            'reservation_discount_percent' => 0,
        ]);

        foreach ($stays as $index => [$room, $arrival, $departure]) {
            $reservation->items()->create([
                'room_id' => $room->getKey(),
                'arrival_date' => $arrival,
                'departure_date' => $departure,
                'item_tax_rate_id' => $this->taxRate->getKey(),
                'item_name' => 'Übernachtung',
                'item_description' => '',
                'item_quantity' => CarbonImmutable::parse($arrival)->diffInDays($departure),
                'item_price' => $price,
                'item_discount_amount' => 0,
                'item_order' => $index + 1,
                'item_product_unit_id' => $this->unit->getKey(),
                'item_tax_included' => false,
            ]);
        }

        app(RecalculateReservation::class)->handle($reservation);

        return $reservation->refresh();
    }
}

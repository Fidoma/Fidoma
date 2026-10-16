<?php

namespace Tests\Feature;

use App\Features\Clients\Filament\Resources\Clients\ClientResource;
use App\Features\Clients\Models\Client;
use App\Features\Invoices\Filament\Resources\Invoices\InvoiceResource;
use App\Features\Invoices\Models\Invoice;
use App\Features\Products\Models\Product;
use App\Features\Reservations\Actions\ConvertReservationToInvoice;
use App\Features\Reservations\Actions\CreateReservation;
use App\Features\Reservations\Actions\RecalculateReservation;
use App\Features\Reservations\Enums\ReservationStatus;
use App\Features\Reservations\Filament\Resources\Reservations\Pages\ListReservations;
use App\Features\Reservations\Filament\Resources\Reservations\ReservationResource;
use App\Features\Reservations\Models\Room;
use App\Features\TaxRates\Models\TaxRate;
use App\Features\Units\Models\Unit;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ReservationTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_calculates_a_reservation_and_converts_it_to_an_invoice_once(): void
    {
        $this->actingAs(User::factory()->create());
        $client = Client::query()->create(['client_name' => 'Hotelgast', 'client_active' => true]);
        $taxRate = TaxRate::query()->firstOrFail();
        $unit = Unit::query()->firstOrFail();
        $room = Room::query()->create([
            'room_number' => '101', 'room_name' => 'Gartenblick', 'room_capacity' => 2, 'room_active' => true,
        ]);
        $product = Product::query()->create([
            'product_sku' => 'NACHT', 'product_name' => 'Übernachtung', 'product_description' => 'Übernachtung',
            'product_price' => 100, 'tax_rate_id' => $taxRate->getKey(), 'unit_id' => $unit->getKey(),
        ]);

        $reservation = app(CreateReservation::class)->handle([
            'client_id' => $client->getKey(),
            'reservation_date_created' => '2026-10-14',
            'reservation_status_id' => ReservationStatus::Confirmed,
            'reservation_discount_amount' => 0,
            'reservation_discount_percent' => 0,
        ]);
        $reservation->items()->create([
            'room_id' => $room->getKey(), 'arrival_date' => '2026-11-01', 'departure_date' => '2026-11-04',
            'item_tax_rate_id' => $taxRate->getKey(), 'item_product_id' => $product->getKey(),
            'item_name' => 'Übernachtung', 'item_description' => 'Drei Nächte', 'item_quantity' => 3,
            'item_price' => 100, 'item_discount_amount' => 0, 'item_order' => 1,
            'item_product_unit_id' => $unit->getKey(), 'item_tax_included' => false,
        ]);

        app(RecalculateReservation::class)->handle($reservation);
        $reservation->refresh();

        $this->assertSame('300.00', $reservation->amount->reservation_item_subtotal);
        $this->assertSame('321.00', $reservation->amount->reservation_total);
        $this->assertSame('2026-11-13', $reservation->reservation_date_expires->toDateString());

        $invoiceCountBeforeConversion = Invoice::query()->count();
        $firstInvoice = app(ConvertReservationToInvoice::class)->handle($reservation);
        $secondInvoice = app(ConvertReservationToInvoice::class)->handle($reservation->refresh());

        $this->assertTrue($firstInvoice->is($secondInvoice));
        $this->assertSame($invoiceCountBeforeConversion + 1, Invoice::query()->count());
        $this->assertSame(1, $firstInvoice->items()->count());
        $this->assertSame('321.00', $firstInvoice->amount->invoice_total);
        $this->assertSame($firstInvoice->getKey(), $reservation->refresh()->invoice_id);

        $component = Livewire::test(ListReservations::class)
            ->assertCanSeeTableRecords([$reservation])
            ->assertTableColumnExists(
                'reservation_number',
                fn ($column): bool => $column->getUrl() === ReservationResource::getUrl('edit', ['record' => $reservation]),
                $reservation,
            )
            ->assertTableColumnExists(
                'invoice.invoice_number',
                fn ($column): bool => $column->getUrl() === InvoiceResource::getUrl('edit', ['record' => $firstInvoice]),
                $reservation,
            )
            ->assertTableColumnExists(
                'client.client_name',
                fn ($column): bool => $column->getUrl() === ClientResource::getUrl('view', ['record' => $client]),
                $reservation,
            )
            ->assertTableColumnExists(
                'items.0.arrival_date',
                fn ($column): bool => $column->getUrl() === null,
                $reservation,
            );

        $this->assertNull($component->instance()->getTable()->getRecordUrl($reservation));
        $this->assertSame(
            ['reservation_number', 'invoice.invoice_number', 'client.client_name'],
            array_slice(array_keys($component->instance()->getTable()->getColumns()), 0, 3),
        );
    }

    public function test_authenticated_user_can_open_reservation_pages(): void
    {
        $this->actingAs(User::factory()->create());

        $this->get('/admin/reservations')->assertOk();
        $this->get('/admin/reservations/create')
            ->assertOk()
            ->assertSeeText('Kunde')
            ->assertSeeText('Reservierungsnummer')
            ->assertSeeText('Reservierungsdatum')
            ->assertSeeText('Status')
            ->assertDontSeeText('Gültig bis');
        $this->get('/admin/reservations/belegungsplan')->assertOk();
        $this->get('/admin/rooms')->assertOk();
    }
}

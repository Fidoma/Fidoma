<?php

namespace Database\Seeders;

use App\Features\Clients\Models\Client;
use App\Features\Products\Models\Product;
use App\Features\Reservations\Actions\CreateReservation;
use App\Features\Reservations\Actions\RecalculateReservation;
use App\Features\Reservations\Enums\ReservationStatus;
use App\Features\Reservations\Models\Reservation;
use App\Features\Reservations\Models\Room;
use App\Features\TaxRates\Models\TaxRate;
use App\Features\Units\Models\Unit;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ReservationSeeder extends Seeder
{
    private const SEED_MARKER = '[Faker-Demodaten]';

    public function run(): void
    {
        $faker = fake('de_DE');
        $faker->seed(20261014);

        DB::transaction(function () use ($faker): void {
            Reservation::query()
                ->where('notes', self::SEED_MARKER)
                ->get()
                ->each
                ->delete();

            $user = User::query()->firstOrCreate(
                ['email' => 'test@example.com'],
                ['name' => 'Test User', 'password' => bcrypt('password')],
            );
            Auth::login($user);

            $clients = Client::query()->where('client_active', true)->get();
            while ($clients->count() < 10) {
                Client::query()->create([
                    'client_name' => $faker->name(),
                    'client_email' => $faker->unique()->safeEmail(),
                    'client_phone' => $faker->phoneNumber(),
                    'client_city' => $faker->city(),
                    'client_country' => 'DE',
                    'client_active' => true,
                ]);
                $clients = Client::query()->where('client_active', true)->get();
            }

            $rooms = collect([
                ['room_number' => '101', 'room_name' => 'Gartenblick', 'room_capacity' => 2],
                ['room_number' => '102', 'room_name' => 'Stadtblick', 'room_capacity' => 2],
                ['room_number' => '103', 'room_name' => 'Familienzimmer', 'room_capacity' => 4],
                ['room_number' => '201', 'room_name' => 'Komfortzimmer', 'room_capacity' => 2],
                ['room_number' => '202', 'room_name' => 'Junior Suite', 'room_capacity' => 3],
                ['room_number' => '301', 'room_name' => 'Suite', 'room_capacity' => 4],
            ])->map(fn (array $room): Room => Room::query()->updateOrCreate(
                ['room_number' => $room['room_number']],
                [...$room, 'room_active' => true],
            ));

            $taxRate = TaxRate::query()->firstOrFail();
            $unit = Unit::query()->firstOrFail();
            $product = Product::query()->firstOrCreate(
                ['product_sku' => 'HOTEL-NACHT'],
                [
                    'product_name' => 'Übernachtung',
                    'product_description' => 'Übernachtung inklusive Frühstück',
                    'product_price' => 119,
                    'tax_rate_id' => $taxRate->getKey(),
                    'unit_id' => $unit->getKey(),
                ],
            );

            foreach (range(1, 10) as $index) {
                $arrival = CarbonImmutable::instance($faker->dateTimeBetween('-5 days', '+35 days'))->startOfDay();
                $nights = $faker->numberBetween(1, 7);
                $departure = $arrival->addDays($nights);
                $room = $rooms->random();
                $client = $clients->random();

                $reservation = app(CreateReservation::class)->handle([
                    'client_id' => $client->getKey(),
                    'reservation_date_created' => now()->toDateString(),
                    'reservation_date_expires' => now()->addDays(30)->toDateString(),
                    'reservation_status_id' => $faker->randomElement(ReservationStatus::cases()),
                    'reservation_discount_amount' => 0,
                    'reservation_discount_percent' => $index % 5 === 0 ? 10 : 0,
                    'notes' => self::SEED_MARKER,
                ]);

                $reservation->items()->create([
                    'room_id' => $room->getKey(),
                    'arrival_date' => $arrival->toDateString(),
                    'departure_date' => $departure->toDateString(),
                    'item_tax_rate_id' => $product->tax_rate_id ?: $taxRate->getKey(),
                    'item_tax_included' => false,
                    'item_product_id' => $product->getKey(),
                    'item_name' => $product->product_name,
                    'item_description' => "{$nights} Nächte für {$client->client_name}",
                    'item_quantity' => $nights,
                    'item_price' => $product->product_price,
                    'item_discount_amount' => 0,
                    'item_order' => 1,
                    'item_product_unit_id' => $product->unit_id ?: $unit->getKey(),
                ]);

                app(RecalculateReservation::class)->handle($reservation);
            }

            Auth::logout();
        });
    }
}

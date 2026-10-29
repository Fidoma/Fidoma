<?php

namespace App\Features\Reservations\Actions;

use App\Features\Products\Models\Product;
use App\Features\Reservations\Enums\ReservationStatus;
use App\Features\Reservations\Models\Reservation;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

final class CreateReservationWithItem
{
    public function __construct(
        private readonly CreateReservation $createReservation,
        private readonly RecalculateReservation $recalculate,
    ) {}

    /** @param array{client_id: int|string, product_id: int|string, room_id: int|string, arrival_date: string, departure_date: string} $data */
    public function handle(array $data): Reservation
    {
        return DB::transaction(function () use ($data): Reservation {
            $product = Product::query()->findOrFail($data['product_id']);
            $arrival = CarbonImmutable::parse($data['arrival_date']);
            $departure = CarbonImmutable::parse($data['departure_date']);
            $reservation = $this->createReservation->handle([
                'client_id' => $data['client_id'],
                'reservation_date_created' => now()->toDateString(),
                'reservation_status_id' => ReservationStatus::Open,
                'reservation_discount_amount' => 0,
                'reservation_discount_percent' => 0,
            ]);

            $reservation->items()->create([
                'room_id' => $data['room_id'],
                'arrival_date' => $arrival->toDateString(),
                'departure_date' => $departure->toDateString(),
                'item_tax_rate_id' => $product->tax_rate_id,
                'item_tax_included' => $product->product_tax_included,
                'item_product_id' => $product->getKey(),
                'item_name' => $product->product_name,
                'item_description' => $product->product_description,
                'item_quantity' => max(1, $arrival->diffInDays($departure)),
                'item_price' => $product->product_price,
                'item_discount_amount' => 0,
                'item_order' => 1,
                'item_product_unit_id' => $product->unit_id,
            ]);

            return $this->recalculate->handle($reservation);
        });
    }
}

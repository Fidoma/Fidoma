<?php

namespace App\Features\Reservations\Actions;

use App\Features\Reservations\Enums\ReservationStatus;
use App\Features\Reservations\Models\Reservation;
use Illuminate\Support\Facades\DB;

final class UpdateReservationStatus
{
    public function handle(Reservation $reservation, ReservationStatus $status): Reservation
    {
        return DB::transaction(function () use ($reservation, $status): Reservation {
            $reservation = Reservation::query()->lockForUpdate()->findOrFail($reservation->getKey());

            if ($reservation->reservation_status_id !== $status) {
                $reservation->update(['reservation_status_id' => $status]);
            }

            return $reservation->refresh();
        });
    }
}

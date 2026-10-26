<?php

namespace App\Features\Reservations\Policies;

use App\Features\Reservations\Models\Reservation;
use App\Models\User;

final class ReservationPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Reservation $reservation): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, Reservation $reservation): bool
    {
        return true;
    }

    public function delete(User $user, Reservation $reservation): bool
    {
        return true;
    }
}

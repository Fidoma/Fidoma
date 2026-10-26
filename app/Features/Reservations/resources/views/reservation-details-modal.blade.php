@livewire(
    \App\Features\Reservations\Livewire\ReservationDetails::class,
    ['reservationId' => $reservationId],
    key('reservation-details-'.$reservationId)
)

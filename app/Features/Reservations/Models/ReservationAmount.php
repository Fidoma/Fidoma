<?php

namespace App\Features\Reservations\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReservationAmount extends Model
{
    protected $table = 'ip_reservation_amounts';

    protected $primaryKey = 'reservation_amount_id';

    public $timestamps = false;

    protected $guarded = [];

    /** @return BelongsTo<Reservation, $this> */
    public function reservation(): BelongsTo
    {
        return $this->belongsTo(Reservation::class, 'reservation_id', 'reservation_id');
    }
}

<?php

namespace App\Features\Reservations\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReservationItemAmount extends Model
{
    protected $table = 'ip_reservation_item_amounts';

    protected $primaryKey = 'item_amount_id';

    public $timestamps = false;

    protected $guarded = [];

    /** @return BelongsTo<ReservationItem, $this> */
    public function item(): BelongsTo
    {
        return $this->belongsTo(ReservationItem::class, 'item_id', 'item_id');
    }
}

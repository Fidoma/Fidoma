<?php

namespace App\Features\Reservations\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Room extends Model
{
    protected $table = 'ip_rooms';

    protected $primaryKey = 'room_id';

    public $timestamps = false;

    protected $fillable = ['room_number', 'room_name', 'room_capacity', 'room_active'];

    protected function casts(): array
    {
        return ['room_capacity' => 'integer', 'room_active' => 'boolean'];
    }

    /** @return HasMany<ReservationItem, $this> */
    public function reservationItems(): HasMany
    {
        return $this->hasMany(ReservationItem::class, 'room_id', 'room_id');
    }

    public function displayName(): string
    {
        return $this->room_name ? "{$this->room_number} – {$this->room_name}" : $this->room_number;
    }
}

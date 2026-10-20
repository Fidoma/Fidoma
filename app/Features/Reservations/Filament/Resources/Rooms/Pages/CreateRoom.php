<?php

namespace App\Features\Reservations\Filament\Resources\Rooms\Pages;

use App\Features\Reservations\Filament\Resources\Rooms\RoomResource;
use App\Filament\Resources\Pages\CreateRecord;

class CreateRoom extends CreateRecord
{
    protected static string $resource = RoomResource::class;
}

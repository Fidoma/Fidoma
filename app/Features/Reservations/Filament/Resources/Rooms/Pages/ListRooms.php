<?php

namespace App\Features\Reservations\Filament\Resources\Rooms\Pages;

use App\Features\Reservations\Filament\Resources\Rooms\RoomResource;
use App\Filament\Concerns\WithoutPageHeading;
use Filament\Resources\Pages\ListRecords;

class ListRooms extends ListRecords
{
    use WithoutPageHeading;

    protected static string $resource = RoomResource::class;
}

<?php

namespace App\Features\Reservations\Filament\Resources\Rooms\Pages;

use App\Features\Reservations\Filament\Resources\Rooms\RoomResource;
use Filament\Resources\Pages\EditRecord;

class EditRoom extends EditRecord
{
    protected static string $resource = RoomResource::class;
}

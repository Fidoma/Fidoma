<?php

namespace App\Features\Reservations\Filament\Resources\Reservations\Pages;

use App\Features\Reservations\Actions\CreateReservation as CreateReservationAction;
use App\Features\Reservations\Actions\RecalculateReservation;
use App\Features\Reservations\Filament\Resources\Reservations\ReservationResource;
use App\Filament\Resources\Pages\CreateRecord;
use Filament\Support\Enums\Width;
use Illuminate\Database\Eloquent\Model;

class CreateReservation extends CreateRecord
{
    protected static string $resource = ReservationResource::class;

    protected Width|string|null $maxContentWidth = Width::Full;

    protected function handleRecordCreation(array $data): Model
    {
        return app(CreateReservationAction::class)->handle($data);
    }

    protected function afterCreate(): void
    {
        app(RecalculateReservation::class)->handle($this->record);
    }
}

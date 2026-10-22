<?php

namespace App\Features\Reservations\Filament\Resources\Reservations\Pages;

use App\Features\Reservations\Actions\RecalculateReservation;
use App\Features\Reservations\Filament\Resources\Reservations\ReservationResource;
use App\Filament\Concerns\WithoutPageHeading;
use Filament\Actions\Action;
use Filament\Resources\Pages\EditRecord;
use Filament\Support\Enums\Width;

class EditReservation extends EditRecord
{
    use WithoutPageHeading;

    protected static string $resource = ReservationResource::class;

    protected Width|string|null $maxContentWidth = Width::Full;

    protected function afterSave(): void
    {
        app(RecalculateReservation::class)->handle($this->record);
    }

    /** @return array<Action> */
    protected function getFormActions(): array
    {
        return [];
    }
}

<?php

namespace App\Features\Reservations\Enums;

enum ReservationStatus: int
{
    case Open = 1;
    case Confirmed = 2;
    case Canceled = 3;

    public function label(): string
    {
        return match ($this) {
            self::Open => 'Offen',
            self::Confirmed => 'Bestätigt',
            self::Canceled => 'Storniert',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Open => 'gray',
            self::Confirmed => 'success',
            self::Canceled => 'danger',
        };
    }

    /** @return array<int, string> */
    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $status): array => [$status->value => $status->label()])
            ->all();
    }
}

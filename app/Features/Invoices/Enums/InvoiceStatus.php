<?php

namespace App\Features\Invoices\Enums;

enum InvoiceStatus: int
{
    case Draft = 1;
    case Open = 2;
    case Paid = 4;

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Entwurf',
            self::Open => 'Offen',
            self::Paid => 'Bezahlt',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Draft => 'gray',
            self::Open => 'info',
            self::Paid => 'success',
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

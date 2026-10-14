<?php

namespace App\Features\Invoices\Enums;

enum InvoiceStatus: int
{
    case Draft = 1;
    case Sent = 2;
    case Paid = 4;

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Entwurf',
            self::Sent => 'Versendet',
            self::Paid => 'Bezahlt',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Draft => 'gray',
            self::Sent => 'info',
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

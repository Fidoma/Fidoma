<?php

namespace App\Features\Invoices\Enums;

enum InvoiceType: string
{
    case Invoice = 'invoice';
    case Credit = 'credit';
    case Cancellation = 'cancellation';

    public function label(): string
    {
        return match ($this) {
            self::Invoice => 'Rechnung',
            self::Credit => 'Gutschrift',
            self::Cancellation => 'Stornorechnung',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Invoice => 'gray',
            self::Credit => 'success',
            self::Cancellation => 'danger',
        };
    }

    public function numberGroupName(): string
    {
        return match ($this) {
            self::Invoice => 'Rechnung',
            self::Credit => 'Gutschrift',
            self::Cancellation => 'Stornorechnung',
        };
    }

    public function sign(): int
    {
        return $this === self::Invoice ? 1 : -1;
    }
}

<?php

namespace App\Features\Invoices\Enums;

enum InvoiceBillingType: string
{
    case Standard = 'standard';
    case Progress = 'progress';
    case Final = 'final';

    public function label(): string
    {
        return __('invoices::messages.billing_types.'.$this->value);
    }

    public function color(): string
    {
        return match ($this) {
            self::Standard => 'gray',
            self::Progress => 'warning',
            self::Final => 'success',
        };
    }
}

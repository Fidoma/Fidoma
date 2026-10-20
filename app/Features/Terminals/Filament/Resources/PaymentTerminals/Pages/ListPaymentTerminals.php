<?php

namespace App\Features\Terminals\Filament\Resources\PaymentTerminals\Pages;

use App\Features\Terminals\Filament\Resources\PaymentTerminals\PaymentTerminalResource;
use App\Filament\Concerns\WithoutPageHeading;
use Filament\Resources\Pages\ListRecords;

class ListPaymentTerminals extends ListRecords
{
    use WithoutPageHeading;

    protected static string $resource = PaymentTerminalResource::class;
}

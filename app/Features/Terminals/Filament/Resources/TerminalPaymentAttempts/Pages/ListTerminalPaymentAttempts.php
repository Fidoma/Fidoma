<?php

namespace App\Features\Terminals\Filament\Resources\TerminalPaymentAttempts\Pages;

use App\Features\Terminals\Filament\Resources\TerminalPaymentAttempts\TerminalPaymentAttemptResource;
use App\Filament\Concerns\WithoutPageHeading;
use Filament\Resources\Pages\ListRecords;

class ListTerminalPaymentAttempts extends ListRecords
{
    use WithoutPageHeading;

    protected static string $resource = TerminalPaymentAttemptResource::class;
}

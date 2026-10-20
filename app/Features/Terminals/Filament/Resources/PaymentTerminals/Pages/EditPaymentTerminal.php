<?php

namespace App\Features\Terminals\Filament\Resources\PaymentTerminals\Pages;

use App\Features\Terminals\Filament\Resources\PaymentTerminals\PaymentTerminalResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditPaymentTerminal extends EditRecord
{
    protected static string $resource = PaymentTerminalResource::class;

    protected function getHeaderActions(): array
    {
        return [DeleteAction::make()];
    }
}

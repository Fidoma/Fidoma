<?php

namespace App\Features\Terminals\Filament\Resources\PaymentTerminals\Pages;

use App\Features\Terminals\Enums\TerminalOperatingStatus;
use App\Features\Terminals\Filament\Resources\PaymentTerminals\PaymentTerminalResource;
use Filament\Resources\Pages\CreateRecord;

class CreatePaymentTerminal extends CreateRecord
{
    protected static string $resource = PaymentTerminalResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['user_id'] = auth()->id();
        $data['operating_status'] = TerminalOperatingStatus::Idle;

        return $data;
    }
}

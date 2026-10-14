<?php

namespace App\Features\TaxRates\Filament\Resources\TaxRates\Pages;

use App\Features\TaxRates\Filament\Resources\TaxRates\TaxRateResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditTaxRate extends EditRecord
{
    protected static string $resource = TaxRateResource::class;

    protected function getHeaderActions(): array
    {
        return [DeleteAction::make()];
    }
}

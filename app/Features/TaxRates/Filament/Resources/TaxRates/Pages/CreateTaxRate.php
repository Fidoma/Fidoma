<?php

namespace App\Features\TaxRates\Filament\Resources\TaxRates\Pages;

use App\Features\TaxRates\Actions\CreateTaxRate as CreateTaxRateAction;
use App\Features\TaxRates\Filament\Resources\TaxRates\TaxRateResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreateTaxRate extends CreateRecord
{
    protected static string $resource = TaxRateResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        return app(CreateTaxRateAction::class)->handle($data);
    }
}

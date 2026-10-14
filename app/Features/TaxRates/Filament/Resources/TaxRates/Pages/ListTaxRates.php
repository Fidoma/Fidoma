<?php

namespace App\Features\TaxRates\Filament\Resources\TaxRates\Pages;

use App\Features\TaxRates\Filament\Resources\TaxRates\TaxRateResource;
use App\Filament\Concerns\WithoutPageHeading;
use Filament\Resources\Pages\ListRecords;

class ListTaxRates extends ListRecords
{
    use WithoutPageHeading;

    protected static string $resource = TaxRateResource::class;
}

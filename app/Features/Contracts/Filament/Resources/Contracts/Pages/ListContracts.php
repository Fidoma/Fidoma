<?php

namespace App\Features\Contracts\Filament\Resources\Contracts\Pages;

use App\Features\Contracts\Filament\Resources\Contracts\ContractResource;
use App\Filament\Concerns\WithoutPageHeading;
use Filament\Resources\Pages\ListRecords;

class ListContracts extends ListRecords
{
    use WithoutPageHeading;

    protected static string $resource = ContractResource::class;
}

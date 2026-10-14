<?php

namespace App\Features\Units\Filament\Resources\Units\Pages;

use App\Features\Units\Filament\Resources\Units\UnitResource;
use App\Filament\Concerns\WithoutPageHeading;
use Filament\Resources\Pages\ListRecords;

class ListUnits extends ListRecords
{
    use WithoutPageHeading;

    protected static string $resource = UnitResource::class;
}

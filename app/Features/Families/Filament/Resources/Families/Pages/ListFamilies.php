<?php

namespace App\Features\Families\Filament\Resources\Families\Pages;

use App\Features\Families\Filament\Resources\Families\FamilyResource;
use App\Filament\Concerns\WithoutPageHeading;
use Filament\Resources\Pages\ListRecords;

class ListFamilies extends ListRecords
{
    use WithoutPageHeading;

    protected static string $resource = FamilyResource::class;
}

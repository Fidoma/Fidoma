<?php

namespace App\Features\Products\Filament\Resources\Products\Pages;

use App\Features\Products\Filament\Resources\Products\ProductResource;
use App\Filament\Concerns\WithoutPageHeading;
use Filament\Resources\Pages\ListRecords;

class ListProducts extends ListRecords
{
    use WithoutPageHeading;

    protected static string $resource = ProductResource::class;
}

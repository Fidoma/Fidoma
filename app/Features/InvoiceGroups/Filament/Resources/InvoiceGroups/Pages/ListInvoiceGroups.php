<?php

namespace App\Features\InvoiceGroups\Filament\Resources\InvoiceGroups\Pages;

use App\Features\InvoiceGroups\Filament\Resources\InvoiceGroups\InvoiceGroupResource;
use App\Filament\Concerns\WithoutPageHeading;
use Filament\Resources\Pages\ListRecords;

class ListInvoiceGroups extends ListRecords
{
    use WithoutPageHeading;

    protected static string $resource = InvoiceGroupResource::class;
}

<?php

namespace App\Features\Contracts\Filament\Resources\ContractTemplates\Pages;

use App\Features\Contracts\Filament\Resources\ContractTemplates\ContractTemplateResource;
use App\Filament\Concerns\WithoutPageHeading;
use Filament\Resources\Pages\ListRecords;

class ListContractTemplates extends ListRecords
{
    use WithoutPageHeading;

    protected static string $resource = ContractTemplateResource::class;
}

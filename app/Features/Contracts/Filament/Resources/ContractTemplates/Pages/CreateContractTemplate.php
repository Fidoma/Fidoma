<?php

namespace App\Features\Contracts\Filament\Resources\ContractTemplates\Pages;

use App\Features\Contracts\Filament\Resources\ContractTemplates\ContractTemplateResource;
use Filament\Resources\Pages\CreateRecord;

class CreateContractTemplate extends CreateRecord
{
    protected static string $resource = ContractTemplateResource::class;
}

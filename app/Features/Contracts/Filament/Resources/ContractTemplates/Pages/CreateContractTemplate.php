<?php

namespace App\Features\Contracts\Filament\Resources\ContractTemplates\Pages;

use App\Features\Contracts\Filament\Resources\ContractTemplates\ContractTemplateResource;
use App\Features\Contracts\Filament\Resources\ContractTemplates\Pages\Concerns\ManagesContractTemplateFields;
use Filament\Resources\Pages\CreateRecord;

class CreateContractTemplate extends CreateRecord
{
    use ManagesContractTemplateFields;

    protected static string $resource = ContractTemplateResource::class;

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        return $this->extractCustomPlaceholders($data);
    }

    protected function afterCreate(): void
    {
        $this->syncCustomPlaceholders();
    }
}

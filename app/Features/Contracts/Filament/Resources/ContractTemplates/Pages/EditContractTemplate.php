<?php

namespace App\Features\Contracts\Filament\Resources\ContractTemplates\Pages;

use App\Features\Contracts\Filament\Resources\ContractTemplates\ContractTemplateResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditContractTemplate extends EditRecord
{
    protected static string $resource = ContractTemplateResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make()
                ->disabled(fn (): bool => $this->record->contracts()->exists())
                ->tooltip(fn (): ?string => $this->record->contracts()->exists()
                    ? 'Verwendete Vorlagen können nicht gelöscht werden.'
                    : null),
        ];
    }
}

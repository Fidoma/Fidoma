<?php

namespace App\Features\Contracts\Filament\Resources\Contracts\Pages;

use App\Features\Contracts\Filament\Resources\Contracts\ContractResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewContract extends ViewRecord
{
    protected static string $resource = ContractResource::class;

    public function getTitle(): string
    {
        return 'Vertrag '.$this->record->number;
    }

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make()->label('Vertrag bearbeiten'),
        ];
    }
}

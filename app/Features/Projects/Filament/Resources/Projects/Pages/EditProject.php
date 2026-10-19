<?php

namespace App\Features\Projects\Filament\Resources\Projects\Pages;

use App\Features\Projects\Filament\Resources\Projects\ProjectResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditProject extends EditRecord
{
    protected static string $resource = ProjectResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make()->disabled(fn (): bool => $this->record->invoices()->exists()),
        ];
    }
}

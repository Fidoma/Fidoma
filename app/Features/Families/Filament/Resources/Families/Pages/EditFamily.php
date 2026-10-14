<?php

namespace App\Features\Families\Filament\Resources\Families\Pages;

use App\Features\Families\Filament\Resources\Families\FamilyResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditFamily extends EditRecord
{
    protected static string $resource = FamilyResource::class;

    protected function getHeaderActions(): array
    {
        return [DeleteAction::make()];
    }
}

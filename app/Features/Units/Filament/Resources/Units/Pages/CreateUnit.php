<?php

namespace App\Features\Units\Filament\Resources\Units\Pages;

use App\Features\Units\Actions\CreateUnit as CreateUnitAction;
use App\Features\Units\Filament\Resources\Units\UnitResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreateUnit extends CreateRecord
{
    protected static string $resource = UnitResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        return app(CreateUnitAction::class)->handle($data);
    }
}

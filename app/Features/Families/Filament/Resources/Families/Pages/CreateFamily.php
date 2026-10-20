<?php

namespace App\Features\Families\Filament\Resources\Families\Pages;

use App\Features\Families\Actions\CreateFamily as CreateFamilyAction;
use App\Features\Families\Filament\Resources\Families\FamilyResource;
use App\Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreateFamily extends CreateRecord
{
    protected static string $resource = FamilyResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        return app(CreateFamilyAction::class)->handle($data);
    }
}

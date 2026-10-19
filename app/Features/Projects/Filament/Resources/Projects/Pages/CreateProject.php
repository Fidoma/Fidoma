<?php

namespace App\Features\Projects\Filament\Resources\Projects\Pages;

use App\Features\Projects\Actions\CreateProject as CreateProjectAction;
use App\Features\Projects\Filament\Resources\Projects\ProjectResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreateProject extends CreateRecord
{
    protected static string $resource = ProjectResource::class;

    /** @param array<string, mixed> $data */
    protected function handleRecordCreation(array $data): Model
    {
        return app(CreateProjectAction::class)->handle($data);
    }
}

<?php

namespace App\Features\Clients\Filament\Resources\Clients\Pages;

use App\Features\Clients\Actions\CreateClient as CreateClientAction;
use App\Features\Clients\Filament\Resources\Clients\ClientResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreateClient extends CreateRecord
{
    protected static string $resource = ClientResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        return app(CreateClientAction::class)->handle($data);
    }
}

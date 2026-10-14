<?php

namespace App\Features\Clients\Filament\Resources\Clients\Pages;

use App\Features\Clients\Filament\Resources\Clients\ClientResource;
use Filament\Resources\Pages\EditRecord;

class EditClient extends EditRecord
{
    protected static string $resource = ClientResource::class;
}

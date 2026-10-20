<?php

namespace App\Features\Contracts\Filament\Resources\Contracts\Pages;

use App\Features\Contracts\Actions\CreateContract as CreateContractAction;
use App\Features\Contracts\Filament\Resources\Contracts\ContractResource;
use App\Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreateContract extends CreateRecord
{
    protected static string $resource = ContractResource::class;

    /** @param array<string, mixed> $data */
    protected function handleRecordCreation(array $data): Model
    {
        return app(CreateContractAction::class)->handle($data, (int) auth()->id());
    }
}

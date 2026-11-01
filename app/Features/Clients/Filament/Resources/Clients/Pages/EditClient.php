<?php

namespace App\Features\Clients\Filament\Resources\Clients\Pages;

use App\Features\Clients\Filament\Resources\Clients\ClientResource;
use Filament\Resources\Pages\EditRecord;

class EditClient extends EditRecord
{
    protected static string $resource = ClientResource::class;

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeFill(array $data): array
    {
        if (blank($data['client_company'] ?? null)) {
            $data['client_company'] = trim(implode(' ', array_filter([
                $data['client_name'] ?? null,
                $data['client_surname'] ?? null,
            ])));
        }

        return $data;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeSave(array $data): array
    {
        $data['client_name'] = $data['client_company'];

        return $data;
    }
}

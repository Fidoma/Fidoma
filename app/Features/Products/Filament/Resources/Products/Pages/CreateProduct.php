<?php

namespace App\Features\Products\Filament\Resources\Products\Pages;

use App\Features\Products\Actions\CreateProduct as CreateProductAction;
use App\Features\Products\Filament\Resources\Products\ProductResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreateProduct extends CreateRecord
{
    protected static string $resource = ProductResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        return app(CreateProductAction::class)->handle($data);
    }
}

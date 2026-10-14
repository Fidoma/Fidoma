<?php

namespace App\Features\InvoiceGroups\Filament\Resources\InvoiceGroups\Pages;

use App\Features\InvoiceGroups\Actions\CreateInvoiceGroup as CreateInvoiceGroupAction;
use App\Features\InvoiceGroups\Filament\Resources\InvoiceGroups\InvoiceGroupResource;
use Filament\Resources\Pages\CreateRecord;
use Filament\Support\Enums\Width;
use Illuminate\Database\Eloquent\Model;

class CreateInvoiceGroup extends CreateRecord
{
    protected static string $resource = InvoiceGroupResource::class;

    protected Width|string|null $maxContentWidth = Width::Full;

    protected function handleRecordCreation(array $data): Model
    {
        return app(CreateInvoiceGroupAction::class)->handle($data);
    }
}

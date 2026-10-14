<?php

namespace App\Features\InvoiceGroups\Filament\Resources\InvoiceGroups\Pages;

use App\Features\InvoiceGroups\Filament\Resources\InvoiceGroups\InvoiceGroupResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Filament\Support\Enums\Width;

class EditInvoiceGroup extends EditRecord
{
    protected static string $resource = InvoiceGroupResource::class;

    protected Width|string|null $maxContentWidth = Width::Full;

    protected function getHeaderActions(): array
    {
        return [DeleteAction::make()];
    }
}

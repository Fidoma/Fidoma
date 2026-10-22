<?php

namespace App\Features\Invoices\Filament\Resources\Invoices\Pages;

use App\Features\Invoices\Actions\CreateInvoice as CreateInvoiceAction;
use App\Features\Invoices\Actions\RecalculateInvoice;
use App\Features\Invoices\Filament\Resources\Invoices\InvoiceResource;
use App\Filament\Concerns\WithoutPageHeading;
use App\Filament\Resources\Pages\CreateRecord;
use Filament\Actions\Action;
use Filament\Support\Enums\Width;
use Illuminate\Database\Eloquent\Model;

class CreateInvoice extends CreateRecord
{
    use WithoutPageHeading;

    protected static string $resource = InvoiceResource::class;

    protected Width|string|null $maxContentWidth = Width::Full;

    /** @return array<Action> */
    protected function getFormActions(): array
    {
        return [];
    }

    protected function handleRecordCreation(array $data): Model
    {
        return app(CreateInvoiceAction::class)->handle($data);
    }

    protected function afterCreate(): void
    {
        app(RecalculateInvoice::class)->handle($this->record);
    }
}

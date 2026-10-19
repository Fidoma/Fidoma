<?php

namespace App\Features\Clients\Filament\Resources\Clients\Pages;

use App\Features\Clients\Filament\Resources\Clients\ClientResource;
use App\Features\Invoices\Filament\Resources\Invoices\InvoiceResource;
use App\Features\Quotes\Filament\Resources\Quotes\QuoteResource;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;
use Filament\Support\Icons\Heroicon;

class ViewClient extends ViewRecord
{
    protected static string $resource = ClientResource::class;

    public function hasCombinedRelationManagerTabsWithContent(): bool
    {
        return true;
    }

    public function getContentTabLabel(): ?string
    {
        return __('clients::messages.tabs.details');
    }

    public function getTitle(): string
    {
        return $this->record->client_company ?: trim($this->record->client_name.' '.$this->record->client_surname);
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('createQuote')
                ->label(__('clients::messages.actions.create_quote'))
                ->icon(Heroicon::OutlinedDocumentText)
                ->url(QuoteResource::getUrl('create', ['client_id' => $this->record->getKey()])),
            Action::make('createInvoice')
                ->label(__('clients::messages.actions.create_invoice'))
                ->icon(Heroicon::OutlinedDocumentCurrencyEuro)
                ->url(InvoiceResource::getUrl('create', ['client_id' => $this->record->getKey()])),
            EditAction::make()
                ->label(__('clients::messages.actions.edit_client')),
        ];
    }
}

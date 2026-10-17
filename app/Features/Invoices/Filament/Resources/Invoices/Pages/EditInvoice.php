<?php

namespace App\Features\Invoices\Filament\Resources\Invoices\Pages;

use App\Features\Invoices\Actions\IssueInvoice;
use App\Features\Invoices\Actions\RecalculateInvoice;
use App\Features\Invoices\Enums\InvoiceStatus;
use App\Features\Invoices\Filament\Actions\InvoiceEmailAction;
use App\Features\Invoices\Filament\Resources\Invoices\InvoiceResource;
use App\Filament\Concerns\WithoutPageHeading;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Width;
use Filament\Support\Icons\Heroicon;

class EditInvoice extends EditRecord
{
    use WithoutPageHeading;

    protected static string $resource = InvoiceResource::class;

    protected Width|string|null $maxContentWidth = Width::Full;

    public function form(Schema $schema): Schema
    {
        return parent::form($schema);
    }

    protected function afterSave(): void
    {
        app(RecalculateInvoice::class)->handle($this->record);
    }

    protected function beforeSave(): void
    {
        abort_unless($this->isDraft(), 403, 'Nur Rechnungen im Status Entwurf können bearbeitet werden.');
    }

    public function issue(): void
    {
        abort_unless($this->isDraft(), 422, 'Nur Rechnungen im Status Entwurf können ausgestellt werden.');

        $this->save(shouldRedirect: false, shouldSendSavedNotification: false);
        $this->record->refresh();

        app(IssueInvoice::class)->handle($this->record);
        $this->record->refresh();

        Notification::make()
            ->success()
            ->title('Rechnung wurde ausgestellt.')
            ->body('Die Rechnungsnummer wurde vergeben und die Rechnung ist jetzt offen.')
            ->send();
    }

    /** @return array<Action> */
    protected function getFormActions(): array
    {
        return [
            ...($this->isDraft() ? parent::getFormActions() : []),
            InvoiceEmailAction::make()->outlined(),
            Action::make('createPdf')
                ->label('PDF erstellen')
                ->icon(Heroicon::OutlinedDocumentArrowDown)
                ->color('gray')
                ->outlined()
                ->url(fn (): string => route('invoices.pdf', ['invoice' => $this->record]))
                ->openUrlInNewTab(),
            Action::make('createXRechnung')
                ->label('XRechnung herunterladen')
                ->icon(Heroicon::OutlinedCodeBracketSquare)
                ->color('gray')
                ->outlined()
                ->url(fn (): string => route('invoices.xrechnung', ['invoice' => $this->record]))
                ->openUrlInNewTab(),
        ];
    }

    private function isDraft(): bool
    {
        return ! $this->record->is_archived
            && $this->record->invoice_status_id === InvoiceStatus::Draft;
    }
}

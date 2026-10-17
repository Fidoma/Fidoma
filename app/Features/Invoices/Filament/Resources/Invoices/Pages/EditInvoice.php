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
use Illuminate\Database\Eloquent\Model;

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
        abort_unless($this->isDraft() || $this->canEditInvoiceNumber(), 403, 'Diese Rechnung kann nicht bearbeitet werden.');
    }

    /** @param array<string, mixed> $data */
    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        if ($this->isDraft()) {
            return parent::handleRecordUpdate($record, $data);
        }

        $record->update(['invoice_number' => $data['invoice_number']]);

        return $record;
    }

    public function issue(): void
    {
        abort_unless($this->isDraft(), 422, 'Nur Rechnungen im Status Entwurf können ausgestellt werden.');

        $this->save(shouldRedirect: false, shouldSendSavedNotification: false);
        $this->record->refresh();

        $this->record = app(IssueInvoice::class)->handle($this->record);

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
            ...($this->isDraft() || $this->canEditInvoiceNumber() ? parent::getFormActions() : []),
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

    private function canEditInvoiceNumber(): bool
    {
        return ! $this->record->is_archived
            && $this->record->invoice_status_id === InvoiceStatus::Open;
    }
}

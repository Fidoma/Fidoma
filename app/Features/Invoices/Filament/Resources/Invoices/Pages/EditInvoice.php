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
        abort_unless($this->isDraft(), 403, __('invoices::messages.issue.edit_forbidden'));
    }

    public function issue(): void
    {
        abort_unless($this->isDraft(), 422, __('invoices::messages.issue.issue_forbidden'));

        $this->save(shouldRedirect: false, shouldSendSavedNotification: false);
        $this->record->refresh();

        app(IssueInvoice::class)->handle($this->record);
        $this->record->refresh();

        Notification::make()
            ->success()
            ->title(__('invoices::messages.issue.success'))
            ->body(__('invoices::messages.issue.success_body'))
            ->send();
    }

    /** @return array<Action> */
    protected function getFormActions(): array
    {
        return [
            ...($this->isDraft() ? parent::getFormActions() : []),
            InvoiceEmailAction::make()->outlined(),
            Action::make('createPdf')
                ->label(__('invoices::messages.actions.create_pdf'))
                ->icon(Heroicon::OutlinedDocumentArrowDown)
                ->color('gray')
                ->outlined()
                ->url(fn (): string => route('invoices.pdf', ['invoice' => $this->record]))
                ->openUrlInNewTab(),
            Action::make('createXRechnung')
                ->label(__('invoices::messages.actions.download_xrechnung'))
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

<?php

namespace App\Features\Invoices\Livewire;

use App\Features\BankTransactions\Models\BankTransaction;
use App\Features\Invoices\Actions\ArchiveInvoice;
use App\Features\Invoices\Actions\CreateInvoiceCounterpart;
use App\Features\Invoices\Actions\RecordInvoicePayment;
use App\Features\Invoices\Enums\InvoiceStatus;
use App\Features\Invoices\Enums\InvoiceType;
use App\Features\Invoices\Filament\Actions\InvoiceEmailAction;
use App\Features\Invoices\Filament\Resources\Invoices\InvoiceResource;
use App\Features\Invoices\Models\Invoice;
use App\Features\Invoices\Services\InvoiceDefaults;
use App\Features\PaymentMethods\Models\PaymentMethod;
use Filament\Actions\Action;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Schemas\Concerns\InteractsWithSchemas;
use Filament\Schemas\Contracts\HasSchemas;
use Filament\Support\Enums\Width;
use Filament\Support\Icons\Heroicon;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\On;
use Livewire\Component;

class InvoiceDetails extends Component implements HasActions, HasSchemas
{
    use InteractsWithActions;
    use InteractsWithSchemas;

    public Invoice $invoice;

    public function mount(int $invoiceId): void
    {
        $invoice = Invoice::query()->findOrFail($invoiceId);
        abort_unless(InvoiceResource::canView($invoice), 403);

        $this->invoice = $invoice;
        $this->refreshInvoice();
    }

    public function addPaymentAction(): Action
    {
        return Action::make('addPayment')
            ->label(__('invoices::messages.details.add_payment'))
            ->icon(Heroicon::OutlinedPlus)
            ->color('primary')
            ->visible(fn (): bool => $this->canAddPayment())
            ->modalHeading(__('invoices::messages.details.add_payment'))
            ->modalDescription(__('invoices::messages.details.payment_choice_description'))
            ->modalSubmitActionLabel(__('invoices::messages.details.continue'))
            ->schema([
                Radio::make('payment_type')
                    ->label(__('invoices::messages.details.payment_type'))
                    ->options(fn (): array => array_filter([
                        'manual' => __('invoices::messages.details.manual_payment'),
                        'bank' => $this->canAssignBankTransaction()
                            ? __('invoices::messages.details.bank_transaction')
                            : null,
                    ]))
                    ->descriptions([
                        'manual' => __('invoices::messages.details.manual_payment_description'),
                        'bank' => __('invoices::messages.details.bank_transaction_description'),
                    ])
                    ->default('manual')
                    ->required(),
            ])
            ->action(function (array $data): void {
                $this->mountAction($data['payment_type'] === 'bank' ? 'assignBankTransaction' : 'recordManualPayment');
            });
    }

    public function recordManualPaymentAction(): Action
    {
        return Action::make('recordManualPayment')
            ->label(__('invoices::messages.details.manual_payment'))
            ->icon(Heroicon::OutlinedBanknotes)
            ->record($this->invoice)
            ->visible(fn (): bool => $this->canAddPayment())
            ->modalHeading(__('invoices::messages.payment.heading', ['number' => $this->invoice->invoice_number]))
            ->modalSubmitActionLabel(__('invoices::messages.actions.save_payment'))
            ->schema([
                DatePicker::make('payment_date')
                    ->label(__('invoices::messages.fields.payment_date'))
                    ->default(now()->toDateString())
                    ->required(),
                Select::make('payment_method_id')
                    ->label(__('invoices::messages.fields.payment_method'))
                    ->options(fn () => PaymentMethod::query()
                        ->orderBy('payment_method_name')
                        ->pluck('payment_method_name', 'payment_method_id'))
                    ->default(fn (): ?int => $this->invoice->payment_method
                        ?: (app(InvoiceDefaults::class)->paymentMethodId() ?: null))
                    ->searchable()
                    ->preload()
                    ->required(),
                TextInput::make('payment_amount')
                    ->label(__('invoices::messages.fields.amount'))
                    ->numeric()
                    ->minValue(0.01)
                    ->maxValue(fn (): float => $this->balance())
                    ->default(fn (): float => $this->balance())
                    ->suffix('€')
                    ->required(),
                TextInput::make('payment_note')
                    ->label(__('invoices::messages.fields.note'))
                    ->default('')
                    ->maxLength(1000),
            ])
            ->action(function (array $data): void {
                app(RecordInvoicePayment::class)->handle($this->invoice, $data);
                $this->refreshInvoice();

                Notification::make()->success()
                    ->title(__('invoices::messages.payment.success'))
                    ->send();
            });
    }

    public function assignBankTransactionAction(): Action
    {
        return Action::make('assignBankTransaction')
            ->label(__('invoices::messages.details.bank_transaction'))
            ->icon(Heroicon::OutlinedLink)
            ->visible(fn (): bool => $this->canAssignBankTransaction())
            ->slideOver()
            ->modalWidth(Width::TwoExtraLarge)
            ->modalHeading(__('invoices::messages.details.assign_bank_transaction'))
            ->modalSubmitAction(false)
            ->modalCancelActionLabel(__('invoices::messages.details.close'))
            ->modalContent(fn () => view('invoices::assign-bank-transaction-modal', [
                'invoiceId' => $this->invoice->getKey(),
                'paymentId' => null,
            ]));
    }

    public function sendEmailAction(): Action
    {
        return InvoiceEmailAction::make()
            ->record($this->invoice)
            ->visible(fn (): bool => $this->isIssued() && InvoiceResource::canEdit($this->invoice));
    }

    public function archiveAction(): Action
    {
        return Action::make('archive')
            ->label(__('invoices::messages.actions.archive'))
            ->icon(Heroicon::OutlinedArchiveBox)
            ->color('gray')
            ->visible(fn (): bool => $this->isIssued()
                && ! $this->invoice->is_archived
                && InvoiceResource::canEdit($this->invoice))
            ->requiresConfirmation()
            ->modalHeading(__('invoices::messages.archive.heading'))
            ->modalDescription(__('invoices::messages.archive.description'))
            ->action(function (): void {
                app(ArchiveInvoice::class)->handle($this->invoice);
                $this->refreshInvoice();

                Notification::make()->success()->title(__('invoices::messages.archive.success'))->send();
            });
    }

    public function createCreditAction(): Action
    {
        return $this->counterpartAction('createCredit', InvoiceType::Credit);
    }

    public function cancelInvoiceAction(): Action
    {
        return $this->counterpartAction('cancelInvoice', InvoiceType::Cancellation);
    }

    #[On('invoice-mapping-created')]
    #[On('invoice-mapping-removed')]
    public function refreshInvoice(): void
    {
        $this->invoice->refresh()->load([
            'amount',
            'client',
            'payments' => fn ($query) => $query->orderByDesc('payment_date')->orderByDesc('payment_id'),
            'payments.paymentMethod',
            'payments.bankTransactionMappings.bankTransaction',
        ]);
    }

    public function paymentProgress(): int
    {
        $total = max(0, (float) ($this->invoice->amount?->invoice_total ?? 0));
        $paid = max(0, (float) ($this->invoice->amount?->invoice_paid ?? 0));

        return $total > 0 ? (int) min(100, round(($paid / $total) * 100)) : 0;
    }

    public function render(): View
    {
        return view('invoices::invoice-details');
    }

    private function counterpartAction(string $name, InvoiceType $type): Action
    {
        return Action::make($name)
            ->label($type === InvoiceType::Credit
                ? __('invoices::messages.actions.create_credit')
                : __('invoices::messages.actions.cancel_invoice'))
            ->icon($type === InvoiceType::Credit ? Heroicon::OutlinedDocumentMinus : Heroicon::OutlinedNoSymbol)
            ->color($type === InvoiceType::Credit ? 'gray' : 'danger')
            ->visible(fn (): bool => $this->invoice->invoice_type === InvoiceType::Invoice
                && $this->isIssued()
                && ! $this->invoice->is_archived
                && InvoiceResource::canCreate())
            ->requiresConfirmation()
            ->modalHeading($type === InvoiceType::Credit
                ? __('invoices::messages.actions.create_credit')
                : __('invoices::messages.actions.cancel_invoice'))
            ->modalDescription(__('invoices::messages.counterpart.description', [
                'type' => $type->label(),
                'number' => $this->invoice->invoice_number,
            ]))
            ->action(function () use ($type): void {
                $counterpart = app(CreateInvoiceCounterpart::class)->handle($this->invoice, $type);

                Notification::make()->success()
                    ->title(__('invoices::messages.counterpart.success', ['type' => $type->label()]))
                    ->send();

                $this->redirect(InvoiceResource::getUrl('edit', ['record' => $counterpart]));
            });
    }

    private function canAddPayment(): bool
    {
        return $this->invoice->invoice_type === InvoiceType::Invoice
            && ! $this->invoice->is_archived
            && $this->invoice->invoice_status_id !== InvoiceStatus::Paid
            && $this->balance() > 0
            && InvoiceResource::canEdit($this->invoice);
    }

    private function canAssignBankTransaction(): bool
    {
        return $this->canAddPayment()
            && $this->invoice->invoice_status_id !== InvoiceStatus::Draft
            && Gate::allows('viewAny', BankTransaction::class);
    }

    private function isIssued(): bool
    {
        return $this->invoice->invoice_status_id !== InvoiceStatus::Draft;
    }

    private function balance(): float
    {
        return max(0, round((float) ($this->invoice->amount?->invoice_balance ?? 0), 2));
    }
}

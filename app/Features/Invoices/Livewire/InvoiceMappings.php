<?php

namespace App\Features\Invoices\Livewire;

use App\Features\BankTransactions\Models\BankTransaction;
use App\Features\Invoices\Enums\InvoiceStatus;
use App\Features\Invoices\Enums\InvoiceType;
use App\Features\Invoices\Models\Invoice;
use App\Features\Payments\Models\Payment;
use Filament\Actions\Action;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Schemas\Concerns\InteractsWithSchemas;
use Filament\Schemas\Contracts\HasSchemas;
use Filament\Support\Enums\Width;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\On;
use Livewire\Component;

class InvoiceMappings extends Component implements HasActions, HasSchemas, HasTable
{
    use InteractsWithActions;
    use InteractsWithSchemas;
    use InteractsWithTable;

    public string $mappingView = 'invoices';

    public function mount(): void
    {
        Gate::authorize('viewAny', BankTransaction::class);
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(fn (): Builder => $this->isPaymentView()
                ? Payment::query()
                    ->whereHas('invoice', fn (Builder $query): Builder => $this->scopeMappingInvoices($query))
                    ->with(['invoice.client', 'paymentMethod'])
                    ->withCount('bankTransactionMappings')
                : $this->scopeMappingInvoices(Invoice::query())
                    ->with(['client', 'amount'])
                    ->withCount('bankTransactionMappings'))
            ->defaultSort(
                fn (): string => $this->isPaymentView() ? 'payment_date' : 'invoice_date_created',
                'desc',
            )
            ->columns([
                TextColumn::make('invoice_number')
                    ->label('Rechnungsnummer')
                    ->placeholder('Entwurf')
                    ->searchable()
                    ->sortable()
                    ->visible(fn (): bool => ! $this->isPaymentView()),
                TextColumn::make('client.client_name')
                    ->label('Kunde')
                    ->placeholder('Kein Kunde')
                    ->searchable()
                    ->sortable()
                    ->visible(fn (): bool => ! $this->isPaymentView()),
                TextColumn::make('invoice_date_created')
                    ->label('Rechnungsdatum')
                    ->date('d.m.Y')
                    ->sortable()
                    ->visible(fn (): bool => ! $this->isPaymentView()),
                TextColumn::make('invoice_date_due')
                    ->label('Fällig am')
                    ->date('d.m.Y')
                    ->sortable()
                    ->color(fn (Invoice $record): ?string => $record->isOverdue() ? 'danger' : null)
                    ->visible(fn (): bool => ! $this->isPaymentView()),
                TextColumn::make('amount.invoice_total')
                    ->label('Gesamt')
                    ->money('EUR', locale: 'de')
                    ->sortable()
                    ->visible(fn (): bool => ! $this->isPaymentView()),
                TextColumn::make('amount.invoice_balance')
                    ->label('Offen')
                    ->money('EUR', locale: 'de')
                    ->sortable()
                    ->visible(fn (): bool => ! $this->isPaymentView()),
                TextColumn::make('invoice_status_id')
                    ->label('Rechnungsstatus')
                    ->badge()
                    ->formatStateUsing(fn (InvoiceStatus $state): string => $state->label())
                    ->color(fn (InvoiceStatus $state): string => $state->color())
                    ->visible(fn (): bool => ! $this->isPaymentView()),
                TextColumn::make('bank_transaction_mappings_count')
                    ->label('Zuordnung')
                    ->badge()
                    ->formatStateUsing(fn (int $state): string => $state > 0 ? 'Zugeordnet' : 'Nicht zugeordnet')
                    ->color(fn (int $state): string => $state > 0 ? 'success' : 'gray')
                    ->visible(fn (): bool => ! $this->isPaymentView()),
                TextColumn::make('payment_date')
                    ->label('Zahlungsdatum')
                    ->date('d.m.Y')
                    ->sortable()
                    ->visible(fn (): bool => $this->isPaymentView()),
                TextColumn::make('invoice.client.client_name')
                    ->label('Kunde')
                    ->placeholder('Kein Kunde')
                    ->searchable()
                    ->sortable()
                    ->visible(fn (): bool => $this->isPaymentView()),
                TextColumn::make('invoice.invoice_number')
                    ->label('Rechnung')
                    ->placeholder('Entwurf')
                    ->searchable()
                    ->sortable()
                    ->visible(fn (): bool => $this->isPaymentView()),
                TextColumn::make('paymentMethod.payment_method_name')
                    ->label('Zahlungsart')
                    ->placeholder('–')
                    ->visible(fn (): bool => $this->isPaymentView()),
                TextColumn::make('payment_amount')
                    ->label('Betrag')
                    ->money('EUR', locale: 'de')
                    ->sortable()
                    ->visible(fn (): bool => $this->isPaymentView()),
                TextColumn::make('payment_note')
                    ->label('Notiz')
                    ->placeholder('–')
                    ->searchable()
                    ->wrap()
                    ->visible(fn (): bool => $this->isPaymentView()),
                TextColumn::make('payment_mapping_status')
                    ->label('Zuordnung')
                    ->state(fn (Payment $record): int => $record->bank_transaction_mappings_count)
                    ->badge()
                    ->formatStateUsing(fn (int $state): string => $state > 0 ? 'Zugeordnet' : 'Nicht zugeordnet')
                    ->color(fn (int $state): string => $state > 0 ? 'success' : 'gray')
                    ->visible(fn (): bool => $this->isPaymentView()),
            ])
            ->filters([
                SelectFilter::make('invoice_status_id')
                    ->label('Status')
                    ->options(InvoiceStatus::options())
                    ->visible(fn (): bool => ! $this->isPaymentView())
                    ->query(function (Builder $query, array $data): Builder {
                        $status = $data['value'] ?? null;

                        if (blank($status)) {
                            return $query;
                        }

                        return $this->isPaymentView()
                            ? $query->whereHas('invoice', fn (Builder $invoiceQuery): Builder => $invoiceQuery->where('invoice_status_id', $status))
                            : $query->where('invoice_status_id', $status);
                    }),
            ])
            ->recordAction('openAssignment')
            ->searchable()
            ->searchPlaceholder(fn (): string => $this->isPaymentView()
                ? 'Kunde, Rechnung oder Notiz durchsuchen'
                : 'Rechnungsnummer oder Kunde durchsuchen')
            ->paginated([10, 20, 50])
            ->defaultPaginationPageOption(20)
            ->emptyStateHeading(fn (): string => $this->isPaymentView()
                ? 'Keine Zahlungen gefunden'
                : 'Keine Rechnungen gefunden')
            ->emptyStateDescription(fn (): string => $this->isPaymentView()
                ? 'Es sind noch keine Zahlungen zur Zuordnung vorhanden.'
                : 'Es sind noch keine Einnahmen zur Zuordnung vorhanden.')
            ->emptyStateIcon(Heroicon::OutlinedDocumentCurrencyEuro);
    }

    public function assignAction(): Action
    {
        return Action::make('assign')
            ->label('Zuordnen')
            ->icon(Heroicon::OutlinedLink)
            ->color('gray')
            ->slideOver()
            ->modalWidth(Width::TwoExtraLarge)
            ->modalHeading('Zahlungseingang zuordnen')
            ->modalSubmitAction(false)
            ->modalCancelActionLabel('Schließen')
            ->modalContent(fn (array $arguments) => view('filament.pages.partials.assign-invoice-bank-transaction', [
                'invoiceId' => (int) $arguments['invoiceId'],
                'paymentId' => isset($arguments['paymentId']) ? (int) $arguments['paymentId'] : null,
            ]));
    }

    public function openAssignment(int|string $recordId): void
    {
        $payment = $this->isPaymentView()
            ? Payment::query()->find($recordId)
            : null;
        $invoiceId = $payment?->invoice_id ?? (int) $recordId;

        if ($invoiceId < 1) {
            return;
        }

        $this->mountAction('assign', [
            'invoiceId' => $invoiceId,
            'paymentId' => $payment?->getKey(),
        ]);
    }

    public function showMappingView(string $view): void
    {
        if (! in_array($view, ['invoices', 'payments'], true)) {
            return;
        }

        if ($view === 'payments') {
            $this->removeTableFilter('invoice_status_id');
        }

        $this->mappingView = $view;
        $this->resetTable();
    }

    private function isPaymentView(): bool
    {
        return $this->mappingView === 'payments';
    }

    private function scopeMappingInvoices(Builder $query): Builder
    {
        return $query
            ->where('invoice_type', InvoiceType::Invoice)
            ->where('is_archived', false);
    }

    #[On('invoice-mapping-created')]
    #[On('invoice-mapping-removed')]
    public function refreshMappings(): void
    {
        $this->resetTable();
    }

    public function render(): View
    {
        return view('livewire.features.invoices.invoice-mappings');
    }
}

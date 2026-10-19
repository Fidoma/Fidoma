<?php

namespace App\Features\Invoices\Livewire;

use App\Features\BankTransactions\Models\BankTransaction;
use App\Features\Invoices\Enums\InvoiceStatus;
use App\Features\Invoices\Enums\InvoiceType;
use App\Features\Invoices\Models\Invoice;
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

    public function mount(): void
    {
        Gate::authorize('viewAny', BankTransaction::class);
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(fn (): Builder => Invoice::query()
                ->where('invoice_type', InvoiceType::Invoice)
                ->where('is_archived', false)
                ->with(['client', 'amount'])
                ->withCount('bankTransactionMappings'))
            ->defaultSort('invoice_date_created', 'desc')
            ->columns([
                TextColumn::make('invoice_number')
                    ->label('Rechnungsnummer')
                    ->placeholder('Entwurf')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('client.client_name')
                    ->label('Kunde')
                    ->placeholder('Kein Kunde')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('invoice_date_created')
                    ->label('Rechnungsdatum')
                    ->date('d.m.Y')
                    ->sortable(),
                TextColumn::make('invoice_date_due')
                    ->label('Fällig am')
                    ->date('d.m.Y')
                    ->sortable()
                    ->color(fn (Invoice $record): ?string => $record->isOverdue() ? 'danger' : null),
                TextColumn::make('amount.invoice_total')
                    ->label('Gesamt')
                    ->money('EUR', locale: 'de')
                    ->sortable(),
                TextColumn::make('amount.invoice_balance')
                    ->label('Offen')
                    ->money('EUR', locale: 'de')
                    ->sortable(),
                TextColumn::make('invoice_status_id')
                    ->label('Rechnungsstatus')
                    ->badge()
                    ->formatStateUsing(fn (InvoiceStatus $state): string => $state->label())
                    ->color(fn (InvoiceStatus $state): string => $state->color()),
                TextColumn::make('bank_transaction_mappings_count')
                    ->label('Zuordnung')
                    ->badge()
                    ->formatStateUsing(fn (int $state): string => $state > 0 ? 'Zugeordnet' : 'Nicht zugeordnet')
                    ->color(fn (int $state): string => $state > 0 ? 'success' : 'gray'),
            ])
            ->filters([
                SelectFilter::make('invoice_status_id')
                    ->label('Status')
                    ->options(InvoiceStatus::options()),
            ])
            ->recordAction('openAssignment')
            ->searchable()
            ->searchPlaceholder('Rechnungsnummer oder Kunde durchsuchen')
            ->paginated([10, 20, 50])
            ->defaultPaginationPageOption(20)
            ->emptyStateHeading('Keine Rechnungen gefunden')
            ->emptyStateDescription('Es sind noch keine Einnahmen zur Zuordnung vorhanden.')
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
            ]));
    }

    public function openAssignment(int|string $invoiceId): void
    {
        $invoiceId = (int) $invoiceId;

        if ($invoiceId < 1) {
            return;
        }

        $this->mountAction('assign', ['invoiceId' => $invoiceId]);
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

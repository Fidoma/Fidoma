<?php

namespace App\Features\Invoices\Livewire;

use App\Features\BankTransactions\Models\BankTransaction;
use App\Features\Invoices\Actions\AssignInvoiceBankTransactions;
use App\Features\Invoices\Models\Invoice;
use App\Features\Invoices\Queries\InvoiceBankTransactionCandidateQuery;
use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\TextEntry;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Concerns\InteractsWithSchemas;
use Filament\Schemas\Contracts\HasSchemas;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Alignment;
use Filament\Support\Enums\FontWeight;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\Layout\Split;
use Filament\Tables\Columns\Layout\Stack;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Table;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\On;
use Livewire\Component;
use Throwable;

class AssignInvoiceBankTransaction extends Component implements HasActions, HasSchemas, HasTable
{
    use InteractsWithActions;
    use InteractsWithSchemas;
    use InteractsWithTable;

    public Invoice $invoice;

    public function mount(int $invoiceId): void
    {
        Gate::authorize('viewAny', BankTransaction::class);
        $this->invoice = Invoice::query()->with(['client', 'amount'])->findOrFail($invoiceId);
    }

    public function invoiceInfolist(Schema $schema): Schema
    {
        return $schema
            ->record($this->invoice)
            ->components([
                Section::make('Rechnungsinformationen')
                    ->description('Ausgewählte Zahlungseingänge werden als Zahlungen der Rechnung verbucht.')
                    ->columns(2)
                    ->compact()
                    ->schema([
                        TextEntry::make('invoice_number')
                            ->label('Rechnungsnummer')
                            ->placeholder('Entwurf')
                            ->weight(FontWeight::Normal)
                            ->suffixAction(Action::make('filterInvoiceNumber')
                                ->label('Rechnungsnummer als Filter hinzufügen')
                                ->icon(Heroicon::OutlinedMagnifyingGlass)
                                ->color('gray')
                                ->action(fn () => $this->applyTableSearch((string) $this->invoice->invoice_number))),
                        TextEntry::make('client.client_name')
                            ->label('Kunde')
                            ->placeholder('–')
                            ->weight(FontWeight::Normal)
                            ->suffixAction(Action::make('filterClient')
                                ->label('Kunde als Filter hinzufügen')
                                ->icon(Heroicon::OutlinedMagnifyingGlass)
                                ->color('gray')
                                ->action(fn () => $this->applyTableSearch((string) $this->invoice->client?->client_name))),
                        TextEntry::make('invoice_date_created')
                            ->label('Rechnungsdatum')
                            ->date('d.m.Y')
                            ->weight(FontWeight::Normal),
                        TextEntry::make('invoice_date_due')
                            ->label('Fällig am')
                            ->date('d.m.Y')
                            ->weight(FontWeight::Normal),
                        TextEntry::make('amount.invoice_total')
                            ->label('Rechnungsbetrag')
                            ->money('EUR', locale: 'de')
                            ->weight(FontWeight::Normal),
                        TextEntry::make('amount.invoice_balance')
                            ->label('Offener Betrag')
                            ->money('EUR', locale: 'de')
                            ->weight(FontWeight::Normal)
                            ->suffixAction(Action::make('filterAmount')
                                ->label('Offenen Betrag als Filter hinzufügen')
                                ->icon(Heroicon::OutlinedMagnifyingGlass)
                                ->color('gray')
                                ->action(fn () => $this->applyTableSearch((string) $this->invoice->amount?->invoice_balance))),
                    ]),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->heading('Banktransaktionen')
            ->description('Nur positive, noch nicht zugeordnete Zahlungseingänge werden angeboten.')
            ->query(fn (): Builder => app(InvoiceBankTransactionCandidateQuery::class)
                ->query($this->invoice)
                ->doesntHave('mappings'))
            ->columns([
                Split::make([
                    Stack::make([
                        TextColumn::make('counterparty_name')
                            ->label('Gegenpartei')
                            ->placeholder('Unbekannte Gegenpartei')
                            ->weight('medium')
                            ->searchable([
                                'counterparty_name',
                                'counterparty_iban',
                                'purpose',
                                'booking_text',
                                'transaction_reference',
                                'bank_transaction_id',
                                'bank_reference',
                                'end_to_end_id',
                            ])
                            ->wrap(),
                        TextColumn::make('purpose')
                            ->label('Verwendungszweck')
                            ->placeholder('Kein Verwendungszweck')
                            ->color('gray')
                            ->limit(55)
                            ->wrap(),
                    ])->space(1),
                    Stack::make([
                        TextColumn::make('amount')
                            ->label('Betrag')
                            ->money(fn (BankTransaction $record): string => $record->currency, locale: 'de')
                            ->color('success')
                            ->grow(false)
                            ->searchable(query: function (Builder $query, string $search): Builder {
                                $amount = str_replace(',', '.', trim($search));

                                return is_numeric($amount)
                                    ? $query->whereRaw('amount = ?', [(float) $amount])
                                    : $query->whereRaw('1 = 0');
                            }),
                        TextColumn::make('booking_date')
                            ->label('Buchungsdatum')
                            ->date('d.m.Y')
                            ->color('gray')
                            ->grow(false),
                        TextColumn::make('match_score')
                            ->label('Match')
                            ->state(fn (BankTransaction $record): ?int => (int) $record->match_score > 0
                                ? (int) $record->match_score
                                : null)
                            ->formatStateUsing(fn (int|string $state): string => 'Match '.(int) $state.' %')
                            ->badge()
                            ->color('info')
                            ->grow(false),
                    ])->alignment(Alignment::End)->space(1)->grow(false),
                ]),
            ])
            ->filters([
                Filter::make('counterparty_name')
                    ->label('Gegenpartei')
                    ->schema([TextInput::make('value')->label('Gegenpartei')])
                    ->query(fn (Builder $query, array $data): Builder => $query->when(
                        filled($data['value'] ?? null),
                        fn (Builder $query): Builder => $query->where('counterparty_name', 'like', '%'.trim((string) $data['value']).'%'),
                    )),
            ])
            ->toolbarActions([
                BulkAction::make('assignSelected')
                    ->label('Zuordnen')
                    ->icon(Heroicon::OutlinedLink)
                    ->action(fn (Collection $records) => $this->assignSelected($records)),
            ])
            ->selectCurrentPageOnly()
            ->searchPlaceholder('Name, IBAN, Zweck, Referenz oder Betrag')
            ->paginated([5, 10, 25])
            ->defaultPaginationPageOption(5)
            ->emptyStateHeading('Keine passenden Zahlungseingänge gefunden')
            ->emptyStateDescription('Passen Sie die Suche an oder prüfen Sie bereits verknüpfte Transaktionen.')
            ->emptyStateIcon(Heroicon::OutlinedBuildingLibrary);
    }

    public function applyTableSearch(string $search): void
    {
        $search = trim($search);

        if ($search === '') {
            return;
        }

        $this->tableSearch = $search;
        $this->updatedTableSearch();
    }

    /** @param Collection<int, BankTransaction> $records */
    public function assignSelected(Collection $records): void
    {
        try {
            app(AssignInvoiceBankTransactions::class)->handleMany(
                $this->invoice->getKey(),
                $records->mapWithKeys(fn (BankTransaction $record): array => [
                    $record->getKey() => $record->assignmentFingerprint(),
                ])->all(),
            );
        } catch (ValidationException $exception) {
            Notification::make()->danger()
                ->title('Die Zuordnung konnte nicht gespeichert werden.')
                ->body(collect($exception->errors())->flatten()->first())
                ->send();

            return;
        } catch (Throwable $exception) {
            report($exception);
            Notification::make()->danger()
                ->title('Die Zuordnung konnte nicht gespeichert werden.')
                ->body('Bitte versuchen Sie es erneut.')
                ->send();

            return;
        }

        $this->invoice->refresh()->load(['client', 'amount']);
        $this->deselectAllTableRecords();
        $this->resetTable();

        Notification::make()->success()
            ->title($records->count() === 1
                ? 'Zahlungseingang wurde zugeordnet.'
                : $records->count().' Zahlungseingänge wurden zugeordnet.')
            ->send();

        $this->dispatch('invoice-mapping-created');
    }

    #[On('invoice-mapping-removed')]
    public function refreshAssignableTransactions(): void
    {
        $this->invoice->refresh()->load(['client', 'amount']);
        $this->deselectAllTableRecords();
        $this->resetTable();
    }

    public function render(): View
    {
        return view('livewire.features.invoices.assign-invoice-bank-transaction');
    }
}

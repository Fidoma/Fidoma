<?php

namespace App\Features\Documents\Livewire;

use App\Features\BankTransactions\Models\BankTransaction;
use App\Features\Documents\Actions\AssignPaperlessDocument;
use App\Features\Documents\Data\PaperlessDocument;
use App\Features\Documents\Queries\BankTransactionCandidateQuery;
use App\Features\Documents\Services\PaperlessClient;
use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
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
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\Indicator;
use Filament\Tables\Table;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Js;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\On;
use Livewire\Component;
use Throwable;

class AssignBankTransaction extends Component implements HasActions, HasSchemas, HasTable
{
    use InteractsWithActions;
    use InteractsWithSchemas;
    use InteractsWithTable;

    /** @var array<string, int|float|string|null> */
    public array $document = [];

    public bool $showAssigned = false;

    public function mount(int $documentId, PaperlessClient $paperless): void
    {
        Gate::authorize('viewAny', BankTransaction::class);
        $this->document = $paperless->document($documentId)->toArray();
    }

    public function documentInfolist(Schema $schema): Schema
    {
        return $schema
            ->state($this->document)
            ->components([
                Section::make('Dokumentinformationen')
                    ->description('Die bestehende Zuordnung speichert keine Teilbeträge. Deshalb werden keine rechnerischen Restbeträge ausgewiesen.')
                    ->extraAttributes(['class' => 'document-information'])
                    ->columns(2)
                    ->compact()
                    ->schema([
                        TextEntry::make('title')
                            ->label('Dokumentname')
                            ->placeholder('Dokument #'.($this->document['id'] ?? ''))
                            ->weight(FontWeight::Normal),
                        TextEntry::make('id')
                            ->label('Paperless ID')
                            ->weight(FontWeight::Normal)
                            ->suffixAction(
                                Action::make('openPaperlessDocument')
                                    ->label('Dokument in Paperless öffnen')
                                    ->icon(Heroicon::OutlinedArrowTopRightOnSquare)
                                    ->color('gray')
                                    ->url(fn (): ?string => $this->paperlessDocumentUrl())
                                    ->extraAttributes([
                                        'target' => PaperlessClient::DOCUMENT_PREVIEW_WINDOW,
                                        'x-on:click.prevent' => "window.paperlessDocumentPreview = window.open(\$el.href, '".PaperlessClient::DOCUMENT_PREVIEW_WINDOW."'); if (window.paperlessDocumentPreview) { window.paperlessDocumentPreview.focus(); }",
                                    ]),
                            ),
                        TextEntry::make('supplier')
                            ->label('Lieferant')
                            ->placeholder('–')
                            ->weight(FontWeight::Normal)
                            ->suffixAction(function (?string $state): ?Action {
                                if (blank($state)) {
                                    return null;
                                }

                                return Action::make('filterSupplier')
                                    ->label('Lieferant als Filter hinzufügen')
                                    ->icon(Heroicon::OutlinedMagnifyingGlass)
                                    ->color('gray')
                                    ->action(fn () => $this->applySupplierFilter());
                            }),
                        TextEntry::make('invoiceNumber')
                            ->label('Rechnungsnummer')
                            ->placeholder('–')
                            ->weight(FontWeight::Normal)
                            ->suffixAction(function (?string $state): ?Action {
                                if (blank($state)) {
                                    return null;
                                }

                                return Action::make('filterInvoiceNumber')
                                    ->label('Rechnungsnummer als Filter hinzufügen')
                                    ->icon(Heroicon::OutlinedMagnifyingGlass)
                                    ->color('gray')
                                    ->action(fn () => $this->applyInvoiceNumberFilter());
                            }),
                        TextEntry::make('documentDate')
                            ->label('Rechnungsdatum')
                            ->date('d.m.Y')
                            ->weight(FontWeight::Normal)
                            ->placeholder('–'),
                        TextEntry::make('amount')
                            ->label('Rechnungsbetrag')
                            ->money((string) ($this->document['currency'] ?? 'EUR'), locale: 'de')
                            ->placeholder('–')
                            ->weight(FontWeight::Normal)
                            ->suffixAction(function (mixed $state): ?Action {
                                if (! is_numeric($state)) {
                                    return null;
                                }

                                return Action::make('filterAmount')
                                    ->label('Rechnungsbetrag als Filter hinzufügen')
                                    ->icon(Heroicon::OutlinedMagnifyingGlass)
                                    ->color('gray')
                                    ->action(fn () => $this->applyAmountFilter());
                            }),
                    ]),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->identifier('documentBankTransactions')
            ->heading(__('documents::messages.assignment.transactions_heading'))
            ->description(__('documents::messages.assignment.transactions_description'))
            ->query(fn (): Builder => app(BankTransactionCandidateQuery::class)
                ->query($this->paperlessDocument())
                ->when(
                    $this->showAssigned,
                    fn (Builder $query): Builder => $query->has('mappings'),
                    fn (Builder $query): Builder => $query->doesntHave('mappings'),
                ))
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
                            ->color('danger')
                            ->grow(false)
                            ->searchable(query: function (Builder $query, string $search): Builder {
                                $amount = str_replace(',', '.', trim($search));

                                return is_numeric($amount)
                                    ? $query->whereRaw('ABS(amount) = ?', [(float) $amount])
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
                        TextColumn::make('mappings_count')
                            ->label('Status')
                            ->state(fn (BankTransaction $record): int => $record->mapped_to_current_document
                                ? -1
                                : (int) $record->mappings_count)
                            ->badge()
                            ->formatStateUsing(fn (int|string $state): string => match (true) {
                                (int) $state === -1 => 'Diesem Dokument zugeordnet',
                                (int) $state > 0 => (int) $state.' Zuordnung(en)',
                                default => 'Nicht zugeordnet',
                            })
                            ->color(fn (int|string $state): string => (int) $state !== 0 ? 'success' : 'gray')
                            ->grow(false),
                    ])
                        ->alignment(Alignment::End)
                        ->space(1)
                        ->grow(false),
                ]),
            ])
            ->filters([
                Filter::make('supplier')
                    ->schema([
                        TextInput::make('value'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        $supplier = trim((string) ($data['value'] ?? ''));

                        return $supplier === ''
                            ? $query
                            : $query->where('counterparty_name', 'like', "%{$supplier}%");
                    })
                    ->indicateUsing(fn (array $data): array => filled($data['value'] ?? null)
                        ? [Indicator::make('Lieferant: '.trim((string) $data['value']))->removeField('value')]
                        : []),
                Filter::make('invoice_number')
                    ->schema([
                        TextInput::make('value'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        $invoiceNumber = trim((string) ($data['value'] ?? ''));

                        if ($invoiceNumber === '') {
                            return $query;
                        }

                        return $query->where(function (Builder $query) use ($invoiceNumber): void {
                            $search = "%{$invoiceNumber}%";

                            $query
                                ->where('purpose', 'like', $search)
                                ->orWhere('booking_text', 'like', $search)
                                ->orWhere('transaction_reference', 'like', $search)
                                ->orWhere('bank_transaction_id', 'like', $search)
                                ->orWhere('bank_reference', 'like', $search)
                                ->orWhere('end_to_end_id', 'like', $search);
                        });
                    })
                    ->indicateUsing(fn (array $data): array => filled($data['value'] ?? null)
                        ? [Indicator::make('Rechnungsnummer: '.trim((string) $data['value']))->removeField('value')]
                        : []),
                Filter::make('amount')
                    ->schema([
                        TextInput::make('value')
                            ->numeric(),
                    ])
                    ->query(fn (Builder $query, array $data): Builder => filled($data['value'] ?? null)
                        ? $query->whereRaw('ABS(amount) = ?', [(float) $data['value']])
                        : $query)
                    ->indicateUsing(fn (array $data): array => filled($data['value'] ?? null)
                        ? [Indicator::make('Betrag: '.number_format((float) $data['value'], 2, ',', '').' '.($this->document['currency'] ?? 'EUR'))->removeField('value')]
                        : []),
            ], layout: FiltersLayout::Hidden)
            ->deferFilters(false)
            ->toolbarActions([
                BulkAction::make('assignSelected')
                    ->label('Link')
                    ->icon(Heroicon::OutlinedLink)
                    ->requiresConfirmation()
                    ->modalHeading('Ausgewählte Banktransaktionen zuordnen')
                    ->modalDescription(fn (Collection $records): string => $records->count().' '.($records->count() === 1
                        ? 'Banktransaktion wird dem Dokument zugeordnet.'
                        : 'Banktransaktionen werden dem Dokument zugeordnet.'))
                    ->action(fn (Collection $records) => $this->assignSelected($records)),
            ])
            ->checkIfRecordIsSelectableUsing(fn (BankTransaction $record): bool => ! $record->mapped_to_current_document)
            ->selectCurrentPageOnly()
            ->searchPlaceholder('Name, IBAN, Zweck, Referenz oder Betrag')
            ->paginated([5, 10, 25])
            ->defaultPaginationPageOption(5)
            ->emptyStateHeading(__('documents::messages.assignment.empty_heading'))
            ->emptyStateDescription(__('documents::messages.assignment.empty_description'))
            ->emptyStateIcon(Heroicon::OutlinedBuildingLibrary);
    }

    public function assignmentStatusToggle(Schema $schema): Schema
    {
        return $schema
            ->components([
                Toggle::make('showAssigned')
                    ->label(fn (?bool $state): string => $state
                        ? __('documents::messages.assignment.assigned')
                        : __('documents::messages.assignment.unassigned'))
                    ->hiddenLabel()
                    ->extraAttributes(function (?bool $state): array {
                        $status = $state
                            ? __('documents::messages.assignment.assigned')
                            : __('documents::messages.assignment.unassigned');

                        return [
                            'aria-label' => $status,
                            'x-tooltip' => '{ content: '.Js::from($status)->toHtml().', theme: $store.theme }',
                        ];
                    })
                    ->live(),
            ]);
    }

    public function updatedShowAssigned(): void
    {
        $this->deselectAllTableRecords();
        $this->resetPage();
    }

    public function applyInvoiceNumberFilter(): void
    {
        $this->applyTableFilter('invoice_number', (string) ($this->document['invoiceNumber'] ?? ''));
    }

    public function applySupplierFilter(): void
    {
        $this->applyTableFilter('supplier', (string) ($this->document['supplier'] ?? ''));
    }

    public function applyAmountFilter(): void
    {
        $amount = $this->document['amount'] ?? null;

        if (! is_numeric($amount)) {
            return;
        }

        $this->applyTableFilter('amount', abs((float) $amount));
    }

    /** @param Collection<int, BankTransaction> $records */
    public function assignSelected(Collection $records): void
    {
        try {
            app(AssignPaperlessDocument::class)->handleMany(
                (int) $this->document['id'],
                $records->mapWithKeys(fn (BankTransaction $record): array => [
                    $record->getKey() => $record->assignmentFingerprint(),
                ])->all(),
            );
        } catch (ValidationException $exception) {
            Notification::make()
                ->danger()
                ->title('Die Zuordnung konnte nicht gespeichert werden.')
                ->body(collect($exception->errors())->flatten()->first())
                ->send();

            return;
        } catch (Throwable $exception) {
            report($exception);
            Notification::make()
                ->danger()
                ->title('Die Zuordnung konnte nicht gespeichert werden.')
                ->body('Bitte versuchen Sie es erneut.')
                ->send();

            return;
        }

        $assignedCount = $records->count();
        $this->deselectAllTableRecords();
        $this->resetTable();

        Notification::make()
            ->success()
            ->title($assignedCount === 1
                ? 'Banktransaktion wurde zugeordnet.'
                : $assignedCount.' Banktransaktionen wurden zugeordnet.')
            ->send();

        $this->dispatch('paperless-mapping-created');
    }

    #[On('paperless-mapping-removed')]
    public function refreshAssignableTransactions(): void
    {
        $this->deselectAllTableRecords();
        $this->resetTable();
    }

    public function render(): View
    {
        return view('livewire.features.documents.assign-bank-transaction');
    }

    private function paperlessDocument(): PaperlessDocument
    {
        return new PaperlessDocument(...$this->document);
    }

    private function paperlessDocumentUrl(): ?string
    {
        return app(PaperlessClient::class)->documentDetailsUrl((int) ($this->document['id'] ?? 0));
    }

    private function applyTableFilter(string $filter, float|string $value): void
    {
        $value = is_string($value) ? trim($value) : $value;

        if ($value === '') {
            return;
        }

        $this->tableFilters = array_replace_recursive(
            $this->tableFilters ?? [],
            [$filter => ['value' => $value]],
        );
        $this->updatedTableFilters();
    }
}

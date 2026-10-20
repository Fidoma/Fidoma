<?php

namespace App\Features\Documents\Filament\Pages;

use App\Features\BankTransactions\Models\BankTransaction;
use App\Features\BankTransactions\Models\BankTransactionMapping;
use App\Features\Documents\Data\PaperlessDocument;
use App\Features\Documents\Services\PaperlessClient;
use App\Filament\Concerns\HasConfigurableNavigation;
use App\Filament\Concerns\WithoutPageHeading;
use BackedEnum;
use Carbon\CarbonImmutable;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Pages\Page;
use Filament\Support\Enums\Width;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\Indicator;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\LengthAwarePaginator as Paginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Js;
use Livewire\Attributes\On;
use Throwable;
use UnitEnum;

class Documents extends Page implements HasTable
{
    use HasConfigurableNavigation, InteractsWithTable, WithoutPageHeading;

    protected static function navigationModule(): string
    {
        return 'assignments';
    }

    protected static ?string $title = 'Zuordnungen';

    protected static ?string $navigationLabel = 'Zuordnungen';

    protected static string|UnitEnum|null $navigationGroup = 'Finanzen';

    protected static ?int $navigationSort = 2;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedLink;

    protected string $view = 'documents::documents';

    public ?string $paperlessError = null;

    public string $activeTab = 'expenses';

    public static function getNavigationLabel(): string
    {
        return __('documents::messages.navigation');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('documents::messages.navigation_group');
    }

    public function getTitle(): string
    {
        return __('documents::messages.navigation');
    }

    /** @var list<array{id: int, title: string, supplier: ?string, invoiceNumber: ?string, documentDate: ?string, amount: ?float, currency: string, tags: array<int, string>, mappedCount: int}> */
    public array $paperlessDocumentRecords = [];

    public function mount(): void
    {
        $this->loadCachedPaperlessDocuments();
    }

    public static function canAccess(): bool
    {
        return auth()->user()?->can('viewAny', BankTransaction::class) ?? false;
    }

    public function table(Table $table): Table
    {
        return $table
            ->records(fn (?string $search, array $filters, int $page, int $recordsPerPage, ?string $sortColumn, ?string $sortDirection): LengthAwarePaginator => $this->paperlessDocuments(
                $search,
                $filters,
                $page,
                $recordsPerPage,
                $sortColumn,
                $sortDirection,
            ))
            ->columns([
                TextColumn::make('title')
                    ->label('Dokument')
                    ->weight('medium')
                    ->sortable()
                    ->wrap(),
                TextColumn::make('supplier')
                    ->label('Lieferant')
                    ->sortable()
                    ->placeholder('Kein Lieferant'),
                TextColumn::make('invoiceNumber')
                    ->label('Rechnungsnummer')
                    ->sortable()
                    ->placeholder('–')
                    ->wrap(),
                TextColumn::make('documentDate')
                    ->label('Rechnungsdatum')
                    ->date('d.m.Y')
                    ->sortable()
                    ->placeholder('Kein Datum'),
                TextColumn::make('amount')
                    ->label('Betrag')
                    ->money(fn (array $record): string => $record['currency'], locale: 'de')
                    ->sortable()
                    ->placeholder('–'),
                TextColumn::make('mappedCount')
                    ->label('Status')
                    ->badge()
                    ->sortable()
                    ->formatStateUsing(fn (int $state): string => $state === 0
                        ? 'Nicht zugeordnet'
                        : $state.' '.($state === 1 ? 'Zuordnung' : 'Zuordnungen'))
                    ->color(fn (int $state): string => $state > 0 ? 'success' : 'gray'),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('Status')
                    ->options([
                        'unassigned' => 'Nicht zugeordnet',
                        'assigned' => 'Zugeordnet',
                    ])
                    ->placeholder('Alle'),
                SelectFilter::make('paperless_tag')
                    ->label('Paperless-Tag')
                    ->options(fn (): array => $this->paperlessTagOptions())
                    ->searchable()
                    ->placeholder('Alle'),
                Filter::make('document_date')
                    ->label('Ausstellungsdatum')
                    ->schema([
                        DatePicker::make('from')
                            ->label('Von')
                            ->native(false),
                        DatePicker::make('until')
                            ->label('Bis')
                            ->native(false),
                    ])
                    ->columns(2)
                    ->indicateUsing(function (array $data): array {
                        $indicators = [];

                        if (filled($data['from'] ?? null)) {
                            $indicators[] = Indicator::make(
                                'Ausstellungsdatum von '.CarbonImmutable::parse($data['from'])->format('d.m.Y'),
                            )->removeField('from');
                        }

                        if (filled($data['until'] ?? null)) {
                            $indicators[] = Indicator::make(
                                'Ausstellungsdatum bis '.CarbonImmutable::parse($data['until'])->format('d.m.Y'),
                            )->removeField('until');
                        }

                        return $indicators;
                    }),
            ])
            ->persistFiltersInSession()
            ->toolbarActions([
                Action::make('refreshPaperlessDocuments')
                    ->label('Aktualisieren')
                    ->icon(Heroicon::OutlinedArrowPath)
                    ->color('gray')
                    ->action(fn (): null => $this->refreshPaperlessDocuments()),
            ])
            ->recordAction('openAssignment')
            ->searchable()
            ->searchPlaceholder('Dokumente in Paperless durchsuchen')
            ->defaultSort('documentDate', 'desc')
            ->paginated([10, 20, 50])
            ->defaultPaginationPageOption(20)
            ->emptyStateHeading('Keine Dokumente gefunden')
            ->emptyStateDescription(fn (): ?string => $this->paperlessError)
            ->emptyStateIcon(Heroicon::OutlinedDocumentText);
    }

    public function showTab(string $tab): void
    {
        if (! in_array($tab, ['income', 'expenses'], true)) {
            return;
        }

        $this->activeTab = $tab;
    }

    #[On('paperless-mapping-created')]
    #[On('paperless-mapping-removed')]
    public function refreshMappings(): void
    {
        $this->addMappingCounts();
        $this->flushCachedTableRecords();
    }

    public function refreshPaperlessDocuments(): null
    {
        $this->fetchPaperlessDocuments();
        $this->flushCachedTableRecords();
        $this->resetPage($this->getTablePaginationPageName());

        return null;
    }

    public function assignAction(): Action
    {
        return Action::make('assign')
            ->label('Zuordnen')
            ->icon(Heroicon::OutlinedLink)
            ->color('gray')
            ->slideOver()
            ->modalWidth(Width::TwoExtraLarge)
            ->modalHeading('Banktransaktion zuordnen')
            ->modalSubmitAction(false)
            ->modalCancelActionLabel('Schließen')
            ->modalContent(fn (array $arguments) => view('documents::assign-bank-transaction-modal', [
                'documentId' => (int) $arguments['documentId'],
            ]));
    }

    public function openAssignment(int|string $documentId): void
    {
        $documentId = (int) $documentId;

        if ($documentId < 1) {
            return;
        }

        if ($paperlessUrl = app(PaperlessClient::class)->documentDetailsUrl($documentId)) {
            $this->js('if (window.paperlessDocumentPreview && ! window.paperlessDocumentPreview.closed) {'
                .' window.paperlessDocumentPreview.location.href = '.Js::from($paperlessUrl).';'
                .'}');
        }

        $this->mountAction('assign', ['documentId' => $documentId]);
    }

    /** @param array<string, mixed> $filters */
    private function paperlessDocuments(
        ?string $search,
        array $filters,
        int $page,
        int $recordsPerPage,
        ?string $sortColumn,
        ?string $sortDirection,
    ): LengthAwarePaginator {
        $records = collect($this->paperlessDocumentRecords);
        $search = mb_strtolower(trim((string) $search));

        if ($search !== '') {
            $records = $records->filter(function (array $record) use ($search): bool {
                $searchableValues = [
                    $record['title'],
                    $record['supplier'],
                    $record['invoiceNumber'],
                    $record['documentDate'],
                    $record['amount'],
                ];

                return collect($searchableValues)
                    ->contains(fn (mixed $value): bool => str_contains(mb_strtolower((string) $value), $search));
            });
        }

        $records = match (data_get($filters, 'status.value')) {
            'unassigned' => $records->where('mappedCount', 0),
            'assigned' => $records->where('mappedCount', '>', 0),
            default => $records,
        };

        if (filled($paperlessTag = data_get($filters, 'paperless_tag.value'))) {
            $records = $records->filter(fn (array $record): bool => array_key_exists(
                (int) $paperlessTag,
                $record['tags'] ?? [],
            ));
        }

        if (filled($dateFrom = data_get($filters, 'document_date.from'))) {
            $records = $records->filter(fn (array $record): bool => filled($record['documentDate'])
                && $record['documentDate'] >= $dateFrom);
        }

        if (filled($dateUntil = data_get($filters, 'document_date.until'))) {
            $records = $records->filter(fn (array $record): bool => filled($record['documentDate'])
                && $record['documentDate'] <= $dateUntil);
        }

        $sortColumn = in_array($sortColumn, ['title', 'supplier', 'invoiceNumber', 'documentDate', 'amount', 'mappedCount'], true)
            ? $sortColumn
            : 'documentDate';
        $sortDirection = $sortDirection === 'asc' ? 'asc' : 'desc';
        $records = $records->sort(function (array $left, array $right) use ($sortColumn, $sortDirection): int {
            $leftValue = $left[$sortColumn];
            $rightValue = $right[$sortColumn];

            if ($leftValue === $rightValue) {
                return 0;
            }

            if ($leftValue === null) {
                return 1;
            }

            if ($rightValue === null) {
                return -1;
            }

            $comparison = is_numeric($leftValue) && is_numeric($rightValue)
                ? $leftValue <=> $rightValue
                : strnatcasecmp((string) $leftValue, (string) $rightValue);

            return $sortDirection === 'desc' ? -$comparison : $comparison;
        });

        $total = $records->count();
        $records = $records
            ->forPage($page, $recordsPerPage)
            ->mapWithKeys(fn (array $record): array => [$record['id'] => $record]);

        return $this->paginator($records, $total, $page, $recordsPerPage);
    }

    private function fetchPaperlessDocuments(): void
    {
        try {
            $this->setPaperlessDocuments(app(PaperlessClient::class)->refreshCachedDocuments());
            $this->addMappingCounts();
            $this->paperlessError = null;
        } catch (Throwable $exception) {
            report($exception);
            $this->paperlessError = 'Die Verbindung zu Paperless ist derzeit nicht verfügbar. Bitte prüfen Sie URL, API-Token und Erreichbarkeit.';
        }
    }

    private function loadCachedPaperlessDocuments(): void
    {
        try {
            $this->setPaperlessDocuments(app(PaperlessClient::class)->cachedDocuments());
            $this->addMappingCounts();
        } catch (Throwable $exception) {
            report($exception);
            $this->paperlessDocumentRecords = [];
        }
    }

    /** @param iterable<int, PaperlessDocument> $documents */
    private function setPaperlessDocuments(iterable $documents): void
    {
        $this->paperlessDocumentRecords = collect($documents)
            ->map(fn ($document): array => [
                ...$document->toArray(),
                'mappedCount' => 0,
            ])
            ->values()
            ->all();
    }

    private function addMappingCounts(): void
    {
        $documentIds = collect($this->paperlessDocumentRecords)->pluck('id');
        $mappedCounts = BankTransactionMapping::query()
            ->whereIn('document_id', $documentIds)
            ->selectRaw('document_id, COUNT(*) AS aggregate')
            ->groupBy('document_id')
            ->pluck('aggregate', 'document_id');

        $this->paperlessDocumentRecords = collect($this->paperlessDocumentRecords)
            ->map(fn (array $document): array => [
                ...$document,
                'mappedCount' => (int) ($mappedCounts[$document['id']] ?? 0),
            ])
            ->values()
            ->all();
    }

    /** @return array<string, string> */
    private function paperlessTagOptions(): array
    {
        $options = [];

        foreach ($this->paperlessDocumentRecords as $document) {
            foreach ($document['tags'] ?? [] as $tagId => $tagName) {
                $options[(string) $tagId] = $tagName;
            }
        }

        natcasesort($options);

        return $options;
    }

    /** @param Collection<int|string, array<string, mixed>> $records */
    private function paginator(Collection $records, int $total, int $page, int $recordsPerPage): LengthAwarePaginator
    {
        return new Paginator(
            items: $records,
            total: $total,
            perPage: $recordsPerPage,
            currentPage: $page,
        );
    }
}

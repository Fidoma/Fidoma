<?php

namespace App\Filament\Pages;

use App\Features\BankTransactions\Models\BankTransaction;
use App\Features\BankTransactions\Models\BankTransactionMapping;
use App\Features\Documents\Services\PaperlessClient;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Pages\Page;
use Filament\Support\Enums\Width;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\LengthAwarePaginator as Paginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Js;
use Livewire\Attributes\On;
use Throwable;

class Documents extends Page implements HasTable
{
    use InteractsWithTable;

    protected static ?string $title = 'Dokumente';

    protected static ?string $navigationLabel = 'Dokumente';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentText;

    protected string $view = 'filament.pages.documents';

    public ?string $paperlessError = null;

    public static function canAccess(): bool
    {
        return auth()->user()?->can('viewAny', BankTransaction::class) ?? false;
    }

    public function table(Table $table): Table
    {
        return $table
            ->records(fn (?string $search, int $page, int $recordsPerPage): LengthAwarePaginator => $this->paperlessDocuments(
                $search,
                $page,
                $recordsPerPage,
            ))
            ->columns([
                TextColumn::make('title')
                    ->label('Dokument')
                    ->weight('medium')
                    ->wrap(),
                TextColumn::make('supplier')
                    ->label('Lieferant')
                    ->placeholder('Kein Lieferant'),
                TextColumn::make('invoiceNumber')
                    ->label('Rechnungsnummer')
                    ->placeholder('–')
                    ->wrap(),
                TextColumn::make('documentDate')
                    ->label('Rechnungsdatum')
                    ->date('d.m.Y')
                    ->placeholder('Kein Datum'),
                TextColumn::make('amount')
                    ->label('Betrag')
                    ->money(fn (array $record): string => $record['currency'], locale: 'de')
                    ->placeholder('–'),
                TextColumn::make('mappedCount')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn (int $state): string => $state === 0
                        ? 'Nicht zugeordnet'
                        : $state.' '.($state === 1 ? 'Zuordnung' : 'Zuordnungen'))
                    ->color(fn (int $state): string => $state > 0 ? 'success' : 'gray'),
            ])
            ->recordAction('openAssignment')
            ->searchable()
            ->searchPlaceholder('Dokumente in Paperless durchsuchen')
            ->paginated([10, 20, 50])
            ->defaultPaginationPageOption(20)
            ->emptyStateHeading('Keine Dokumente gefunden')
            ->emptyStateDescription(fn (): ?string => $this->paperlessError)
            ->emptyStateIcon(Heroicon::OutlinedDocumentText);
    }

    #[On('paperless-mapping-created')]
    #[On('paperless-mapping-removed')]
    public function refreshMappings(): void
    {
        $this->resetTable();
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
            ->modalContent(fn (array $arguments) => view('filament.pages.partials.assign-bank-transaction', [
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
                .' window.paperlessDocumentPreview.focus();'
                .'}');
        }

        $this->mountAction('assign', ['documentId' => $documentId]);
    }

    private function paperlessDocuments(?string $search, int $page, int $recordsPerPage): LengthAwarePaginator
    {
        try {
            $result = app(PaperlessClient::class)->documents((string) $search, $page, $recordsPerPage);
            $documentIds = collect($result['documents'])->pluck('id');
            $mappedCounts = BankTransactionMapping::query()
                ->whereIn('document_id', $documentIds)
                ->selectRaw('document_id, COUNT(*) AS aggregate')
                ->groupBy('document_id')
                ->pluck('aggregate', 'document_id');

            $records = collect($result['documents'])
                ->mapWithKeys(fn ($document): array => [$document->id => [
                    ...$document->toArray(),
                    'mappedCount' => (int) ($mappedCounts[$document->id] ?? 0),
                ]]);

            $this->paperlessError = null;

            return $this->paginator($records, $result['count'], $page, $recordsPerPage);
        } catch (Throwable $exception) {
            report($exception);
            $this->paperlessError = 'Die Verbindung zu Paperless ist derzeit nicht verfügbar. Bitte prüfen Sie URL, API-Token und Erreichbarkeit.';

            return $this->paginator(collect(), 0, $page, $recordsPerPage);
        }
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

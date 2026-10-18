<?php

namespace App\Features\Imports\Livewire;

use App\Features\Imports\Filament\Pages\DataImport;
use App\Features\Imports\Services\BankTransactionCsvImporter;
use App\Features\Imports\Services\BankTransactionDuplicateDetector;
use App\Features\Imports\Services\CsvReader;
use Filament\Actions\BulkAction;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Tables\TableComponent;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Contracts\View\View;
use Illuminate\Pagination\LengthAwarePaginator as Paginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Throwable;

class BankTransactionCsvPreview extends TableComponent
{
    public string $path;

    /** @var array<string, string|null> */
    public array $mapping = [];

    /** @var array<string, mixed> */
    public array $defaults = [];

    /** @var array<string, mixed> */
    public array $signRule = [];

    /** @var list<int> */
    public array $excludedLines = [];

    /** @var list<array<string, mixed>> */
    public array $previewRecords = [];

    public string $activeTab = 'all';

    public function mount(string $path, array $mapping, array $defaults, array $signRule, array $excludedLines = []): void
    {
        abort_unless(str_starts_with($path, 'imports/bank-transactions/') && Storage::disk('local')->exists($path), 404);
        $this->path = $path;
        $this->mapping = $mapping;
        $this->defaults = $defaults;
        $this->signRule = $signRule;
        $this->excludedLines = $excludedLines;
        $this->previewRecords = $this->readRecords()
            ->reject(fn (array $record): bool => in_array((int) $record['line'], $this->excludedLines, true))
            ->values()
            ->all();
    }

    public function table(Table $table): Table
    {
        return $table
            ->identifier('bankTransactionCsvPreview')
            ->records(fn (int $page, int $recordsPerPage): LengthAwarePaginator => $this->records($page, $recordsPerPage))
            ->resolveSelectedRecordsUsing(fn (array $keys): Collection => $this->visibleRecords()
                ->keyBy('id')
                ->only($keys))
            ->columns([
                TextColumn::make('line')->label('Zeile'),
                TextColumn::make('booking_date')->label('Buchungsdatum')->date('d.m.Y'),
                TextColumn::make('counterparty_name')->label('Gegenpartei')->placeholder('–')->wrap(),
                TextColumn::make('booking_text')->label('Buchungstext')->placeholder('–')->wrap(),
                TextColumn::make('purpose')->label('Verwendungszweck')->placeholder('–')->limit(60)->wrap(),
                TextColumn::make('amount')->label('Betrag')
                    ->formatStateUsing(fn (mixed $state, array $record): string => number_format((float) $state, 2, ',', '.').' '.($record['currency'] ?? '')),
                TextColumn::make('duplicate_status')->label('Importstatus')->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'duplicate' => 'Bereits vorhanden',
                        'possible_duplicate' => 'Mögliches Duplikat',
                        'invalid' => 'Ungültig',
                        default => 'Neu',
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'duplicate' => 'danger',
                        'possible_duplicate', 'invalid' => 'warning',
                        default => 'success',
                    }),
            ])
            ->toolbarActions([
                BulkAction::make('removeSelected')
                    ->label('Aus Vorschau entfernen')
                    ->icon(Heroicon::OutlinedTrash)
                    ->color('danger')
                    ->action(fn (Collection $records) => $this->removeSelected($records)),
            ])
            ->selectCurrentPageOnly()
            ->paginated([5, 10, 25, 50])
            ->defaultPaginationPageOption(10)
            ->emptyStateHeading('Keine CSV-Zeilen in diesem Bereich')
            ->emptyStateDescription('Wählen Sie einen anderen Status-Tab aus.');
    }

    public function showTab(string $tab): void
    {
        abort_unless(in_array($tab, ['all', 'new', 'duplicates'], true), 404);
        $this->activeTab = $tab;
        $this->resetTable();
    }

    /** @param Collection<int, array<string, mixed>> $records */
    public function removeSelected(Collection $records): void
    {
        $lines = $records
            ->pluck('line')
            ->map(fn (mixed $line): int => (int) $line)
            ->values()
            ->all();
        $recordIds = $records
            ->pluck('id')
            ->map(fn (mixed $id): string => (string) $id)
            ->all();

        $this->excludedLines = collect([...$this->excludedLines, ...$lines])
            ->unique()
            ->sort()
            ->values()
            ->all();

        $this->previewRecords = collect($this->previewRecords)
            ->reject(fn (array $record): bool => in_array((string) $record['id'], $recordIds, true))
            ->values()
            ->all();

        $this->selectedTableRecords = [];
        $this->deselectedTableRecords = [];
        $this->isTrackingDeselectedTableRecords = false;
        unset($this->cachedSelectedTableRecords);
        $this->flushCachedTableRecords();
        $this->resetPage(pageName: $this->getTablePaginationPageName());
        $this->dispatch('deselectAllTableRecords')->self();
        $this->dispatch('csv-preview-rows-removed', lines: $lines)->to(DataImport::class);

        Notification::make()
            ->success()
            ->title(count($lines) === 1 ? 'Transaktion wurde entfernt.' : count($lines).' Transaktionen wurden entfernt.')
            ->send();
    }

    /** @return array{all: int, new: int, duplicates: int} */
    public function counts(): array
    {
        $records = $this->visibleRecords();

        return [
            'all' => $records->count(),
            'new' => $records->where('duplicate_status', 'new')->count(),
            'duplicates' => $records->whereIn('duplicate_status', ['duplicate', 'possible_duplicate'])->count(),
        ];
    }

    public function render(): View
    {
        return view('livewire.features.imports.bank-transaction-csv-preview');
    }

    private function records(int $page, int $recordsPerPage): LengthAwarePaginator
    {
        $records = $this->visibleRecords();
        $records = (match ($this->activeTab) {
            'new' => $records->where('duplicate_status', 'new'),
            'duplicates' => $records->whereIn('duplicate_status', ['duplicate', 'possible_duplicate']),
            default => $records,
        })->values();

        return new Paginator(
            $records->forPage($page, $recordsPerPage),
            $records->count(),
            $recordsPerPage,
            $page,
            ['path' => request()->url(), 'pageName' => $this->getTablePaginationPageName()],
        );
    }

    /** @return Collection<int, array<string, mixed>> */
    private function visibleRecords(): Collection
    {
        return collect($this->previewRecords);
    }

    /** @return Collection<int, array<string, mixed>> */
    private function readRecords(): Collection
    {
        $records = collect();
        $importer = app(BankTransactionCsvImporter::class);
        $detector = app(BankTransactionDuplicateDetector::class);

        foreach (app(CsvReader::class)->rows(Storage::disk('local')->path($this->path)) as $line => $sourceRow) {
            if ($line === 1) {
                continue;
            }

            try {
                $attributes = $importer->prepareRow($sourceRow, $this->mapping, $this->defaults, $this->signRule, $line);
                $status = $detector->status($attributes);
            } catch (Throwable) {
                $attributes = [];
                $status = 'invalid';
            }

            $recordId = (string) Str::uuid();

            $records->push([
                '__key' => $recordId,
                'id' => $recordId,
                'line' => $line,
                'booking_date' => $attributes['booking_date'] ?? null,
                'counterparty_name' => $attributes['counterparty_name'] ?? null,
                'booking_text' => $attributes['booking_text'] ?? null,
                'purpose' => $attributes['purpose'] ?? null,
                'amount' => $attributes['amount'] ?? null,
                'currency' => $attributes['currency'] ?? null,
                'duplicate_status' => $status,
            ]);
        }

        return $records;
    }
}

<?php

namespace App\Features\Imports\Filament\Pages;

use App\Features\BankTransactions\Models\BankTransaction;
use App\Features\Imports\Livewire\BankTransactionCsvPreview;
use App\Features\Imports\Services\BankTransactionCsvImporter;
use App\Features\Imports\Services\CsvReader;
use App\Filament\Concerns\WithoutPageHeading;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\Livewire as LivewireComponent;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\View;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Width;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\On;
use Throwable;
use UnitEnum;

class DataImport extends Page
{
    use WithoutPageHeading;

    protected static ?string $title = 'Datenimport';

    protected static ?string $navigationLabel = 'Datenimport';

    protected static ?string $slug = 'data-import';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedArrowUpTray;

    protected static string|UnitEnum|null $navigationGroup = 'Finanzen';

    protected static ?int $navigationSort = 5;

    protected string $view = 'filament.pages.data-import';

    protected Width|string|null $maxContentWidth = Width::Full;

    /** @var array<string, mixed>|null */
    public ?array $data = [];

    /** @var list<string> */
    public array $csvHeaders = [];

    /** @var list<int> */
    public array $excludedLines = [];

    public static function getNavigationLabel(): string
    {
        return __('imports::messages.feature');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('imports::messages.navigation_group');
    }

    public function getTitle(): string
    {
        return __('imports::messages.feature');
    }

    public function mount(): void
    {
        $this->form->fill([
            'defaults' => ['currency' => 'EUR', 'pending' => '0', 'validated' => '0'],
            'sign_rule' => ['negative_value' => 'DEBIT', 'positive_value' => 'CREDIT'],
        ]);
    }

    public static function canAccess(): bool
    {
        return Gate::allows('create', BankTransaction::class);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->statePath('data')
            ->components([
                Section::make('Banktransaktionen importieren')
                    ->description('CSV-Datei hochladen, Spalten prüfen und anschließend importieren.')
                    ->schema([
                        FileUpload::make('csv_file')
                            ->label('CSV-Datei')
                            ->disk('local')
                            ->directory('imports/bank-transactions')
                            ->acceptedFileTypes(['text/csv', 'text/plain', 'application/csv', 'application/vnd.ms-excel'])
                            ->maxSize(10240)
                            ->required(),
                        Actions::make([
                            Action::make('inspectCsv')
                                ->label('Vorschau und Zuordnung erstellen')
                                ->icon(Heroicon::OutlinedTableCells)
                                ->action('inspectCsv'),
                        ])->visible(fn (): bool => $this->csvHeaders === []),
                    ]),
                Section::make('Spaltenzuordnung')
                    ->description('Ordnen Sie die CSV-Spalten den Banktransaktionsfeldern zu. Leere Zellen können durch einen festen Standardwert ersetzt werden.')
                    ->schema([
                        ...$this->mappingStateComponents(),
                        View::make('features.imports.column-mapping')
                            ->viewData(fn (): array => [
                                'mappingRows' => $this->mappingRows(),
                                'csvHeaders' => $this->csvHeaders,
                            ]),
                        Section::make('Soll/Haben-Regel')
                            ->description('Optional für CSV-Dateien, die das Vorzeichen in einer separaten Spalte führen.')
                            ->columns(3)
                            ->schema([
                                Select::make('sign_rule.column')
                                    ->label('Soll/Haben-Spalte')
                                    ->options($this->headerOptions())
                                    ->placeholder('Vorzeichen bereits im Betrag'),
                                TextInput::make('sign_rule.negative_value')->label('Wert für Ausgabe'),
                                TextInput::make('sign_rule.positive_value')->label('Wert für Einnahme'),
                            ]),
                        Section::make('CSV-Vorschau')
                            ->schema([
                                LivewireComponent::make(
                                    BankTransactionCsvPreview::class,
                                    fn (): array => [
                                        'path' => $this->uploadedPath($this->data['csv_file'] ?? null),
                                        'mapping' => $this->data['mapping'] ?? [],
                                        'defaults' => $this->data['defaults'] ?? [],
                                        'signRule' => $this->data['sign_rule'] ?? [],
                                        'excludedLines' => $this->excludedLines,
                                    ],
                                )->key(fn (): string => 'csv-preview-'.md5(serialize([
                                    $this->data['csv_file'] ?? null,
                                    $this->data['mapping'] ?? [],
                                    $this->data['defaults'] ?? [],
                                    $this->data['sign_rule'] ?? [],
                                ]))),
                            ]),
                        Actions::make([
                            Action::make('importCsv')
                                ->label('Banktransaktionen importieren')
                                ->icon(Heroicon::OutlinedArrowUpTray)
                                ->action('importCsv'),
                            Action::make('resetImport')
                                ->label('Andere Datei auswählen')
                                ->color('gray')
                                ->action('resetImport'),
                        ]),
                    ])
                    ->visible(fn (): bool => $this->csvHeaders !== []),
            ]);
    }

    public function inspectCsv(): void
    {
        $state = $this->form->getState();
        $path = $this->absolutePath($this->uploadedPath($state['csv_file'] ?? null));

        try {
            $inspection = app(CsvReader::class)->inspect($path);
            $this->csvHeaders = $inspection['headers'];
            $this->excludedLines = [];
            $state['mapping'] = app(BankTransactionCsvImporter::class)->suggestMapping($this->csvHeaders);
            $state['defaults'] = array_replace(
                ['currency' => 'EUR', 'pending' => '0', 'validated' => '0'],
                $state['defaults'] ?? [],
            );
            $state['sign_rule'] = array_replace(
                ['negative_value' => 'DEBIT', 'positive_value' => 'CREDIT'],
                $state['sign_rule'] ?? [],
            );
            $this->form->fill($state);
        } catch (Throwable $exception) {
            report($exception);
            $this->addError('data.csv_file', $exception->getMessage());
        }
    }

    public function importCsv(): void
    {
        $state = $this->form->getState();

        try {
            $count = app(BankTransactionCsvImporter::class)->import(
                $this->absolutePath($this->uploadedPath($state['csv_file'] ?? null)),
                $state['mapping'] ?? [],
                $state['defaults'] ?? [],
                $state['sign_rule'] ?? [],
                $this->excludedLines,
            );
        } catch (Throwable $exception) {
            if (method_exists($exception, 'errors')) {
                throw $exception;
            }

            report($exception);
            $this->addError('data.csv_file', $exception->getMessage());

            return;
        }

        Storage::disk('local')->delete($this->uploadedPath($state['csv_file'] ?? null));
        $this->resetImport();

        Notification::make()
            ->success()
            ->title($count.' Banktransaktionen wurden importiert.')
            ->send();
    }

    public function resetImport(): void
    {
        $this->csvHeaders = [];
        $this->excludedLines = [];
        $this->form->fill([
            'defaults' => ['currency' => 'EUR', 'pending' => '0', 'validated' => '0'],
            'sign_rule' => ['negative_value' => 'DEBIT', 'positive_value' => 'CREDIT'],
        ]);
    }

    /** @param list<int> $lines */
    #[On('csv-preview-rows-removed')]
    public function excludeCsvRows(array $lines): void
    {
        $this->excludedLines = collect([...$this->excludedLines, ...$lines])
            ->map(fn (mixed $line): int => (int) $line)
            ->unique()
            ->sort()
            ->values()
            ->all();
    }

    /** @return list<Hidden> */
    private function mappingStateComponents(): array
    {
        return collect(BankTransactionCsvImporter::FIELDS)
            ->flatMap(fn (string $field): array => [
                Hidden::make("mapping.{$field}")
                    ->required(in_array($field, ['account_iban', 'booking_date', 'amount'], true)),
                Hidden::make("defaults.{$field}"),
            ])
            ->all();
    }

    /** @return list<array{field: string, label: string, csvColumn: ?string, defaultValue: ?string, required: bool}> */
    private function mappingRows(): array
    {
        return collect(BankTransactionCsvImporter::FIELDS)
            ->map(fn (string $field): array => [
                'field' => $field,
                'label' => $this->fieldLabel($field),
                'csvColumn' => filled($this->data['mapping'][$field] ?? null)
                    ? (string) $this->data['mapping'][$field]
                    : null,
                'defaultValue' => filled($this->data['defaults'][$field] ?? null)
                    ? (string) $this->data['defaults'][$field]
                    : null,
                'required' => in_array($field, ['account_iban', 'booking_date', 'amount'], true),
            ])
            ->all();
    }

    /** @return array<string, string> */
    private function headerOptions(): array
    {
        return array_combine($this->csvHeaders, $this->csvHeaders) ?: [];
    }

    private function absolutePath(string $path): string
    {
        abort_unless(
            str_starts_with($path, 'imports/bank-transactions/') && Storage::disk('local')->exists($path),
            422,
            __('imports::messages.errors.unreadable'),
        );

        return Storage::disk('local')->path($path);
    }

    private function uploadedPath(mixed $state): string
    {
        if (is_array($state)) {
            $state = reset($state);
        }

        return is_string($state) ? $state : '';
    }

    private function fieldLabel(string $field): string
    {
        return __('imports::messages.fields.'.$field);
    }
}

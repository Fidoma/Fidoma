<?php

namespace App\Features\Reports\Filament\Pages;

use App\Features\Clients\Models\Client;
use App\Features\Invoices\Actions\GenerateInvoicePdf;
use App\Features\Invoices\Actions\GenerateInvoiceXRechnung;
use App\Features\Invoices\Enums\InvoiceStatus;
use App\Features\Invoices\Enums\InvoiceType;
use App\Features\Invoices\Models\Invoice;
use App\Features\Invoices\Models\InvoiceItem;
use App\Features\PaymentMethods\Models\PaymentMethod;
use App\Features\Payments\Models\Payment;
use App\Features\Reports\Services\InvoiceReportService;
use App\Features\Reports\Services\PaymentReportService;
use App\Features\Reports\Services\ReportExportService;
use App\Features\Reports\Services\SalesReportService;
use App\Features\Reports\Services\TaxReportService;
use App\Features\Reports\Services\UstvaReportService;
use App\Features\TaxRates\Models\TaxRate;
use App\Filament\Concerns\WithoutPageHeading;
use BackedEnum;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\CarbonImmutable;
use DomainException;
use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Enums\Width;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;
use UnitEnum;
use ZipArchive;

class Reports extends Page implements HasTable
{
    use InteractsWithTable, WithoutPageHeading;

    protected static ?string $title = 'Berichte';

    protected static ?string $navigationLabel = 'Berichte';

    protected static ?string $slug = 'reports';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedPresentationChartLine;

    protected static string|UnitEnum|null $navigationGroup = 'Finanzen';

    protected static ?int $navigationSort = 4;

    protected string $view = 'filament.pages.reports';

    protected Width|string|null $maxContentWidth = Width::Full;

    public string $activeTab = 'tax';

    public string $taxationType = 'soll';

    public ?string $startDate = null;

    public ?string $endDate = null;

    public function mount(): void
    {
        $this->resetTable();
    }

    public static function canAccess(): bool
    {
        return auth()->check();
    }

    public function setTab(string $tab): void
    {
        abort_unless(in_array($tab, ['tax', 'payments', 'invoices', 'sales'], true), 404);

        $this->activeTab = $tab;
        $this->resetTable();
    }

    public function applyPeriod(): void
    {
        $this->validate([
            'startDate' => ['nullable', 'date', 'required_with:endDate'],
            'endDate' => ['nullable', 'date', 'required_with:startDate', 'after_or_equal:startDate'],
        ], ['endDate.after_or_equal' => 'Das Enddatum muss nach dem Startdatum liegen.']);

        $this->resetTable();
    }

    public function clearDateFilter(): void
    {
        $this->startDate = null;
        $this->endDate = null;
        $this->resetValidation(['startDate', 'endDate']);
        $this->resetTable();
    }

    public function setTaxationType(string $type): void
    {
        abort_unless(in_array($type, ['soll', 'ist'], true), 404);

        $this->taxationType = $type;
    }

    public function table(Table $table): Table
    {
        return match ($this->activeTab) {
            'payments' => $this->paymentsTable($table),
            'invoices' => $this->invoicesTable($table),
            'sales' => $this->salesTable($table),
            default => $this->taxTable($table),
        };
    }

    /** @return array<string, float|int|null> */
    public function summary(): array
    {
        [$start, $end] = $this->dates();

        return match ($this->activeTab) {
            'payments' => app(PaymentReportService::class)->summary(
                $start, $end, $this->integerTableFilterValue('payment_method_id'),
            ),
            'invoices' => app(InvoiceReportService::class)->summary(
                $start,
                $end,
                $this->integerTableFilterValue('invoice_status_id'),
                $this->integerTableFilterValue('client_id'),
                $this->tableFilterValue('invoice_type'),
            ),
            'sales' => app(SalesReportService::class)->summary(
                $start,
                $end,
                $this->integerTableFilterValue('invoice_status_id'),
                $this->integerTableFilterValue('client_id'),
                $this->integerTableFilterValue('item_tax_rate_id'),
                $this->tableFilterValue('invoice_type'),
            ),
            default => app(TaxReportService::class)->summary($start, $end),
        };
    }

    /** @return array<string, mixed> */
    public function ustvaReport(): array
    {
        [$start, $end] = $this->dates();
        $supportedYears = app(UstvaReportService::class)->supportedYears();
        $year = in_array($start->year, $supportedYears, true) ? $start->year : max($supportedYears);

        return app(UstvaReportService::class)->report($start, $end, $this->taxationType, $year);
    }

    public function viewUstvaDetailsAction(): Action
    {
        return Action::make('viewUstvaDetails')
            ->modalHeading(fn (array $arguments): string => 'UStVA Kz. '.($arguments['code'] ?? ''))
            ->modalContent(function (array $arguments): View {
                [$start, $end] = $this->dates();
                $row = collect($this->ustvaReport()['rows'])
                    ->firstWhere('code', (string) ($arguments['code'] ?? ''));

                return view('filament.pages.reports-ustva-details', [
                    'code' => (string) ($row['code'] ?? ''),
                    'description' => (string) ($row['description'] ?? 'Keine Beschreibung verfügbar.'),
                    'excludedDraftCount' => (int) ($row['excluded_draft_count'] ?? 0),
                    'startDate' => $start->toDateString(),
                    'endDate' => $end->toDateString(),
                    'taxationType' => $this->taxationType,
                ]);
            })
            ->modalSubmitAction(false)
            ->modalCancelActionLabel('Schließen')
            ->modalWidth(Width::SevenExtraLarge)
            ->slideOver();
    }

    /** @return array<int, string> */
    public function paymentMethods(): array
    {
        return PaymentMethod::query()->orderBy('payment_method_name')->pluck('payment_method_name', 'payment_method_id')->all();
    }

    /** @return array<int, string> */
    public function clients(): array
    {
        return Client::query()->orderBy('client_name')->pluck('client_name', 'client_id')->all();
    }

    /** @return array<string, string> */
    public function invoiceTypes(): array
    {
        return collect(InvoiceType::cases())
            ->mapWithKeys(fn (InvoiceType $type): array => [$type->value => $type->label()])
            ->all();
    }

    /** @return array<int, string> */
    public function taxRates(): array
    {
        return TaxRate::query()
            ->orderBy('tax_rate_percent')
            ->get()
            ->mapWithKeys(fn (TaxRate $rate): array => [
                $rate->getKey() => rtrim(rtrim(number_format((float) $rate->tax_rate_percent, 2, ',', ''), '0'), ',').' %',
            ])
            ->all();
    }

    public function exportCsv(): StreamedResponse
    {
        [$headings, $rows] = $this->exportData();

        return app(ReportExportService::class)->csv($this->exportFilename(), $headings, $rows);
    }

    public function exportUstvaPdf(): BinaryFileResponse
    {
        [$start, $end] = $this->dates();
        $path = tempnam(sys_get_temp_dir(), 'fidoma-ustva-');

        Pdf::loadView('filament.pages.reports-ustva-pdf', [
            'report' => $this->ustvaReport(),
            'start' => $start,
            'end' => $end,
        ])->setPaper('a4')->save($path);

        return response()->download($path, $this->exportFilename().'.pdf')->deleteFileAfterSend();
    }

    private function taxTable(Table $table): Table
    {
        return $table
            ->query(fn (): Builder => app(TaxReportService::class)->query(...$this->dates()))
            ->columns([
                TextColumn::make('report_label')->label('Steuersatz')
                    ->state(fn (InvoiceItem $record): string => app(TaxReportService::class)->label($record)),
                TextColumn::make('report_net')->label('Netto')->money('EUR', locale: 'de')->sortable(),
                TextColumn::make('report_tax')->label('Umsatzsteuer')->money('EUR', locale: 'de')->sortable(),
                TextColumn::make('report_gross')->label('Brutto')->money('EUR', locale: 'de')->sortable(),
            ])
            ->defaultSort('report_tax_percent')
            ->defaultKeySort(false)
            ->paginated(false)
            ->emptyStateHeading('Keine steuerrelevanten Umsätze')
            ->emptyStateDescription('Für den ausgewählten Zeitraum wurden keine ausgestellten Rechnungspositionen gefunden.')
            ->emptyStateIcon(Heroicon::OutlinedReceiptPercent);
    }

    private function paymentsTable(Table $table): Table
    {
        return $table
            ->query(fn (): Builder => app(PaymentReportService::class)->query(
                ...$this->dates(),
            ))
            ->columns([
                TextColumn::make('payment_date')->label('Zahlungsdatum')->date('d.m.Y')->sortable(),
                TextColumn::make('invoice.client.client_name')->label('Kunde')->placeholder('–')->sortable(),
                TextColumn::make('invoice.invoice_number')->label('Rechnung')->placeholder('–')->sortable(),
                TextColumn::make('paymentMethod.payment_method_name')->label('Zahlungsart')->placeholder('–'),
                TextColumn::make('payment_amount')->label('Betrag')->money('EUR', locale: 'de')->sortable(),
                TextColumn::make('payment_note')->label('Notiz')->placeholder('–')->wrap(),
            ])
            ->filters([
                SelectFilter::make('payment_method_id')
                    ->label('Zahlungsart')
                    ->options(fn (): array => $this->paymentMethods()),
            ])
            ->toolbarActions([$this->csvExportAction()])
            ->defaultSort('payment_date', 'desc')
            ->searchable(false)
            ->paginated([10, 25, 50])
            ->defaultPaginationPageOption(25)
            ->emptyStateHeading('Keine Zahlungen gefunden')
            ->emptyStateDescription('Für den gewählten Zeitraum und die gesetzten Filter liegen keine Zahlungen vor.')
            ->emptyStateIcon(Heroicon::OutlinedBanknotes);
    }

    private function invoicesTable(Table $table): Table
    {
        return $table
            ->query(fn (): Builder => app(InvoiceReportService::class)->query(
                ...$this->dates(),
            ))
            ->columns([
                TextColumn::make('invoice_number')->label('Rechnungsnummer')->searchable()->sortable(),
                TextColumn::make('client.client_name')->label('Kunde')->placeholder('–')->searchable()->sortable(),
                TextColumn::make('invoice_date_created')->label('Rechnungsdatum')->date('d.m.Y')->sortable(),
                TextColumn::make('invoice_date_due')->label('Fälligkeitsdatum')->date('d.m.Y')->sortable(),
                TextColumn::make('net_amount')->label('Netto')->state(fn (Invoice $record): float => (float) ($record->amount?->invoice_total ?? 0)
                    - (float) ($record->amount?->invoice_item_tax_total ?? 0)
                    - (float) ($record->amount?->invoice_tax_total ?? 0))->money('EUR', locale: 'de'),
                TextColumn::make('tax_amount')->label('Umsatzsteuer')->state(fn (Invoice $record): float => (float) ($record->amount?->invoice_item_tax_total ?? 0)
                    + (float) ($record->amount?->invoice_tax_total ?? 0))->money('EUR', locale: 'de'),
                TextColumn::make('amount.invoice_total')->label('Brutto')->money('EUR', locale: 'de')->sortable(),
                TextColumn::make('payment_status')->label('Zahlungsstatus')->badge()
                    ->state(fn (Invoice $record): string => app(InvoiceReportService::class)->paymentStatus($record))
                    ->color(fn (string $state): string => match ($state) {
                        'Bezahlt' => 'success', 'Teilbezahlt' => 'warning', default => 'gray',
                    }),
            ])
            ->filters($this->invoiceFilters())
            ->recordActions([
                Action::make('pdf')->label('PDF')->icon(Heroicon::OutlinedDocumentArrowDown)
                    ->url(fn (Invoice $record): string => route('invoices.pdf', ['invoice' => $record]))
                    ->openUrlInNewTab(),
            ])
            ->toolbarActions([
                $this->csvExportAction(),
                BulkAction::make('downloadPdfs')->label('PDFs als ZIP')->icon(Heroicon::OutlinedArchiveBoxArrowDown)
                    ->action(fn (Collection $records): BinaryFileResponse => $this->downloadInvoicePdfs($records)),
                BulkAction::make('downloadXmls')->label('XML als ZIP')->icon(Heroicon::OutlinedCodeBracketSquare)
                    ->action(fn (Collection $records): BinaryFileResponse => $this->downloadInvoiceXmls($records)),
            ])
            ->defaultSort('invoice_date_created', 'desc')
            ->searchable(false)
            ->paginated([10, 25, 50])
            ->defaultPaginationPageOption(25)
            ->emptyStateHeading('Keine Rechnungen gefunden')
            ->emptyStateDescription('Für den gewählten Zeitraum und die gesetzten Filter liegen keine Rechnungen vor.')
            ->emptyStateIcon(Heroicon::OutlinedDocumentCurrencyEuro);
    }

    private function salesTable(Table $table): Table
    {
        return $table
            ->query(fn (): Builder => app(SalesReportService::class)->query(
                ...$this->dates(),
            ))
            ->columns([
                TextColumn::make('invoice.invoice_number')->label('Rechnung')->placeholder('–')->searchable()->sortable(),
                TextColumn::make('invoice.client.client_name')->label('Kunde / Lieferant')->placeholder('–')->searchable()->sortable(),
                TextColumn::make('item_name')->label('Position')->placeholder('–')->searchable()->wrap()->sortable(),
                TextColumn::make('invoice.invoice_date_created')->label('Rechnungsdatum')->date('d.m.Y')->sortable(),
                TextColumn::make('taxRate.tax_rate_percent')->label('Steuer')->placeholder('–')
                    ->formatStateUsing(fn (mixed $state): string => rtrim(rtrim(number_format((float) $state, 2, ',', ''), '0'), ',').' %')
                    ->sortable(),
                TextColumn::make('amount.item_subtotal')->label('Netto')->money('EUR', locale: 'de')->sortable(),
                TextColumn::make('amount.item_tax_total')->label('Umsatzsteuer')->money('EUR', locale: 'de')->sortable(),
                TextColumn::make('amount.item_total')->label('Brutto')->money('EUR', locale: 'de')->sortable(),
            ])
            ->filters([
                ...$this->invoiceFilters(forItems: true),
                SelectFilter::make('item_tax_rate_id')
                    ->label('Steuer')
                    ->options(fn (): array => $this->taxRates()),
            ])
            ->toolbarActions([$this->csvExportAction()])
            ->defaultSort('item_id', 'desc')
            ->searchable(false)
            ->paginated([10, 25, 50])
            ->defaultPaginationPageOption(25)
            ->emptyStateHeading('Keine Verkaufspositionen gefunden')
            ->emptyStateDescription('Für den gewählten Zeitraum und die gesetzten Filter liegen keine Rechnungspositionen vor.')
            ->emptyStateIcon(Heroicon::OutlinedShoppingCart);
    }

    /** @return list<SelectFilter> */
    private function invoiceFilters(bool $forItems = false): array
    {
        $invoiceQuery = fn (Builder $query, array $data, string $column): Builder => $query->when(
            filled($data['value'] ?? null),
            fn (Builder $query): Builder => $forItems
                ? $query->whereHas('invoice', fn (Builder $query): Builder => $query->where($column, $data['value']))
                : $query->where($column, $data['value']),
        );

        return [
            SelectFilter::make('invoice_type')
                ->label('Rechnungsart')
                ->options(fn (): array => $this->invoiceTypes())
                ->query(fn (Builder $query, array $data): Builder => $invoiceQuery($query, $data, 'invoice_type')),
            SelectFilter::make('invoice_status_id')
                ->label('Rechnungsstatus')
                ->options(InvoiceStatus::options())
                ->query(fn (Builder $query, array $data): Builder => $invoiceQuery($query, $data, 'invoice_status_id')),
            SelectFilter::make('client_id')
                ->label('Kunde')
                ->options(fn (): array => $this->clients())
                ->searchable()
                ->query(fn (Builder $query, array $data): Builder => $invoiceQuery($query, $data, 'client_id')),
        ];
    }

    private function tableFilterValue(string $filter): ?string
    {
        $value = $this->getTableFilterState($filter)['value'] ?? null;

        return filled($value) ? (string) $value : null;
    }

    private function integerTableFilterValue(string $filter): ?int
    {
        $value = $this->tableFilterValue($filter);

        return $value !== null ? (int) $value : null;
    }

    private function csvExportAction(): Action
    {
        return Action::make('exportCsv')
            ->label('CSV')
            ->icon(Heroicon::OutlinedDocumentText)
            ->color('gray')
            ->action('exportCsv');
    }

    /** @return array{CarbonImmutable, CarbonImmutable} */
    private function dates(): array
    {
        return [
            filled($this->startDate) ? CarbonImmutable::parse($this->startDate)->startOfDay() : CarbonImmutable::create(1900, 1, 1)->startOfDay(),
            filled($this->endDate) ? CarbonImmutable::parse($this->endDate)->endOfDay() : CarbonImmutable::create(9999, 12, 31)->endOfDay(),
        ];
    }

    /** @return array{list<string>, iterable<list<bool|float|int|string|null>>} */
    private function exportData(): array
    {
        if ($this->activeTab === 'tax') {
            $report = $this->ustvaReport();
            [$start, $end] = $this->dates();
            $period = $start->format('d.m.Y').'–'.$end->format('d.m.Y');
            $taxationType = $this->taxationType === 'soll' ? 'Sollversteuerung' : 'Istversteuerung';
            $rows = collect($report['rows'])->map(fn (array $row): array => [
                $row['code'], $row['section'], $row['description'], $row['net'], $row['tax'],
                $row['excluded_draft_count'] > 0
                    ? $row['excluded_draft_count'].' Entwürfe ausgeschlossen'
                    : ($row['supported'] ? 'Berechnet' : 'Nicht verfügbar'),
                $period, $taxationType, null,
            ])->concat(collect($report['warnings'])->map(fn (string $warning): array => [
                null, null, null, null, null, 'Hinweis', $period, $taxationType, $warning,
            ]));

            return [[
                'Kz.', 'Bereich', 'Beschreibung', 'Bemessungsgrundlage', 'Steuerbetrag', 'Status',
                'Zeitraum', 'Versteuerung', 'Validierungshinweis',
            ], $rows];
        }

        $records = $this->getFilteredSortedTableQuery()?->get() ?? collect();

        return match ($this->activeTab) {
            'payments' => [[
                'Zahlungsdatum', 'Kunde', 'Rechnung', 'Zahlungsart', 'Betrag', 'Notiz',
            ], $records->map(fn (Payment $record): array => [
                $record->payment_date?->format('d.m.Y'), $record->invoice?->client?->client_name,
                $record->invoice?->invoice_number, $record->paymentMethod?->payment_method_name,
                (float) $record->payment_amount, $record->payment_note,
            ])],
            'invoices' => [[
                'Rechnungsnummer', 'Kunde', 'Rechnungsdatum', 'Fälligkeitsdatum', 'Netto', 'Umsatzsteuer', 'Brutto', 'Zahlungsstatus',
            ], $records->map(fn (Invoice $record): array => [
                $record->invoice_number, $record->client?->client_name, $record->invoice_date_created?->format('d.m.Y'),
                $record->invoice_date_due?->format('d.m.Y'),
                (float) ($record->amount?->invoice_total ?? 0) - (float) ($record->amount?->invoice_item_tax_total ?? 0) - (float) ($record->amount?->invoice_tax_total ?? 0),
                (float) ($record->amount?->invoice_item_tax_total ?? 0) + (float) ($record->amount?->invoice_tax_total ?? 0),
                (float) ($record->amount?->invoice_total ?? 0), app(InvoiceReportService::class)->paymentStatus($record),
            ])],
            'sales' => [[
                'Rechnung', 'Kunde / Lieferant', 'Position', 'Rechnungsdatum', 'Steuer', 'Netto', 'Umsatzsteuer', 'Brutto',
            ], $records->map(fn (InvoiceItem $record): array => [
                $record->invoice?->invoice_number, $record->invoice?->client?->client_name, $record->item_name,
                $record->invoice?->invoice_date_created?->format('d.m.Y'),
                (float) ($record->taxRate?->tax_rate_percent ?? 0),
                (float) ($record->amount?->item_subtotal ?? 0), (float) ($record->amount?->item_tax_total ?? 0),
                (float) ($record->amount?->item_total ?? 0),
            ])],
            default => [[
                'Steuersatz', 'Netto', 'Umsatzsteuer', 'Brutto',
            ], $records->map(fn (InvoiceItem $record): array => [
                app(TaxReportService::class)->label($record), (float) $record->report_net,
                (float) $record->report_tax, (float) $record->report_gross,
            ])],
        };
    }

    private function exportFilename(): string
    {
        if ($this->activeTab === 'tax') {
            if (blank($this->startDate) || blank($this->endDate)) {
                return 'fidoma-ustva-alle-zeitraeume';
            }

            [$start, $end] = $this->dates();

            return 'fidoma-ustva-'.$start->format('Y-m-d').'-'.$end->format('Y-m-d');
        }

        $period = filled($this->startDate) && filled($this->endDate)
            ? "{$this->startDate}-{$this->endDate}"
            : 'alle-zeitraeume';

        return 'fidoma-'.match ($this->activeTab) {
            'payments' => 'zahlungen', 'invoices' => 'rechnungen', 'sales' => 'verkauf', default => 'umsatzsteuer',
        }."-{$period}";
    }

    /** @param Collection<int, Invoice> $records */
    private function downloadInvoicePdfs(Collection $records): BinaryFileResponse
    {
        $path = tempnam(sys_get_temp_dir(), 'fidoma-invoices-').'.zip';
        $zip = new ZipArchive;
        $zip->open($path, ZipArchive::CREATE | ZipArchive::OVERWRITE);
        $generator = app(GenerateInvoicePdf::class);

        foreach ($records as $invoice) {
            $zip->addFromString($invoice->getKey().'-'.$generator->filename($invoice), $generator->handle($invoice)->output());
        }

        $zip->close();

        return response()->download($path, 'fidoma-rechnungen.zip')->deleteFileAfterSend();
    }

    /** @param Collection<int, Invoice> $records */
    private function downloadInvoiceXmls(Collection $records): BinaryFileResponse
    {
        $path = tempnam(sys_get_temp_dir(), 'fidoma-xrechnungen-').'.zip';
        $zip = new ZipArchive;
        $zip->open($path, ZipArchive::CREATE | ZipArchive::OVERWRITE);
        $generator = app(GenerateInvoiceXRechnung::class);
        $errors = [];

        foreach ($records as $invoice) {
            try {
                $zip->addFromString($invoice->getKey().'-'.$generator->filename($invoice), $generator->handle($invoice));
            } catch (DomainException $exception) {
                $number = $invoice->invoice_number ?: 'Rechnung #'.$invoice->getKey();
                $errors[] = $number.': '.$exception->getMessage();
            }
        }

        if ($errors !== []) {
            $zip->addFromString(
                'HINWEISE-ZUM-XML-EXPORT.txt',
                "Für folgende Rechnungen konnte keine gültige XRechnung erzeugt werden:\n\n".implode("\n", $errors)."\n",
            );

            Notification::make()
                ->warning()
                ->title('Nicht alle XML-Dateien konnten erzeugt werden.')
                ->body('Details befinden sich in der Hinweisdatei im ZIP-Archiv.')
                ->send();
        }

        $zip->close();

        return response()->download($path, 'fidoma-xrechnungen.zip')->deleteFileAfterSend();
    }
}

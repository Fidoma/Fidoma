<?php

namespace App\Filament\Pages;

use App\Features\BankTransactions\Models\BankTransaction;
use App\Features\Clients\Models\Client;
use App\Features\Invoices\Actions\GenerateInvoicePdf;
use App\Features\Invoices\Models\Invoice;
use App\Features\Invoices\Models\InvoiceItem;
use App\Features\PaymentMethods\Models\PaymentMethod;
use App\Features\Reports\Services\InvoiceReportService;
use App\Features\Reports\Services\PaymentReportService;
use App\Features\Reports\Services\ReportExportService;
use App\Features\Reports\Services\TaxReportService;
use App\Features\Reports\Services\UstvaReportService;
use App\Filament\Concerns\WithoutPageHeading;
use BackedEnum;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\CarbonImmutable;
use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Pages\Page;
use Filament\Support\Enums\Width;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
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

    public string $taxView = 'overview';

    public string $ustvaFrequency = 'month';

    public int $ustvaYear;

    public int $ustvaMonth;

    public int $ustvaQuarter;

    public string $taxationType = 'soll';

    public ?string $startDate = null;

    public ?string $endDate = null;

    public string $paymentDirection = 'all';

    public ?int $paymentMethodId = null;

    public ?int $invoiceStatus = null;

    public ?int $clientId = null;

    public function mount(): void
    {
        $today = CarbonImmutable::today();
        $supportedYears = app(UstvaReportService::class)->supportedYears();
        $this->ustvaYear = in_array($today->year, $supportedYears, true) ? $today->year : max($supportedYears);
        $this->ustvaMonth = $today->month;
        $this->ustvaQuarter = $today->quarter;
        $this->resetTable();
    }

    public static function canAccess(): bool
    {
        return auth()->check();
    }

    public function setTab(string $tab): void
    {
        abort_unless(in_array($tab, ['tax', 'payments', 'invoices'], true), 404);

        $this->activeTab = $tab;
        $this->resetTable();
    }

    public function setTaxView(string $view): void
    {
        abort_unless(in_array($view, ['overview', 'ustva'], true), 404);

        $this->taxView = $view;
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

    public function updatedPaymentDirection(): void
    {
        $this->resetTable();
    }

    public function updatedPaymentMethodId(): void
    {
        $this->resetTable();
    }

    public function updatedInvoiceStatus(): void
    {
        $this->resetTable();
    }

    public function updatedClientId(): void
    {
        $this->resetTable();
    }

    public function table(Table $table): Table
    {
        return match ($this->activeTab) {
            'payments' => $this->paymentsTable($table),
            'invoices' => $this->invoicesTable($table),
            default => $this->taxTable($table),
        };
    }

    /** @return array<string, float|int|null> */
    public function summary(): array
    {
        [$start, $end] = $this->dates();

        return match ($this->activeTab) {
            'payments' => app(PaymentReportService::class)->summary(
                $start, $end, $this->paymentDirection, $this->paymentMethodId,
            ),
            'invoices' => app(InvoiceReportService::class)->summary(
                $start, $end, $this->invoiceStatus, $this->clientId,
            ),
            default => app(TaxReportService::class)->summary($start, $end),
        };
    }

    /** @return array<string, mixed> */
    public function ustvaReport(): array
    {
        [$start, $end] = $this->ustvaDates();

        return app(UstvaReportService::class)->report($start, $end, $this->taxationType, $this->ustvaYear);
    }

    /** @return list<int> */
    public function ustvaYears(): array
    {
        return app(UstvaReportService::class)->supportedYears();
    }

    public function viewUstvaDetailsAction(): Action
    {
        return Action::make('viewUstvaDetails')
            ->modalHeading(fn (array $arguments): string => 'UStVA Kz. '.($arguments['code'] ?? ''))
            ->modalContent(function (array $arguments): View {
                $row = collect($this->ustvaReport()['rows'])
                    ->firstWhere('code', (string) ($arguments['code'] ?? ''));

                return view('filament.pages.reports-ustva-details', ['row' => $row]);
            })
            ->modalSubmitAction(false)
            ->modalCancelActionLabel('Schließen')
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

    public function exportCsv(): StreamedResponse
    {
        [$headings, $rows] = $this->exportData();

        return app(ReportExportService::class)->csv($this->exportFilename(), $headings, $rows);
    }

    public function exportExcel(): BinaryFileResponse
    {
        [$headings, $rows] = $this->exportData();

        return app(ReportExportService::class)->xlsx($this->exportFilename(), $headings, $rows);
    }

    public function exportUstvaPdf(): BinaryFileResponse
    {
        [$start, $end] = $this->ustvaDates();
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
                ...[...$this->dates(), $this->paymentDirection, $this->paymentMethodId],
            ))
            ->columns([
                TextColumn::make('booking_date')->label('Zahlungsdatum')->date('d.m.Y')->sortable(),
                TextColumn::make('payment_type')->label('Zahlungsart')
                    ->state(fn (BankTransaction $record): string => app(PaymentReportService::class)->paymentType($record)),
                TextColumn::make('counterparty_name')->label('Empfänger / Auftraggeber')->placeholder('–')->searchable()->sortable(),
                TextColumn::make('purpose')->label('Verwendungszweck')->placeholder('–')->searchable()->limit(60)->wrap(),
                TextColumn::make('amount')->label('Betrag')->money(fn (BankTransaction $record): string => $record->currency ?: 'EUR', locale: 'de')
                    ->color(fn (BankTransaction $record): string => (float) $record->amount < 0 ? 'danger' : 'success')->sortable(),
                TextColumn::make('account_iban')->label('Bankkonto')->placeholder('–')->searchable(),
                TextColumn::make('invoice_number')->label('Rechnung')->placeholder('–')
                    ->state(fn (BankTransaction $record): ?string => app(PaymentReportService::class)->invoiceNumber($record)),
            ])
            ->defaultSort('booking_date', 'desc')
            ->searchable(false)
            ->paginated([10, 25, 50])
            ->defaultPaginationPageOption(25)
            ->emptyStateHeading('Keine Zahlungen gefunden')
            ->emptyStateDescription('Für den gewählten Zeitraum und die gesetzten Filter liegen keine Bankbewegungen vor.')
            ->emptyStateIcon(Heroicon::OutlinedBanknotes);
    }

    private function invoicesTable(Table $table): Table
    {
        return $table
            ->query(fn (): Builder => app(InvoiceReportService::class)->query(
                ...[...$this->dates(), $this->invoiceStatus, $this->clientId],
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
            ->recordActions([
                Action::make('pdf')->label('PDF')->icon(Heroicon::OutlinedDocumentArrowDown)
                    ->url(fn (Invoice $record): string => route('invoices.pdf', ['invoice' => $record]))
                    ->openUrlInNewTab(),
            ])
            ->toolbarActions([
                BulkAction::make('downloadPdfs')->label('PDFs als ZIP')->icon(Heroicon::OutlinedArchiveBoxArrowDown)
                    ->action(fn (Collection $records): BinaryFileResponse => $this->downloadInvoicePdfs($records)),
            ])
            ->defaultSort('invoice_date_created', 'desc')
            ->searchable(false)
            ->paginated([10, 25, 50])
            ->defaultPaginationPageOption(25)
            ->emptyStateHeading('Keine Rechnungen gefunden')
            ->emptyStateDescription('Für den gewählten Zeitraum und die gesetzten Filter liegen keine Rechnungen vor.')
            ->emptyStateIcon(Heroicon::OutlinedDocumentCurrencyEuro);
    }

    /** @return array{CarbonImmutable, CarbonImmutable} */
    private function dates(): array
    {
        return [
            filled($this->startDate) ? CarbonImmutable::parse($this->startDate)->startOfDay() : CarbonImmutable::create(1900, 1, 1)->startOfDay(),
            filled($this->endDate) ? CarbonImmutable::parse($this->endDate)->endOfDay() : CarbonImmutable::create(9999, 12, 31)->endOfDay(),
        ];
    }

    /** @return array{CarbonImmutable, CarbonImmutable} */
    private function ustvaDates(): array
    {
        if ($this->ustvaFrequency === 'quarter') {
            $start = CarbonImmutable::create($this->ustvaYear, (($this->ustvaQuarter - 1) * 3) + 1, 1)->startOfQuarter();

            return [$start, $start->endOfQuarter()];
        }

        $start = CarbonImmutable::create($this->ustvaYear, $this->ustvaMonth, 1)->startOfMonth();

        return [$start, $start->endOfMonth()];
    }

    /** @return array{list<string>, iterable<list<bool|float|int|string|null>>} */
    private function exportData(): array
    {
        if ($this->activeTab === 'tax' && $this->taxView === 'ustva') {
            $report = $this->ustvaReport();
            [$start, $end] = $this->ustvaDates();
            $period = $start->format('d.m.Y').'–'.$end->format('d.m.Y');
            $taxationType = $this->taxationType === 'soll' ? 'Sollversteuerung' : 'Istversteuerung';
            $rows = collect($report['rows'])->map(fn (array $row): array => [
                $row['code'], $row['section'], $row['description'], $row['net'], $row['tax'],
                $row['supported'] ? 'Berechnet' : 'Nicht verfügbar', $period, $taxationType, null,
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
                'Zahlungsdatum', 'Zahlungsart', 'Empfänger / Auftraggeber', 'Verwendungszweck', 'Betrag', 'Währung', 'Bankkonto', 'Rechnung',
            ], $records->map(fn (BankTransaction $record): array => [
                $record->booking_date?->format('d.m.Y'), app(PaymentReportService::class)->paymentType($record),
                $record->counterparty_name, $record->purpose, (float) $record->amount, $record->currency,
                $record->account_iban, app(PaymentReportService::class)->invoiceNumber($record),
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
        if ($this->activeTab === 'tax' && $this->taxView === 'ustva') {
            [$start, $end] = $this->ustvaDates();

            return 'fidoma-ustva-'.$start->format('Y-m-d').'-'.$end->format('Y-m-d');
        }

        $period = filled($this->startDate) && filled($this->endDate)
            ? "{$this->startDate}-{$this->endDate}"
            : 'alle-zeitraeume';

        return 'fidoma-'.match ($this->activeTab) {
            'payments' => 'zahlungen', 'invoices' => 'rechnungen', default => 'umsatzsteuer',
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
}

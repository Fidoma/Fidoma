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
use BackedEnum;
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
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;
use UnitEnum;
use ZipArchive;

class Reports extends Page implements HasTable
{
    use InteractsWithTable;

    protected static ?string $title = 'Berichte';

    protected static ?string $navigationLabel = 'Berichte';

    protected static ?string $slug = 'reports';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedPresentationChartLine;

    protected static string|UnitEnum|null $navigationGroup = 'Finanzen';

    protected string $view = 'filament.pages.reports';

    protected Width|string|null $maxContentWidth = Width::Full;

    public string $activeTab = 'tax';

    public string $period = 'current_month';

    public string $startDate = '';

    public string $endDate = '';

    public string $paymentDirection = 'all';

    public ?int $paymentMethodId = null;

    public ?string $bankAccount = null;

    public ?int $invoiceStatus = null;

    public ?int $clientId = null;

    public function mount(): void
    {
        $this->applyPeriod();
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

    public function applyPeriod(): void
    {
        $today = CarbonImmutable::today();

        if ($this->period === 'custom') {
            $this->validate([
                'startDate' => ['required', 'date'],
                'endDate' => ['required', 'date', 'after_or_equal:startDate'],
            ], ['endDate.after_or_equal' => 'Das Enddatum muss nach dem Startdatum liegen.']);
        } else {
            [$start, $end] = match ($this->period) {
                'last_month' => [$today->subMonthNoOverflow()->startOfMonth(), $today->subMonthNoOverflow()->endOfMonth()],
                'current_quarter' => [$today->startOfQuarter(), $today->endOfQuarter()],
                'current_year' => [$today->startOfYear(), $today->endOfYear()],
                default => [$today->startOfMonth(), $today->endOfMonth()],
            };

            $this->startDate = $start->toDateString();
            $this->endDate = $end->toDateString();
        }

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

    public function updatedBankAccount(): void
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
                $start, $end, $this->paymentDirection, $this->paymentMethodId, $this->bankAccount,
            ),
            'invoices' => app(InvoiceReportService::class)->summary(
                $start, $end, $this->invoiceStatus, $this->clientId,
            ),
            default => app(TaxReportService::class)->summary($start, $end),
        };
    }

    /** @return array<int, string> */
    public function paymentMethods(): array
    {
        return PaymentMethod::query()->orderBy('payment_method_name')->pluck('payment_method_name', 'payment_method_id')->all();
    }

    /** @return array<string, string> */
    public function bankAccounts(): array
    {
        return BankTransaction::query()->whereNotNull('account_iban')->distinct()->orderBy('account_iban')
            ->pluck('account_iban', 'account_iban')->all();
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
                ...[...$this->dates(), $this->paymentDirection, $this->paymentMethodId, $this->bankAccount],
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
            ->searchPlaceholder('Auftraggeber, Verwendungszweck oder Bankkonto')
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
            ->searchPlaceholder('Rechnungsnummer oder Kunde')
            ->paginated([10, 25, 50])
            ->defaultPaginationPageOption(25)
            ->emptyStateHeading('Keine Rechnungen gefunden')
            ->emptyStateDescription('Für den gewählten Zeitraum und die gesetzten Filter liegen keine Rechnungen vor.')
            ->emptyStateIcon(Heroicon::OutlinedDocumentCurrencyEuro);
    }

    /** @return array{CarbonImmutable, CarbonImmutable} */
    private function dates(): array
    {
        return [CarbonImmutable::parse($this->startDate)->startOfDay(), CarbonImmutable::parse($this->endDate)->endOfDay()];
    }

    /** @return array{list<string>, iterable<list<bool|float|int|string|null>>} */
    private function exportData(): array
    {
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
        return 'fidoma-'.match ($this->activeTab) {
            'payments' => 'zahlungen', 'invoices' => 'rechnungen', default => 'umsatzsteuer',
        }."-{$this->startDate}-{$this->endDate}";
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

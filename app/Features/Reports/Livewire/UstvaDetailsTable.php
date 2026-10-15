<?php

namespace App\Features\Reports\Livewire;

use App\Features\Invoices\Filament\Resources\Invoices\InvoiceResource;
use App\Features\Invoices\Models\InvoiceItem;
use App\Features\Reports\Services\UstvaReportService;
use Carbon\CarbonImmutable;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Schemas\Concerns\InteractsWithSchemas;
use Filament\Schemas\Contracts\HasSchemas;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Component;

class UstvaDetailsTable extends Component implements HasActions, HasSchemas, HasTable
{
    use InteractsWithActions;
    use InteractsWithSchemas;
    use InteractsWithTable;

    public string $code;

    public string $startDate;

    public string $endDate;

    public string $taxationType;

    public function mount(string $code, string $startDate, string $endDate, string $taxationType): void
    {
        $this->code = $code;
        $this->startDate = $startDate;
        $this->endDate = $endDate;
        $this->taxationType = $taxationType;
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(fn (): Builder => app(UstvaReportService::class)->detailQuery(
                CarbonImmutable::parse($this->startDate)->startOfDay(),
                CarbonImmutable::parse($this->endDate)->endOfDay(),
                $this->taxationType,
                $this->code,
            ))
            ->columns([
                TextColumn::make('invoice.invoice_number')
                    ->label('Rechnung')
                    ->searchable()
                    ->sortable()
                    ->weight('medium'),
                TextColumn::make('invoice.client.client_name')
                    ->label('Kunde / Lieferant')
                    ->placeholder('–')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('item_name')
                    ->label('Position')
                    ->searchable()
                    ->sortable()
                    ->wrap(),
                TextColumn::make('invoice.invoice_date_created')
                    ->label('Rechnungsdatum')
                    ->date('d.m.Y')
                    ->sortable(),
                TextColumn::make('item_date')
                    ->label('Leistungsdatum')
                    ->date('d.m.Y')
                    ->placeholder('–')
                    ->sortable(),
                TextColumn::make('tax_relevant_date')
                    ->label('Steuerlich relevantes Datum')
                    ->state(fn (InvoiceItem $record): string => (string) $this->detail($record)['tax_period'])
                    ->placeholder('–'),
                TextColumn::make('net')
                    ->label('Netto')
                    ->state(fn (InvoiceItem $record): float => $this->detail($record)['net'])
                    ->money('EUR', locale: 'de'),
                TextColumn::make('tax')
                    ->label('Umsatzsteuer')
                    ->state(fn (InvoiceItem $record): float => $this->detail($record)['tax'])
                    ->money('EUR', locale: 'de'),
                TextColumn::make('gross')
                    ->label('Brutto')
                    ->state(fn (InvoiceItem $record): float => $this->detail($record)['gross'])
                    ->money('EUR', locale: 'de'),
                TextColumn::make('taxRate.tax_rate_percent')
                    ->label('Steuersatz')
                    ->formatStateUsing(fn ($state): string => rtrim(rtrim(number_format((float) $state, 2, ',', ''), '0'), ',').' %')
                    ->sortable(),
                TextColumn::make('elster_code')
                    ->label('ELSTER-Kz.')
                    ->state(fn (): string => $this->code)
                    ->badge(),
            ])
            ->recordUrl(fn (InvoiceItem $record): string => InvoiceResource::getUrl('edit', ['record' => $record->invoice_id]))
            ->openRecordUrlInNewTab()
            ->defaultSort('item_id', 'desc')
            ->searchPlaceholder('Rechnung, Kunde oder Position')
            ->paginated([10, 25, 50])
            ->defaultPaginationPageOption(10)
            ->emptyStateHeading('Keine zugeordneten Datensätze')
            ->emptyStateDescription('Für diese Kennzahl sind im gewählten Zeitraum keine eindeutig zuordenbaren Rechnungspositionen vorhanden.');
    }

    /** @return array<string, mixed> */
    private function detail(InvoiceItem $item): array
    {
        return app(UstvaReportService::class)->detailForItem($item, $this->taxationType);
    }

    public function render(): View
    {
        return view('livewire.features.reports.ustva-details-table');
    }
}

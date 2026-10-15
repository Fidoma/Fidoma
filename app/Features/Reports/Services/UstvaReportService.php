<?php

namespace App\Features\Reports\Services;

use App\Features\Invoices\Enums\InvoiceStatus;
use App\Features\Invoices\Models\InvoiceItem;
use Brick\Math\BigDecimal;
use Brick\Math\RoundingMode;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

final class UstvaReportService
{
    private const SUPPORTED_YEARS = [2026];

    /**
     * @return array{
     *   year: int, taxation_type: string, rows: list<array<string, mixed>>,
     *   output_tax: float, input_tax: null, adjustments: null, liability: null,
     *   complete: bool, warnings: list<string>
     * }
     */
    public function report(CarbonInterface $start, CarbonInterface $end, string $taxationType, int $year): array
    {
        $details = $this->details($start, $end, $taxationType);
        $definitions = $this->definitions($year);
        $rows = [];

        foreach ($definitions as $definition) {
            $rowDetails = $details->get($definition['code'], collect());
            $supported = in_array($definition['code'], ['81', '86', '87'], true);
            $rows[] = [
                ...$definition,
                'supported' => $supported,
                'net' => $supported ? $this->sumMoney($rowDetails, 'net') : null,
                'tax' => $supported ? $this->sumMoney($rowDetails, 'tax') : null,
                'details' => $rowDetails->values()->all(),
            ];
        }

        $unknown = $details->get('unknown', collect());
        $warnings = [
            'Vorsteuerbeträge aus Eingangsbelegen und Einfuhrumsatzsteuer sind in Fidoma noch nicht strukturiert verfügbar.',
            'Die steuerliche Einstellung Soll-/Istversteuerung ist noch nicht als Unternehmenseinstellung gespeichert und muss für diese Vorschau manuell gewählt werden.',
        ];

        if ($unknown->isNotEmpty()) {
            $warnings[] = $unknown->count().' Rechnungsposition(en) konnten keiner UStVA-Kennzahl eindeutig zugeordnet werden.';
        }

        if ($taxationType === 'ist') {
            $warnings[] = 'Bei Istversteuerung werden Teilzahlungen proportional auf die Steueranteile der Rechnung verteilt.';
        }

        $outputTax = $this->sumMoney(collect($rows)->where('supported', true), 'tax');

        return [
            'year' => $year,
            'taxation_type' => $taxationType,
            'rows' => $rows,
            'output_tax' => $outputTax,
            'input_tax' => null,
            'adjustments' => null,
            'liability' => null,
            'complete' => false,
            'warnings' => $warnings,
        ];
    }

    /** @return Collection<string, Collection<int, array<string, mixed>>> */
    public function details(CarbonInterface $start, CarbonInterface $end, string $taxationType): Collection
    {
        return $this->detailQuery($start, $end, $taxationType)
            ->get()
            ->map(fn (InvoiceItem $item): array => $this->detailForItem($item, $taxationType))
            ->groupBy('code');
    }

    /** @return Builder<InvoiceItem> */
    public function detailQuery(CarbonInterface $start, CarbonInterface $end, string $taxationType, ?string $code = null): Builder
    {
        return InvoiceItem::query()
            ->whereHas('invoice', fn (Builder $query): Builder => $query
                ->where('invoice_status_id', '!=', InvoiceStatus::Draft->value)
                ->where('is_archived', false)
                ->when(
                    $taxationType === 'soll',
                    fn (Builder $invoiceQuery): Builder => $invoiceQuery->whereBetween('invoice_date_created', [$start, $end]),
                    fn (Builder $invoiceQuery): Builder => $invoiceQuery->whereHas(
                        'payments',
                        fn (Builder $paymentQuery): Builder => $paymentQuery->whereBetween('payment_date', [$start, $end]),
                    ),
                ))
            ->when($code !== null, function (Builder $query) use ($code): Builder {
                return match ($code) {
                    '81' => $query->whereHas('taxRate', fn (Builder $taxQuery): Builder => $taxQuery->where('tax_rate_percent', 19)),
                    '86' => $query->whereHas('taxRate', fn (Builder $taxQuery): Builder => $taxQuery->where('tax_rate_percent', 7)),
                    '87' => $query->whereHas('taxRate', fn (Builder $taxQuery): Builder => $taxQuery
                        ->where('tax_rate_percent', '>', 0)
                        ->whereNotIn('tax_rate_percent', [7, 19])),
                    default => $query->whereRaw('1 = 0'),
                };
            })
            ->with([
                'amount',
                'taxRate',
                'invoice.client',
                'invoice.amount',
                'invoice.payments' => fn ($query) => $query->whereBetween('payment_date', [$start, $end]),
            ]);
    }

    /** @return array<string, mixed> */
    public function detailForItem(InvoiceItem $item, string $taxationType): array
    {
        $invoiceTotal = (string) abs((float) ($item->invoice?->amount?->invoice_total ?? 0));
        $periodPayments = (string) abs((float) ($item->invoice?->payments?->sum('payment_amount') ?? 0));

        $rate = $item->taxRate?->tax_rate_percent;
        $code = match (true) {
            $rate === null => 'unknown',
            (float) $rate === 19.0 => '81',
            (float) $rate === 7.0 => '86',
            (float) $rate > 0.0 => '87',
            default => 'unknown',
        };

        return [
            'code' => $code,
            'invoice_id' => $item->invoice_id,
            'invoice_number' => $item->invoice?->invoice_number,
            'client' => $item->invoice?->client?->client_name,
            'item_name' => $item->item_name,
            'invoice_date' => $item->invoice?->invoice_date_created?->format('d.m.Y'),
            'service_date' => $item->item_date?->format('d.m.Y'),
            'tax_period' => $taxationType === 'soll'
                ? $item->invoice?->invoice_date_created?->format('d.m.Y')
                : $item->invoice?->payments?->pluck('payment_date')->filter()->map->format('d.m.Y')->join(', '),
            'rate' => $rate === null ? null : (float) $rate,
            'net' => $this->allocatedMoney((string) ($item->amount?->item_subtotal ?? 0), $invoiceTotal, $periodPayments, $taxationType),
            'tax' => $this->allocatedMoney((string) ($item->amount?->item_tax_total ?? 0), $invoiceTotal, $periodPayments, $taxationType),
            'gross' => $this->allocatedMoney((string) ($item->amount?->item_total ?? 0), $invoiceTotal, $periodPayments, $taxationType),
        ];
    }

    /** @return list<array{code: string, section: string, description: string, basis: bool, tax: bool}> */
    private function definitions(int $year): array
    {
        // Jahresabhängiger Einstiegspunkt; die 2026er Bezeichnungen basieren auf USt 1 A 2026.
        return match ($year) {
            default => [
                ['code' => '81', 'section' => 'Steuerpflichtige Umsätze', 'description' => 'Steuerpflichtige Umsätze zum Steuersatz von 19 %', 'basis' => true, 'tax' => true],
                ['code' => '86', 'section' => 'Steuerpflichtige Umsätze', 'description' => 'Steuerpflichtige Umsätze zum Steuersatz von 7 %', 'basis' => true, 'tax' => true],
                ['code' => '87', 'section' => 'Steuerpflichtige Umsätze', 'description' => 'Steuerpflichtige Umsätze zu anderen Steuersätzen', 'basis' => true, 'tax' => true],
                ['code' => '41', 'section' => 'Steuerfreie Umsätze', 'description' => 'Innergemeinschaftliche Lieferungen an Abnehmer mit USt-IdNr.', 'basis' => true, 'tax' => false],
                ['code' => '43', 'section' => 'Steuerfreie Umsätze', 'description' => 'Weitere steuerfreie Umsätze mit Vorsteuerabzug', 'basis' => true, 'tax' => false],
                ['code' => '48', 'section' => 'Steuerfreie Umsätze', 'description' => 'Steuerfreie Umsätze ohne Vorsteuerabzug', 'basis' => true, 'tax' => false],
                ['code' => '66', 'section' => 'Abziehbare Vorsteuerbeträge', 'description' => 'Vorsteuerbeträge aus Rechnungen anderer Unternehmer', 'basis' => false, 'tax' => true],
                ['code' => '61', 'section' => 'Abziehbare Vorsteuerbeträge', 'description' => 'Entrichtete Einfuhrumsatzsteuer', 'basis' => false, 'tax' => true],
            ],
        };
    }

    public function supportedYears(): array
    {
        return self::SUPPORTED_YEARS;
    }

    private function allocatedMoney(string $amount, string $invoiceTotal, string $periodPayments, string $taxationType): float
    {
        $value = BigDecimal::of($amount);

        if ($taxationType === 'ist') {
            if (BigDecimal::of($invoiceTotal)->isZero()) {
                return 0.0;
            }

            $payment = BigDecimal::of($periodPayments)->isGreaterThan(BigDecimal::of($invoiceTotal))
                ? BigDecimal::of($invoiceTotal)
                : BigDecimal::of($periodPayments);
            $value = $value->multipliedBy($payment)->dividedBy($invoiceTotal, 2, RoundingMode::HalfUp);
        }

        return (float) $value->toScale(2, RoundingMode::HalfUp)->__toString();
    }

    /** @param Collection<int, array<string, mixed>> $rows */
    private function sumMoney(Collection $rows, string $key): float
    {
        $sum = $rows->reduce(
            fn (BigDecimal $carry, array $row): BigDecimal => $carry->plus((string) ($row[$key] ?? 0)),
            BigDecimal::zero(),
        );

        return (float) $sum->toScale(2, RoundingMode::HalfUp)->__toString();
    }
}

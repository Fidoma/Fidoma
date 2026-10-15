<?php

namespace App\Filament\Support;

use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;

final readonly class DashboardPeriod
{
    public function __construct(
        public CarbonImmutable $start,
        public CarbonImmutable $end,
        public CarbonImmutable $previousStart,
        public CarbonImmutable $previousEnd,
        public string $label,
    ) {}

    /** @param array<string, mixed>|null $filters */
    public static function fromFilters(?array $filters): self
    {
        $period = $filters['period'] ?? 'this_month';
        $today = CarbonImmutable::today();

        [$start, $end, $label] = match ($period) {
            'last_month' => [$today->subMonthNoOverflow()->startOfMonth(), $today->subMonthNoOverflow()->endOfMonth(), 'Letzter Monat'],
            'this_quarter' => [$today->startOfQuarter(), $today->endOfQuarter(), 'Dieses Quartal'],
            'this_year' => [$today->startOfYear(), $today->endOfYear(), 'Dieses Jahr'],
            'custom' => self::customRange($filters, $today),
            default => [$today->startOfMonth(), $today->endOfMonth(), 'Dieser Monat'],
        };

        $days = $start->diffInDays($end) + 1;

        return new self(
            start: $start->startOfDay(),
            end: $end->endOfDay(),
            previousStart: $start->subDays($days)->startOfDay(),
            previousEnd: $start->subDay()->endOfDay(),
            label: $label,
        );
    }

    /** @return array{CarbonImmutable, CarbonImmutable, string} */
    private static function customRange(array $filters, CarbonImmutable $today): array
    {
        $start = self::date($filters['start_date'] ?? null) ?? $today->startOfMonth();
        $end = self::date($filters['end_date'] ?? null) ?? $today;

        if ($start->greaterThan($end)) {
            [$start, $end] = [$end, $start];
        }

        return [$start, $end, $start->format('d.m.Y').' – '.$end->format('d.m.Y')];
    }

    private static function date(mixed $value): ?CarbonImmutable
    {
        if ($value instanceof CarbonInterface) {
            return CarbonImmutable::instance($value);
        }

        return filled($value) ? CarbonImmutable::parse((string) $value) : null;
    }
}

<?php

namespace App\Features\Clients\Support;

use Carbon\CarbonImmutable;

final readonly class ClientAnalyticsPeriod
{
    public function __construct(
        public CarbonImmutable $start,
        public CarbonImmutable $end,
    ) {}

    public static function thisMonth(): self
    {
        $today = CarbonImmutable::today();

        return new self($today->startOfMonth()->startOfDay(), $today->endOfMonth()->endOfDay());
    }

    public static function thisYear(): self
    {
        $today = CarbonImmutable::today();

        return new self($today->startOfYear()->startOfDay(), $today->endOfYear()->endOfDay());
    }

    public static function custom(string $startDate, string $endDate): self
    {
        return new self(
            CarbonImmutable::parse($startDate)->startOfDay(),
            CarbonImmutable::parse($endDate)->endOfDay(),
        );
    }
}

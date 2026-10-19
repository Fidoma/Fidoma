<?php

namespace App\Features\TradeFairs\Queries;

use App\Features\TradeFairs\Models\TradeFair;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Collection;

final class TradeFairQuery
{
    /** @return Collection<int, TradeFair> */
    public function upcoming(CarbonImmutable $today): Collection
    {
        return TradeFair::query()
            ->visibleInScheduler()
            ->whereDate('start_date', '<=', $today->addDays(14)->toDateString())
            ->whereDate('end_date', '>=', $today->toDateString())
            ->orderBy('start_date')
            ->orderBy('name')
            ->get();
    }

    /**
     * The end of a DayPilot range is exclusive.
     *
     * @return Collection<int, TradeFair>
     */
    public function overlapping(CarbonImmutable $start, CarbonImmutable $exclusiveEnd): Collection
    {
        return TradeFair::query()
            ->visibleInScheduler()
            ->whereDate('start_date', '<', $exclusiveEnd->toDateString())
            ->whereDate('end_date', '>=', $start->toDateString())
            ->orderBy('start_date')
            ->orderBy('name')
            ->get();
    }
}

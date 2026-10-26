<?php

namespace App\Features\Reservations\Filament\Widgets;

use App\Features\Reservations\Data\ReservationStatisticsFilter;
use App\Features\Reservations\Enums\ReservationStatus;
use App\Features\Reservations\Models\ReservationItem;
use App\Features\Reservations\Services\ReservationStatisticsService;
use App\Features\Reservations\Support\ReservationStatisticsLinks;
use Carbon\CarbonImmutable;
use Filament\Support\Icons\Heroicon;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class ReservationListKpis extends StatsOverviewWidget
{
    protected ?string $pollingInterval = null;

    protected function getColumns(): int
    {
        return 4;
    }

    protected function getStats(): array
    {
        $today = CarbonImmutable::today();
        $firstArrival = ReservationItem::query()->min('arrival_date');
        $lastDeparture = ReservationItem::query()->max('departure_date');
        $all = new ReservationStatisticsFilter(
            start: CarbonImmutable::parse($firstArrival ?: $today)->startOfDay(),
            end: CarbonImmutable::parse($lastDeparture ?: $today->addDay())->max($today->addDay())->startOfDay(),
        );
        $todayFilter = new ReservationStatisticsFilter($today, $today->addDay());
        $futureFilter = new ReservationStatisticsFilter($today, $today->addDays(31));
        $service = app(ReservationStatisticsService::class);
        $allSummary = $service->summary($all);
        $todaySummary = $service->summary($todayFilter);
        $futureSummary = $service->summary($futureFilter);

        return [
            Stat::make(__('reservations::messages.statistics.list.active'), $allSummary['reservations'] - ($allSummary['status_counts'][ReservationStatus::Canceled->value] ?? 0))
                ->icon(Heroicon::OutlinedCalendarDays)
                ->url(ReservationStatisticsLinks::forStayPeriod($all->start, $all->end)),
            Stat::make(__('reservations::messages.statistics.list.today'), $todaySummary['arrivals_today'].' / '.$todaySummary['departures_today'])
                ->description(__('reservations::messages.statistics.kpis.arrivals_departures'))
                ->icon(Heroicon::OutlinedArrowsRightLeft)
                ->url(ReservationStatisticsLinks::toList(['schedule' => ['value' => 'today_movements']])),
            Stat::make(__('reservations::messages.statistics.list.next_30_days'), $futureSummary['future_arrivals'])
                ->description(__('reservations::messages.statistics.kpis.future_breakdown', [
                    'confirmed' => $futureSummary['future_confirmed_arrivals'],
                    'open' => $futureSummary['future_open_arrivals'],
                ]))
                ->icon(Heroicon::OutlinedArrowRightEndOnRectangle)
                ->url(ReservationStatisticsLinks::toList(['schedule' => ['value' => 'future_arrivals']])),
            Stat::make(__('reservations::messages.statistics.kpis.unbilled'), $allSummary['unbilled'])
                ->icon(Heroicon::OutlinedDocumentText)
                ->color($allSummary['unbilled'] > 0 ? 'warning' : 'success')
                ->url(ReservationStatisticsLinks::toList(['billing' => ['value' => 'unbilled']])),
        ];
    }
}

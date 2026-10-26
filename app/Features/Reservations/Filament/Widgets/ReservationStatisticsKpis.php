<?php

namespace App\Features\Reservations\Filament\Widgets;

use App\Features\Reservations\Data\ReservationStatisticsFilter;
use App\Features\Reservations\Enums\ReservationStatus;
use App\Features\Reservations\Services\ReservationStatisticsService;
use App\Features\Reservations\Support\ReservationStatisticsLinks;
use Filament\Support\Icons\Heroicon;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class ReservationStatisticsKpis extends StatsOverviewWidget
{
    use InteractsWithPageFilters;

    protected int|string|array $columnSpan = 'full';

    protected ?string $pollingInterval = null;

    protected function getColumns(): int
    {
        return 4;
    }

    protected function getStats(): array
    {
        $filter = ReservationStatisticsFilter::fromArray($this->pageFilters);
        $service = app(ReservationStatisticsService::class);
        $summary = $service->summary($filter);
        $previous = $service->summary($filter->previous());
        $previousYear = $service->summary($filter->previousYear());
        $stayUrl = ReservationStatisticsLinks::forFilter($filter);

        return [
            Stat::make(__('reservations::messages.statistics.kpis.reservations'), $summary['reservations'])
                ->description($this->comparison($summary['reservations'], $previous['reservations'], __('reservations::messages.statistics.comparison.previous_period')))
                ->descriptionIcon(Heroicon::OutlinedCalendarDays)
                ->url($stayUrl),
            Stat::make(__('reservations::messages.statistics.kpis.future_arrivals'), $summary['future_arrivals'])
                ->description(__('reservations::messages.statistics.kpis.future_breakdown', [
                    'confirmed' => $summary['future_confirmed_arrivals'],
                    'open' => $summary['future_open_arrivals'],
                ]))
                ->descriptionIcon(Heroicon::OutlinedArrowRightEndOnRectangle)
                ->color('info')
                ->url(ReservationStatisticsLinks::forFilter($filter, [
                    'schedule' => ['value' => 'future_arrivals'],
                ])),
            Stat::make(__('reservations::messages.statistics.kpis.today_movements'), $summary['arrivals_today'].' / '.$summary['departures_today'])
                ->description(__('reservations::messages.statistics.kpis.arrivals_departures'))
                ->descriptionIcon(Heroicon::OutlinedArrowsRightLeft)
                ->url(ReservationStatisticsLinks::forFilter($filter, [
                    'schedule' => ['value' => 'today_movements'],
                ])),
            Stat::make(__('reservations::messages.statistics.kpis.canceled'), $summary['status_counts'][ReservationStatus::Canceled->value] ?? 0)
                ->description(__('reservations::messages.statistics.kpis.selected_period'))
                ->descriptionIcon(Heroicon::OutlinedXCircle)
                ->color('danger')
                ->url(ReservationStatisticsLinks::forFilter($filter, $filter->status === null ? [
                    'reservation_status_id' => ['value' => ReservationStatus::Canceled->value],
                ] : [])),
            Stat::make(__('reservations::messages.statistics.kpis.unbilled'), $summary['unbilled'])
                ->description(__('reservations::messages.statistics.kpis.partial_paid', ['count' => $summary['partially_paid']]))
                ->descriptionIcon(Heroicon::OutlinedDocumentText)
                ->color($summary['unbilled'] > 0 ? 'warning' : 'success')
                ->url(ReservationStatisticsLinks::forFilter($filter, [
                    'billing' => ['value' => 'unbilled'],
                ])),
            Stat::make(__('reservations::messages.statistics.kpis.occupancy'), number_format($summary['occupancy_rate'], 1, ',', '.').' %')
                ->description($this->comparison($summary['occupancy_rate'], $previousYear['occupancy_rate'], __('reservations::messages.statistics.comparison.previous_year')))
                ->descriptionIcon(Heroicon::OutlinedChartBarSquare)
                ->color('primary')
                ->url($stayUrl),
            Stat::make(__('reservations::messages.statistics.kpis.room_nights'), $summary['occupied_room_nights'].' / '.$summary['available_room_nights'])
                ->description(__('reservations::messages.statistics.kpis.actual_forecast', [
                    'actual' => $summary['actual_room_nights'],
                    'forecast' => $summary['forecast_room_nights'],
                ]))
                ->descriptionIcon(Heroicon::OutlinedHomeModern)
                ->url($stayUrl),
            Stat::make(__('reservations::messages.statistics.kpis.unused'), $summary['unused_room_nights'])
                ->description(__('reservations::messages.statistics.kpis.current_capacity'))
                ->descriptionIcon(Heroicon::OutlinedMinusCircle)
                ->color($summary['unused_room_nights'] > 0 ? 'warning' : 'success')
                ->url($stayUrl),
        ];
    }

    private function comparison(float|int $current, float|int $comparison, string $label): string
    {
        if ((float) $comparison === 0.0) {
            return __('reservations::messages.statistics.comparison.no_value', ['period' => $label]);
        }

        $change = (((float) $current - (float) $comparison) / abs((float) $comparison)) * 100;

        return __('reservations::messages.statistics.comparison.value', [
            'value' => number_format(abs($change), 1, ',', '.'),
            'direction' => $change >= 0
                ? __('reservations::messages.statistics.comparison.more')
                : __('reservations::messages.statistics.comparison.less'),
            'period' => $label,
        ]);
    }
}

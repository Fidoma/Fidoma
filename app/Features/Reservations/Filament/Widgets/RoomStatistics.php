<?php

namespace App\Features\Reservations\Filament\Widgets;

use App\Features\Reservations\Data\ReservationStatisticsFilter;
use App\Features\Reservations\Services\ReservationStatisticsService;
use Filament\Support\RawJs;
use Filament\Widgets\ChartWidget;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Collection;
use Illuminate\Support\Js;

class RoomStatistics extends ChartWidget
{
    use InteractsWithPageFilters;

    protected int|string|array $columnSpan = 'full';

    protected ?string $maxHeight = '560px';

    protected ?string $pollingInterval = null;

    /** @var Collection<int, array<string, bool|float|int|string>>|null */
    private ?Collection $roomMetrics = null;

    public function getHeading(): string
    {
        return __('reservations::messages.statistics.rooms.heading');
    }

    public function getDescription(): string
    {
        return __('reservations::messages.statistics.rooms.description');
    }

    public function getEmptyStateHeading(): string|Htmlable
    {
        return __('reservations::messages.statistics.rooms.empty');
    }

    public function getChartAssistiveContent(): string
    {
        return $this->roomMetrics()
            ->map(fn (array $room): string => __('reservations::messages.statistics.rooms.assistive', [
                'room' => $room['room'],
                'occupied' => $room['occupied'],
                'available' => $room['available'],
                'unused' => $room['unused'],
                'rate' => number_format($room['occupancy_rate'], 1, ',', '.'),
                'conflicts' => $room['conflicts'],
            ]))
            ->implode(' ');
    }

    protected function getData(): array
    {
        $rooms = $this->roomMetrics();

        if ($rooms->isEmpty()) {
            return [];
        }

        return [
            'datasets' => [
                [
                    'label' => __('reservations::messages.statistics.rooms.rate'),
                    'data' => $rooms->pluck('occupancy_rate')->all(),
                    'occupied' => $rooms->pluck('occupied')->all(),
                    'available' => $rooms->pluck('available')->all(),
                    'unused' => $rooms->pluck('unused')->all(),
                    'conflicts' => $rooms->pluck('conflicts')->all(),
                    'backgroundColor' => $rooms->map(fn (array $room): string => match (true) {
                        $room['conflicts'] > 0 => '#ef4444',
                        ! $room['active'] => '#94a3b8',
                        default => '#f59e0b',
                    })->all(),
                    'borderWidth' => 0,
                    'borderRadius' => 5,
                    'stack' => 'capacity',
                ],
                [
                    'label' => __('reservations::messages.statistics.rooms.free_capacity'),
                    'data' => $rooms->map(fn (array $room): float => $room['active']
                        ? round(max(0, 100 - (float) $room['occupancy_rate']), 1)
                        : 0.0)->all(),
                    'backgroundColor' => 'rgba(148, 163, 184, 0.35)',
                    'borderWidth' => 0,
                    'borderRadius' => 5,
                    'stack' => 'capacity',
                ],
            ],
            'labels' => $rooms->map(fn (array $room): string => $room['active']
                ? $room['room']
                : $room['room'].' ('.__('reservations::messages.statistics.rooms.inactive').')')->all(),
        ];
    }

    protected function getOptions(): RawJs
    {
        $locale = Js::from(app()->getLocale() === 'de' ? 'de-DE' : 'en-US')->toHtml();
        $rate = Js::from(__('reservations::messages.statistics.rooms.rate'))->toHtml();
        $freeCapacity = Js::from(__('reservations::messages.statistics.rooms.free_capacity'))->toHtml();
        $occupied = Js::from(__('reservations::messages.statistics.rooms.occupied'))->toHtml();
        $available = Js::from(__('reservations::messages.statistics.rooms.available'))->toHtml();
        $unused = Js::from(__('reservations::messages.statistics.rooms.unused'))->toHtml();
        $conflicts = Js::from(__('reservations::messages.statistics.rooms.conflicts'))->toHtml();

        return RawJs::make(<<<JS
            {
                indexAxis: 'y',
                maintainAspectRatio: false,
                interaction: {
                    mode: 'index',
                    axis: 'y',
                    intersect: false,
                },
                scales: {
                    x: {
                        beginAtZero: true,
                        max: 100,
                        stacked: true,
                        ticks: {
                            callback: (value) => value + ' %',
                        },
                    },
                    y: {
                        stacked: true,
                        grid: {
                            display: false,
                        },
                    },
                },
                plugins: {
                    legend: {
                        display: true,
                        position: 'bottom',
                    },
                    tooltip: {
                        mode: 'index',
                        axis: 'y',
                        intersect: false,
                        callbacks: {
                            label: (context) => {
                                const percentage = Number(context.raw ?? 0).toLocaleString({$locale}, {
                                    minimumFractionDigits: 1,
                                    maximumFractionDigits: 1,
                                });

                                return (context.datasetIndex === 1 ? {$freeCapacity} : {$rate})
                                    + ': ' + percentage + ' %';
                            },
                            afterBody: (contexts) => {
                                const context = contexts[0];

                                if (! context) {
                                    return [];
                                }

                                const index = context.dataIndex;
                                const details = context.chart.data.datasets[0];
                                const lines = [
                                    {$occupied} + ': ' + details.occupied[index],
                                    {$available} + ': ' + details.available[index],
                                    {$unused} + ': ' + details.unused[index],
                                ];

                                if (details.conflicts[index] > 0) {
                                    lines.push({$conflicts} + ': ' + details.conflicts[index]);
                                }

                                return lines;
                            },
                        },
                    },
                },
            }
        JS);
    }

    protected function getType(): string
    {
        return 'bar';
    }

    /** @return Collection<int, array<string, bool|float|int|string>> */
    private function roomMetrics(): Collection
    {
        return $this->roomMetrics ??= app(ReservationStatisticsService::class)
            ->roomMetrics(ReservationStatisticsFilter::fromArray($this->pageFilters));
    }
}
